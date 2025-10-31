<?php

require_once '../inc/funciones.php';
require_once '../inc/mailer.php';

/* ===========================================
   FUNCIONES GLOBALES DEL SISTEMA (MySQLi)
   Compatible con tu BD rides y tus columnas
   =========================================== */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/conexion.php';

/* ===== Helpers de sesión/roles ===== */
function isLoggedIn()   { return isset($_SESSION['user']); }
function esAdmin()      { return isset($_SESSION['user']) && $_SESSION['user']['tipo'] === 'admin'; }
function esChofer()     { return isset($_SESSION['user']) && $_SESSION['user']['tipo'] === 'chofer'; }
function esPasajero()   { return isset($_SESSION['user']) && $_SESSION['user']['tipo'] === 'pasajero'; }

function requireLogin($redirect = '../public/index.php') {
    if (!isLoggedIn()) { header("Location: $redirect"); exit; }
}

/* ========= USUARIOS / AUTENTICACIÓN ========= */

function obtenerUsuarioPorEmail($email) {
    global $mysqli;
    $sql = "SELECT * FROM usuarios WHERE correo = ? LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return null;
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res ? $res->fetch_assoc() : null;
}

function obtenerUsuarioPorId($id) {
    global $mysqli;
    $sql = "SELECT * FROM usuarios WHERE id = ? LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return null;
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res ? $res->fetch_assoc() : null;
}

/** Verifica credenciales -> [bool ok, array|null user, string msg] */
function verificarCredenciales($correo, $password_plano) {
    $u = obtenerUsuarioPorEmail($correo);
    if (!$u) return [false, null, "Usuario no encontrado."];

    // estado válido
    if (!in_array($u['estado'], ['activo','pendiente'])) {
        return [false, null, "Usuario inactivo."];
    }

    // password
    if (!password_verify($password_plano, $u['password_hash'])) {
        return [false, null, "Contraseña incorrecta."];
    }

    return [true, $u, ""];
}

/** Guarda datos en sesión (lo mínimo necesario) */
function iniciarSesionUsuario(array $u) {
    $_SESSION['user'] = [
        'id'       => (int)$u['id'],
        'nombre'   => $u['nombre'] ?? '',
        'apellido' => $u['apellido'] ?? '',
        'correo'   => $u['correo'] ?? '',
        'tipo'     => $u['tipo'] ?? 'pasajero',
        'estado'   => $u['estado'] ?? 'pendiente',
        'foto'     => $u['foto'] ?? null,
    ];
}

/** Cierra sesión */
function cerrarSesion() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time()-42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
    }
    session_destroy();
}

/** (Opcional) volver a cargar datos del usuario desde BD */
function refrescarUsuarioEnSesion() {
    if (!isLoggedIn()) return;
    $u = obtenerUsuarioPorId($_SESSION['user']['id']);
    if ($u) iniciarSesionUsuario($u);
}

/* ========= SUBIDAS ========= */

function subirFotoVehiculo($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return null;
    $dir = __DIR__ . '/../uploads/vehiculos';
    if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) return null;
    $nombre = 'veh_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir . '/' . $nombre;
    if (!move_uploaded_file($file['tmp_name'], $dest)) return null;
    return 'uploads/vehiculos/' . $nombre; // ruta relativa para HTML
}

/* ========= VEHÍCULOS (tu esquema) =========
   Tabla: vehiculos (id, usuario_id, placa, color, marca, modelo, anio YEAR, capacidad, foto)
*/

function crearVehiculo($usuarioId, $placa, $marca, $modelo, $anio, $color, $capacidad, $fotoPath = null) {
    global $mysqli;
    $sql = "INSERT INTO vehiculos (usuario_id, placa, color, marca, modelo, anio, capacidad, foto)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return false;
    // tipos: i s s s s i i s
    $stmt->bind_param("issssiis", $usuarioId, $placa, $color, $marca, $modelo, $anio, $capacidad, $fotoPath);
    return $stmt->execute();
}

function actualizarVehiculo($vehiculoId, $usuarioId, $placa, $marca, $modelo, $anio, $color, $capacidad, $fotoPath = null) {
    global $mysqli;
    if ($fotoPath) {
        $sql = "UPDATE vehiculos
                   SET placa=?, color=?, marca=?, modelo=?, anio=?, capacidad=?, foto=?
                 WHERE id=? AND usuario_id=?";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) return false;
        // tipos: s s s s i i s i i
        $stmt->bind_param("ssssiisii", $placa, $color, $marca, $modelo, $anio, $capacidad, $fotoPath, $vehiculoId, $usuarioId);
    } else {
        $sql = "UPDATE vehiculos
                   SET placa=?, color=?, marca=?, modelo=?, anio=?, capacidad=?
                 WHERE id=? AND usuario_id=?";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) return false;
        // tipos: s s s s i i i i
        $stmt->bind_param("ssssiiii", $placa, $color, $marca, $modelo, $anio, $capacidad, $vehiculoId, $usuarioId);
    }
    return $stmt->execute();
}

function eliminarVehiculo($vehiculoId, $usuarioId) {
    global $mysqli;
    // Tu FK en rides_data(vehiculo_id) es ON DELETE SET NULL, se puede eliminar
    $stmt = $mysqli->prepare("DELETE FROM vehiculos WHERE id=? AND usuario_id=?");
    if (!$stmt) return false;
    $stmt->bind_param("ii", $vehiculoId, $usuarioId);
    return $stmt->execute();
}

function listarVehiculosUsuario($usuarioId) {
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT * FROM vehiculos WHERE usuario_id=? ORDER BY creado_en DESC");
    if (!$stmt) return false;
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    return $stmt->get_result();
}

/* ========= RIDES (tu esquema) =========
   Tabla: rides_data (id, usuario_id, vehiculo_id NULL, nombre, lugar_salida, lugar_llegada, fecha NOT NULL, hora, costo, espacios)
*/

function crearRide($usuarioId, $vehiculoId, $nombre, $salida, $llegada, $fecha, $hora, $costo, $espacios) {
    global $mysqli;
    $sql = "INSERT INTO rides_data (usuario_id, vehiculo_id, nombre, lugar_salida, lugar_llegada, fecha, hora, costo, espacios)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return false;

    // Permitir vehiculo_id NULL (tu FK es ON DELETE SET NULL)
    if ($vehiculoId === '' || $vehiculoId === null) { $vehiculoId = null; }

    // Tipos: i (usuario_id), i (vehiculo_id), s, s, s, s, s, d, i
    $stmt->bind_param("iisssssdi", $usuarioId, $vehiculoId, $nombre, $salida, $llegada, $fecha, $hora, $costo, $espacios);
    return $stmt->execute();
}


function actualizarRide($rideId, $usuarioId, $vehiculoId, $nombre, $salida, $llegada, $fecha, $hora, $costo, $espacios) {
    global $mysqli;
    $sql = "UPDATE rides_data
               SET vehiculo_id=?, nombre=?, lugar_salida=?, lugar_llegada=?, fecha=?, hora=?, costo=?, espacios=?
             WHERE id=? AND usuario_id=?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return false;

    if ($vehiculoId === '' || $vehiculoId === null) { $vehiculoId = null; }

    // Tipos SIN ESPACIOS: i s s s s s d i i i  => "isssssdiii"
    $stmt->bind_param("isssssdiii",
        $vehiculoId, $nombre, $salida, $llegada, $fecha, $hora, $costo, $espacios, $rideId, $usuarioId
    );
    return $stmt->execute();
}


function eliminarRide($rideId, $usuarioId) {
    global $mysqli;
    $stmt = $mysqli->prepare("DELETE FROM rides_data WHERE id=? AND usuario_id=?");
    if (!$stmt) return false;
    $stmt->bind_param("ii", $rideId, $usuarioId);
    return $stmt->execute();
}

function listarRidesUsuario($usuarioId) {
    global $mysqli;
    $sql = "SELECT r.*, v.placa, v.marca, v.modelo, v.color
              FROM rides_data r
         LEFT JOIN vehiculos v ON v.id = r.vehiculo_id
             WHERE r.usuario_id=?
          ORDER BY r.creado_en DESC";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    return $stmt->get_result();
}

/* ========= (Opcional) validar espacios vs capacidad ========= */
function capacidadVehiculo($vehiculoId, $usuarioId) {
    global $mysqli;
    if (!$vehiculoId) return null;
    $stmt = $mysqli->prepare("SELECT capacidad FROM vehiculos WHERE id=? AND usuario_id=?");
    if (!$stmt) return null;
    $stmt->bind_param("ii", $vehiculoId, $usuarioId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return $res ? (int)$res['capacidad'] : null;
}


/* ===================================================================
   RESERVAS – funciones con transacciones y control de cupos
   Tablas: rides_data (espacios), reservas (estado, cantidad)
   Estados: pendiente, aceptada, rechazada, cancelada, finalizada
   =================================================================== */

function esUsuarioPasajero() { return isset($_SESSION['user']) && $_SESSION['user']['tipo'] === 'pasajero'; }

function obtenerRidePorId($rideId) {
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT * FROM rides_data WHERE id=? LIMIT 1");
    if (!$stmt) return null;
    $stmt->bind_param("i", $rideId);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res ? $res->fetch_assoc() : null;
}

/**
 * Crea una reserva (estado = 'pendiente') y descuenta cupos de inmediato.
 * Reglas:
 * - Solo pasajeros pueden reservar
 * - No puede reservar su propio ride
 * - cantidad <= espacios disponibles
 * - Si espacios = 0 o insuficientes, falla
 */
function crearReserva($rideId, $pasajeroId, $cantidad = 1) {
    if (!esUsuarioPasajero()) return [false, "Solo usuarios pasajeros pueden reservar."];

    global $mysqli;
    if ($cantidad < 1) return [false, "Cantidad inválida."];

    $mysqli->begin_transaction();
    try {
        // Bloquea el ride para control de cupos
        $stmt = $mysqli->prepare("SELECT usuario_id, espacios FROM rides_data WHERE id=? FOR UPDATE");
        $stmt->bind_param("i", $rideId);
        $stmt->execute();
        $stmt->bind_result($choferId, $espacios);
        if (!$stmt->fetch()) {
            $mysqli->rollback();
            return [false, "Ride no encontrado."];
        }
        $stmt->close();

        if ($choferId === $pasajeroId) {
            $mysqli->rollback();
            return [false, "No puedes reservar tu propio ride."];
        }
        if ($espacios < $cantidad) {
            $mysqli->rollback();
            return [false, "No hay espacios suficientes. Disponibles: $espacios."];
        }

        // Inserta reserva (pendiente)
        $stmt = $mysqli->prepare("INSERT INTO reservas (ride_id, pasajero_id, estado, cantidad) VALUES (?, ?, 'pendiente', ?)");
        $stmt->bind_param("iii", $rideId, $pasajeroId, $cantidad);
        if (!$stmt->execute()) {
            $mysqli->rollback();
            return [false, "No se pudo crear la reserva."];
        }

        // Descuenta cupos del ride
        $stmt = $mysqli->prepare("UPDATE rides_data SET espacios = espacios - ? WHERE id=?");
        $stmt->bind_param("ii", $cantidad, $rideId);
        if (!$stmt->execute()) {
            $mysqli->rollback();
            return [false, "No se pudo actualizar los espacios del ride."];
        }

        $mysqli->commit();
        return [true, "Reserva creada correctamente."];
    } catch (Throwable $e) {
        $mysqli->rollback();
        return [false, "Error en reserva: ".$e->getMessage()];
    }
}

/**
 * Pasajero cancela su reserva (si está pendiente o aceptada)
 * - Reintegra cupos al ride
 */
function cancelarReservaPorPasajero($reservaId, $pasajeroId) {
    if (!esUsuarioPasajero()) return [false, "Acción permitida solo a pasajeros."];
    global $mysqli;

    $mysqli->begin_transaction();
    try {
        // Obtiene la reserva y bloquea
        $stmt = $mysqli->prepare("SELECT r.ride_id, r.pasajero_id, r.estado, r.cantidad 
                                  FROM reservas r
                                  WHERE r.id=? FOR UPDATE");
        $stmt->bind_param("i", $reservaId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if (!$res) { $mysqli->rollback(); return [false,"Reserva no encontrada."]; }

        if ((int)$res['pasajero_id'] !== (int)$pasajeroId) { $mysqli->rollback(); return [false,"No puedes cancelar esta reserva."]; }
        if (!in_array($res['estado'], ['pendiente','aceptada'])) { $mysqli->rollback(); return [false,"No se puede cancelar en estado ".$res['estado']."."]; }

        // Marca cancelada
        $stmt = $mysqli->prepare("UPDATE reservas SET estado='cancelada' WHERE id=?");
        $stmt->bind_param("i", $reservaId);
        if (!$stmt->execute()) { $mysqli->rollback(); return [false,"No se pudo cancelar la reserva."]; }

        // Reintegra cupos
        $stmt = $mysqli->prepare("UPDATE rides_data SET espacios = espacios + ? WHERE id=?");
        $stmt->bind_param("ii", $res['cantidad'], $res['ride_id']);
        if (!$stmt->execute()) { $mysqli->rollback(); return [false,"No se pudo reintegrar espacios."]; }

        $mysqli->commit();
        return [true, "Reserva cancelada."];
    } catch (Throwable $e) {
        $mysqli->rollback();
        return [false, "Error al cancelar: ".$e->getMessage()];
    }
}

/**
 * Chofer acepta reserva (cambia a 'aceptada')
 * - NO toca cupos (ya fueron descontados al crear)
 */
function aceptarReservaPorChofer($reservaId, $choferId) {
    global $mysqli;

    $mysqli->begin_transaction();
    try {
        // Ubicar reserva y ride del chofer
        $stmt = $mysqli->prepare("SELECT r.id, r.estado, r.ride_id, r.cantidad, d.usuario_id AS chofer
                                  FROM reservas r
                                  JOIN rides_data d ON d.id = r.ride_id
                                  WHERE r.id=? FOR UPDATE");
        $stmt->bind_param("i", $reservaId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if (!$res) { $mysqli->rollback(); return [false, "Reserva no encontrada."]; }
        if ((int)$res['chofer'] !== (int)$choferId) { $mysqli->rollback(); return [false,"No puedes aceptar reservas de otro chofer."]; }
        if ($res['estado'] !== 'pendiente') { $mysqli->rollback(); return [false,"Solo se pueden aceptar reservas pendientes."]; }

        $stmt = $mysqli->prepare("UPDATE reservas SET estado='aceptada' WHERE id=?");
        $stmt->bind_param("i", $reservaId);
        if (!$stmt->execute()) { $mysqli->rollback(); return [false,"No se pudo aceptar la reserva."]; }

        $mysqli->commit();
        return [true, "Reserva aceptada."];
    } catch (Throwable $e) {
        $mysqli->rollback();
        return [false, "Error al aceptar: ".$e->getMessage()];
    }
}

/**
 * Chofer rechaza reserva (cambia a 'rechazada')
 * - Debe devolver cupos al ride
 */
function rechazarReservaPorChofer($reservaId, $choferId) {
    global $mysqli;

    $mysqli->begin_transaction();
    try {
        $stmt = $mysqli->prepare("SELECT r.id, r.estado, r.ride_id, r.cantidad, d.usuario_id AS chofer
                                  FROM reservas r
                                  JOIN rides_data d ON d.id = r.ride_id
                                  WHERE r.id=? FOR UPDATE");
        $stmt->bind_param("i", $reservaId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if (!$res) { $mysqli->rollback(); return [false, "Reserva no encontrada."]; }
        if ((int)$res['chofer'] !== (int)$choferId) { $mysqli->rollback(); return [false,"No puedes rechazar reservas de otro chofer."]; }
        if ($res['estado'] !== 'pendiente') { $mysqli->rollback(); return [false,"Solo se pueden rechazar reservas pendientes."]; }

        // Rechazar
        $stmt = $mysqli->prepare("UPDATE reservas SET estado='rechazada' WHERE id=?");
        $stmt->bind_param("i", $reservaId);
        if (!$stmt->execute()) { $mysqli->rollback(); return [false,"No se pudo rechazar la reserva."]; }

        // Devolver cupos
        $stmt = $mysqli->prepare("UPDATE rides_data SET espacios = espacios + ? WHERE id=?");
        $stmt->bind_param("ii", $res['cantidad'], $res['ride_id']);
        if (!$stmt->execute()) { $mysqli->rollback(); return [false,"No se pudo reintegrar espacios."]; }

        $mysqli->commit();
        return [true, "Reserva rechazada."];
    } catch (Throwable $e) {
        $mysqli->rollback();
        return [false, "Error al rechazar: ".$e->getMessage()];
    }
}

/** Listado reservas del PASAJERO (activas/pasadas) */
function listarReservasPasajero($pasajeroId, $scope = 'activas') {
    global $mysqli;
    // Activas: fecha >= hoy Y estado en (pendiente, aceptada)
    // Pasadas: fecha < hoy  O estado en (rechazada, cancelada, finalizada)
    $cond = ($scope === 'activas')
        ? "(d.fecha >= CURDATE() AND r.estado IN ('pendiente','aceptada'))"
        : "(d.fecha < CURDATE() OR r.estado IN ('rechazada','cancelada','finalizada'))";

    $sql = "SELECT r.*, d.nombre AS ride_nombre, d.lugar_salida, d.lugar_llegada, d.fecha, d.hora,
                   v.placa, v.marca, v.modelo, v.color
            FROM reservas r
            JOIN rides_data d ON d.id = r.ride_id
       LEFT JOIN vehiculos v ON v.id = d.vehiculo_id
           WHERE r.pasajero_id=? AND $cond
        ORDER BY d.fecha DESC, d.hora DESC, r.creado_en DESC";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $pasajeroId);
    $stmt->execute();
    return $stmt->get_result();
}

/** Listado reservas del CHOFER (activas/pasadas) */
function listarReservasChofer($choferId, $scope = 'activas') {
    global $mysqli;
    $cond = ($scope === 'activas')
        ? "(d.fecha >= CURDATE() AND r.estado IN ('pendiente','aceptada'))"
        : "(d.fecha < CURDATE() OR r.estado IN ('rechazada','cancelada','finalizada'))";

    $sql = "SELECT r.*, d.nombre AS ride_nombre, d.lugar_salida, d.lugar_llegada, d.fecha, d.hora,
                   u.nombre AS pasajero_nombre, u.apellido AS pasajero_apellido, u.correo AS pasajero_correo,
                   v.placa, v.marca, v.modelo, v.color
            FROM reservas r
            JOIN rides_data d ON d.id = r.ride_id
            JOIN usuarios u ON u.id = r.pasajero_id
       LEFT JOIN vehiculos v ON v.id = d.vehiculo_id
           WHERE d.usuario_id=? AND $cond
        ORDER BY d.fecha ASC, d.hora ASC, r.creado_en ASC";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $choferId);
    $stmt->execute();
    return $stmt->get_result();
}

/* =======================
   PERFIL DE USUARIO
   ======================= */

function emailDisponible($correo, $excluirUsuarioId = null) {
    global $mysqli;
    if ($excluirUsuarioId) {
        $sql = "SELECT id FROM usuarios WHERE correo=? AND id<>? LIMIT 1";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("si", $correo, $excluirUsuarioId);
    } else {
        $sql = "SELECT id FROM usuarios WHERE correo=? LIMIT 1";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("s", $correo);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->num_rows === 0;
}

/* Sube/actualiza foto de USUARIO (ruta absoluta Windows) y devuelve ruta relativa para BD */
function subirFotoUsuario($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return null;
    if (!is_uploaded_file($file['tmp_name'])) return null;

    // 3MB
    if ($file['size'] > 3 * 1024 * 1024) return null;

    // Detectar extensión segura
    $ext = null;
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        if     ($mime === 'image/jpeg') $ext = 'jpg';
        elseif ($mime === 'image/png')  $ext = 'png';
        elseif ($mime === 'image/webp') $ext = 'webp';
        elseif ($mime === 'image/gif')  $ext = 'gif';
    }
    if ($ext === null) {
        $extGuess = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($extGuess, ['jpg','jpeg','png','webp','gif'])) {
            $ext = ($extGuess === 'jpeg') ? 'jpg' : $extGuess;
        }
    }
    if (!$ext) return null;

    // Carpeta fija
    $absDir = 'C:\\ISW-613\\httpdocs\\Primer_proyecto\\uploads\\fotos_usuarios';
    if (!is_dir($absDir)) { @mkdir($absDir, 0775, true); }

    $nombre = 'u_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    $absPath = $absDir . DIRECTORY_SEPARATOR . $nombre;

    if (!move_uploaded_file($file['tmp_name'], $absPath)) return null;

    return 'uploads/fotos_usuarios/' . $nombre; // ruta relativa para BD
}

/* Actualiza datos del usuario (sin password). Devuelve [ok,msg] */
function actualizarPerfilUsuario($id, $nombre, $apellido, $cedula, $fecha_nac, $correo, $telefono, $rutaFotoNueva = null) {
    global $mysqli;

    // Validar correo único
    if (!emailDisponible($correo, $id)) {
        return [false, "El correo ya está en uso."];
    }

    if ($rutaFotoNueva) {
        $sql = "UPDATE usuarios
                   SET nombre=?, apellido=?, cedula=?, fecha_nacimiento=?, correo=?, telefono=?, foto=?
                 WHERE id=?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sssssssi", $nombre, $apellido, $cedula, $fecha_nac, $correo, $telefono, $rutaFotoNueva, $id);
    } else {
        $sql = "UPDATE usuarios
                   SET nombre=?, apellido=?, cedula=?, fecha_nacimiento=?, correo=?, telefono=?
                 WHERE id=?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ssssssi", $nombre, $apellido, $cedula, $fecha_nac, $correo, $telefono, $id);
    }
    if (!$stmt->execute()) {
        return [false, "No se pudo actualizar el perfil."];
    }
    return [true, "Perfil actualizado correctamente."];
}

/* Cambiar contraseña (requiere la actual). Devuelve [ok,msg] */
function cambiarPasswordUsuario($id, $passActual, $passNueva) {
    global $mysqli;
    $u = obtenerUsuarioPorId($id);
    if (!$u) return [false, "Usuario no encontrado."];

    if (!password_verify($passActual, $u['password_hash'])) {
        return [false, "La contraseña actual no es correcta."];
    }
    $hash = password_hash($passNueva, PASSWORD_DEFAULT);
    $stmt = $mysqli->prepare("UPDATE usuarios SET password_hash=? WHERE id=?");
    $stmt->bind_param("si", $hash, $id);
    if (!$stmt->execute()) return [false, "No se pudo cambiar la contraseña."];
    return [true, "Contraseña actualizada."];
}

function base_url_public() {
    // Detecta http/https y host actual
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST']; // puede ser 127.0.0.1, 192.168.x.x, dominio, etc.
    // Ajusta el nombre de tu carpeta si difiere:
    return $scheme . '://' . $host . '/Primer_proyecto/public';
}
