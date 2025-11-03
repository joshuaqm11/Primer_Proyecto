// Realizado por Joshua Quesada y Fabio Oconitrillo
<?php

require_once '../inc/funciones.php';        // Helpers generales
require_once '../inc/mailer.php';           // Envío de correos (registro, notificaciones, etc.)

/* ===========================================
   FUNCIONES GLOBALES DEL SISTEMA (MySQLi)
   Compatible con tu BD rides y tus columnas
   =========================================== */

if (session_status() === PHP_SESSION_NONE) { session_start(); }  // Asegura sesión iniciada
require_once __DIR__ . '/conexion.php';                          // Carga $mysqli (conexión a BD)

/* ===== Helpers de sesión/roles ===== */
// Verificadores de sesión y tipo de usuario (para control de acceso en páginas)
function isLoggedIn()   { return isset($_SESSION['user']); }
function esAdmin()      { return isset($_SESSION['user']) && $_SESSION['user']['tipo'] === 'admin'; }
function esChofer()     { return isset($_SESSION['user']) && $_SESSION['user']['tipo'] === 'chofer'; }
function esPasajero()   { return isset($_SESSION['user']) && $_SESSION['user']['tipo'] === 'pasajero'; }

// Redirección si no hay sesión
function requireLogin($redirect = '../public/index.php') {
    if (!isLoggedIn()) { header("Location: $redirect"); exit; }
}

/* ========= USUARIOS / AUTENTICACIÓN ========= */

// Obtiene registro de usuarios por correo (para login/validaciones)
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

// Obtiene registro de usuarios por id
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

/** Verifica credenciales -> 
 *  - Revisa existencia, estado y contraseña
 */
function verificarCredenciales($correo, $password_plano) {
    $u = obtenerUsuarioPorEmail($correo);
    if (!$u) return [false, null, "Usuario no encontrado."];

    // estado válido (permite 'activo' y 'pendiente')
    if (!in_array($u['estado'], ['activo','pendiente'])) {
        return [false, null, "Usuario inactivo."];
    }

    // Verificación de hash con password_verify
    if (!password_verify($password_plano, $u['password_hash'])) {
        return [false, null, "Contraseña incorrecta."];
    }

    return [true, $u, ""];
}

/** Guarda datos básicos en sesión (minimiza exposición de información sensible) */
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

/** Cierra sesión por completo (datos + cookie + session_destroy) */
function cerrarSesion() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time()-42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
    }
    session_destroy();
}

/** Refresca los datos del usuario en sesión desde BD */
function refrescarUsuarioEnSesion() {
    if (!isLoggedIn()) return;
    $u = obtenerUsuarioPorId($_SESSION['user']['id']);
    if ($u) iniciarSesionUsuario($u);
}

/* ========= SUBIDAS ========= */

/** Sube foto de vehículo y devuelve ruta relativa (o null si falla)
 *  - Valida extensión
 *  - Crea carpeta si no existe
 *  - Genera nombre aleatorio
 */
function subirFotoVehiculo($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return null;
    $dir = __DIR__ . '/../uploads/vehiculos';
    if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) return null;
    $nombre = 'veh_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir . '/' . $nombre;
    if (!move_uploaded_file($file['tmp_name'], $dest)) return null;
    return 'uploads/vehiculos/' . $nombre; // ruta relativa para HTML/BD
}

/* ========= VEHÍCULOS  =========
   Tabla: vehiculos (id, usuario_id, placa, color, marca, modelo, anio YEAR, capacidad, foto)
*/

// Inserta vehículo del usuario
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

// Actualiza datos del vehículo (con o sin nueva foto)
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
                   SET placa=?, color=?, marca=?, modelo=?, anio=?, capacidad?
                 WHERE id=? AND usuario_id=?";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) return false;
        // tipos: s s s s i i i i
        $stmt->bind_param("ssssiiii", $placa, $color, $marca, $modelo, $anio, $capacidad, $vehiculoId, $usuarioId);
    }
    return $stmt->execute();
}

// Elimina vehículo del usuario
function eliminarVehiculo($vehiculoId, $usuarioId) {
    global $mysqli;
    $stmt = $mysqli->prepare("DELETE FROM vehiculos WHERE id=? AND usuario_id?");
    if (!$stmt) return false;
    $stmt->bind_param("ii", $vehiculoId, $usuarioId);
    return $stmt->execute();
}

// Lista vehículos del usuario
function listarVehiculosUsuario($usuarioId) {
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT * FROM vehiculos WHERE usuario_id=? ORDER BY creado_en DESC");
    if (!$stmt) return false;
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    return $stmt->get_result();
}

/* ========= RIDES  =========
   Tabla: rides_data (id, usuario_id, vehiculo_id NULL, nombre, lugar_salida, lugar_llegada, fecha NOT NULL, hora, costo, espacios)
*/

// Crea ride
function crearRide($usuarioId, $vehiculoId, $nombre, $salida, $llegada, $fecha, $hora, $costo, $espacios) {
    global $mysqli;
    $sql = "INSERT INTO rides_data (usuario_id, vehiculo_id, nombre, lugar_salida, lugar_llegada, fecha, hora, costo, espacios)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return false;

    if ($vehiculoId === '' || $vehiculoId === null) { $vehiculoId = null; }

    // Tipado de parámetros
    $stmt->bind_param("iisssssdi", $usuarioId, $vehiculoId, $nombre, $salida, $llegada, $fecha, $hora, $costo, $espacios);
    return $stmt->execute();
}

// Actualiza ride propio
function actualizarRide($rideId, $usuarioId, $vehiculoId, $nombre, $salida, $llegada, $fecha, $hora, $costo, $espacios) {
    global $mysqli;
    $sql = "UPDATE rides_data
               SET vehiculo_id=?, nombre=?, lugar_salida=?, lugar_llegada=?, fecha=?, hora=?, costo=?, espacios=?
             WHERE id=? AND usuario_id=?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return false;

    if ($vehiculoId === '' || $vehiculoId === null) { $vehiculoId = null; }

    // Tipos SIN ESPACIOS: "isssssdiii"
    $stmt->bind_param("isssssdiii",
        $vehiculoId, $nombre, $salida, $llegada, $fecha, $hora, $costo, $espacios, $rideId, $usuarioId
    );
    return $stmt->execute();
}

// Elimina ride propio
function eliminarRide($rideId, $usuarioId) {
    global $mysqli;
    $stmt = $mysqli->prepare("DELETE FROM rides_data WHERE id=? AND usuario_id=?");
    if (!$stmt) return false;
    $stmt->bind_param("ii", $rideId, $usuarioId);
    return $stmt->execute();
}

// Lista rides del usuario con datos del vehículo
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

/* ========= Validar espacios vs capacidad ========= */
// Devuelve capacidad del vehículo si pertenece al usuario (o null si no valido)
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
   =================================================================== */

// Helper rápido para validar tipo pasajero del lado servidor
function esUsuarioPasajero() { return isset($_SESSION['user']) && $_SESSION['user']['tipo'] === 'pasajero'; }

// Obtiene ride por id
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
 */
function crearReserva($rideId, $pasajeroId, $cantidad = 1) {
    if (!esUsuarioPasajero()) return [false, "Solo usuarios pasajeros pueden reservar."];

    global $mysqli;
    if ($cantidad < 1) return [false, "Cantidad inválida."];

    $mysqli->begin_transaction();
    try {
        // Bloquea el ride para control de cupos mientras se procesa
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

        // Inserta reserva en estado pendiente
        $stmt = $mysqli->prepare("INSERT INTO reservas (ride_id, pasajero_id, estado, cantidad) VALUES (?, ?, 'pendiente', ?)");
        $stmt->bind_param("iii", $rideId, $pasajeroId, $cantidad);
        if (!$stmt->execute()) {
            $mysqli->rollback();
            return [false, "No se pudo crear la reserva."];
        }

        // Descuenta cupos del ride inmediatamente
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
        // Bloquea la reserva para evitar carreras
        $stmt = $mysqli->prepare("SELECT r.ride_id, r.pasajero_id, r.estado, r.cantidad 
                                  FROM reservas r
                                  WHERE r.id=? FOR UPDATE");
        $stmt->bind_param("i", $reservaId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if (!$res) { $mysqli->rollback(); return [false,"Reserva no encontrada."]; }

        // Autorización + estado permitido
        if ((int)$res['pasajero_id'] !== (int)$pasajeroId) { $mysqli->rollback(); return [false,"No puedes cancelar esta reserva."]; }
        if (!in_array($res['estado'], ['pendiente','aceptada'])) { $mysqli->rollback(); return [false,"No se puede cancelar en estado ".$res['estado']."."]; }

        // Marca como cancelada
        $stmt = $mysqli->prepare("UPDATE reservas SET estado='cancelada' WHERE id=?");
        $stmt->bind_param("i", $reservaId);
        if (!$stmt->execute()) { $mysqli->rollback(); return [false,"No se pudo cancelar la reserva."]; }

        // Reintegra cupos al ride
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
 * - No modifica cupos (ya se descontaron al crear)
 */
function aceptarReservaPorChofer($reservaId, $choferId) {
    global $mysqli;

    $mysqli->begin_transaction();
    try {
        // Busca la reserva y verifica que el ride sea del chofer
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

        // Actualiza estado
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
        // Busca y bloquea, validando propiedad del ride por el chofer
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

        // Marca como rechazada
        $stmt = $mysqli->prepare("UPDATE reservas SET estado='rechazada' WHERE id=?");
        $stmt->bind_param("i", $reservaId);
        if (!$stmt->execute()) { $mysqli->rollback(); return [false,"No se pudo rechazar la reserva."]; }

        // Reintegra cupos en el ride
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

/** Listado de reservas del PASAJERO
 *  - activas: futuras + estado (pendiente|aceptada)
 *  - pasadas: fecha pasada o estado terminal (rechazada|cancelada|finalizada)
 */
function listarReservasPasajero($pasajeroId, $scope = 'activas') {
    global $mysqli;
    $cond = ($scope === 'activas')
        ? "(d.fecha >= CURDATE() AND r.estado IN ('pendiente','aceptada'))"
        : "(d.fecha < CURDATE() OR r.estado IN ('rechazada','cancelada','finalizada'))";

    $sql = "SELECT r*, d.nombre AS ride_nombre, d.lugar_salida, d.lugar_llegada, d.fecha, d.hora,
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

/** Listado de reservas del CHOFER
 *  - activas: futuras + estado (pendiente|aceptada)
 *  - pasadas: fecha pasada o estado terminal (rechazada|cancelada|finalizada)
 */
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

// Verifica disponibilidad de correo (excluyendo al propio usuario si edita)
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

/** Sube/actualiza foto de usuario a una carpeta fija en Windows
 *  - Valida tamaño (<=3MB), tipo MIME y extensión
 *  - Devuelve ruta relativa para almacenar en BD
 */
function subirFotoUsuario($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return null;
    if (!is_uploaded_file($file['tmp_name'])) return null;

    // Límite 3MB
    if ($file['size'] > 3 * 1024 * 1024) return null;

    // Detectar extensión segura desde MIME
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

    return 'uploads/fotos_usuarios/' . $nombre; 
}

/** Actualiza datos del perfil
 *  - Valida unicidad de correo
 *  - Si hay nueva foto, actualiza campo foto
 */
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
                   SET nombre=?, apellido=?, cedula=?, fecha_nacimiento=?, correo=?, telefono?
                 WHERE id=?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ssssssi", $nombre, $apellido, $cedula, $fecha_nac, $correo, $telefono, $id);
    }
    if (!$stmt->execute()) {
        return [false, "No se pudo actualizar el perfil."];
    }
    return [true, "Perfil actualizado correctamente."];
}

/** Cambiar contraseña (requiere la actual)
 *  - Verifica hash actual
 *  - Guarda nuevo hash por defecto (bcrypt)
 */
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

// Construye base URL hacia /public detectando esquema y host actual
function base_url_public() {
    // Detecta http/https y host actual
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'];
    // Ajusta el nombre de tu carpeta si difiere:
    return $scheme . '://' . $host . '/Primer_proyecto/public';
}
