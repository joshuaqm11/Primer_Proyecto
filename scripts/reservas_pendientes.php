#!/usr/bin/env php
<?php
/**
 * Notifica a choferes sobre reservas "pendiente" con más de X minutos.
 * Uso: php reservas_pendientes.php [minutos]
 * Ej.: php reservas_pendientes.php 30
 *
 * Requisitos:
 *   - inc/conexion.php expone $mysqli (MySQLi conectado)
 *   - inc/mailer.php expone enviarCorreo($paraEmail, $paraNombre, $asunto, $htmlBody, $altBody='')
 */

date_default_timezone_set('America/Costa_Rica');

/* ===== Bootstrap mínimo SIN tocar inc/funciones.php ===== */
require_once __DIR__ . '/../inc/conexion.php';  // -> $mysqli
require_once __DIR__ . '/../inc/mailer.php';    // -> function enviarCorreo(...)


/* ===== Parametro minutos ===== */
$min = 30;
if (isset($argv[1]) && is_numeric($argv[1]) && (int)$argv[1] > 0) {
    $min = (int)$argv[1];
}
echo "[INFO] Minutos de antigüedad: {$min}\n";

/* ===== Consulta reservas pendientes con > X min ===== */
global $mysqli;

$sql = "
SELECT 
    res.id            AS reserva_id,
    res.ride_id       AS ride_id,
    res.cantidad      AS cantidad,
    res.creado_en     AS creado_en,
    r.nombre          AS ride_nombre,
    r.usuario_id      AS chofer_id,
    u.nombre          AS chofer_nombre,
    u.apellido        AS chofer_apellido,
    u.correo          AS chofer_correo
FROM reservas res
JOIN rides_data r ON r.id = res.ride_id
JOIN usuarios u   ON u.id = r.usuario_id
WHERE res.estado = 'pendiente'
  AND res.creado_en <= (NOW() - INTERVAL ? MINUTE)
ORDER BY u.id, res.creado_en ASC
";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    fwrite(STDERR, "[ERROR] SQL prepare: " . $mysqli->error . PHP_EOL);
    exit(1);
}
$stmt->bind_param("i", $min);
$stmt->execute();
$rs = $stmt->get_result();
if (!$rs || $rs->num_rows === 0) {
    echo "[INFO] No hay reservas pendientes con más de {$min} minutos.\n";
    exit(0);
}

/* ===== Agrupar por chofer ===== */
$porChofer = []; // chofer_id => ['correo'=>..., 'nombre'=>..., 'reservas'=>[...]]
while ($row = $rs->fetch_assoc()) {
    $cid = (int)$row['chofer_id'];
    if (!isset($porChofer[$cid])) {
        $porChofer[$cid] = [
            'correo'   => $row['chofer_correo'],
            'nombre'   => trim(($row['chofer_nombre'] ?? '') . ' ' . ($row['chofer_apellido'] ?? '')),
            'reservas' => []
        ];
    }
    $porChofer[$cid]['reservas'][] = [
        'reserva_id'  => (int)$row['reserva_id'],
        'ride_id'     => (int)$row['ride_id'],
        'ride_nombre' => $row['ride_nombre'] ?? '(sin nombre)',
        'cantidad'    => (int)$row['cantidad'],
        'creado_en'   => $row['creado_en'],
    ];
}
$stmt->close();

/* ===== URL del panel (ajústala si usas otro host o carpeta) ===== */
if (!defined('BASE_PUBLIC_URL')) {
    define('BASE_PUBLIC_URL', 'http://localhost/Primer_proyecto/public');
}
$panelReservasUrl = rtrim(BASE_PUBLIC_URL, '/') . '/index.php';

/* ===== Wrapper de envío (usa tu enviarCorreo) ===== */
function _enviar_notificacion($toEmail, $toName, $subject, $html) {
    if (!function_exists('enviarCorreo')) {
        // Fallback mínimo (solo por si acaso)
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Aventones <no-reply@aventones.local>\r\n";
        return @mail($toEmail, $subject, $html, $headers);
    }
    // enviarCorreo($paraEmail, $paraNombre, $asunto, $htmlBody, $altBody='')
    $res = enviarCorreo($toEmail, $toName, $subject, $html, strip_tags($html));
    if (is_array($res)) return (bool)$res[0];
    return (bool)$res;
}

/* ===== Envío por chofer ===== */
$totalChoferes = 0;
$totalReservas = 0;

foreach ($porChofer as $cid => $info) {
    $correo = trim((string)$info['correo']);
    $nombre = $info['nombre'] ?: 'Chofer';
    $lista  = $info['reservas'];

    if ($correo === '' || empty($lista)) {
        continue;
    }

    $totalChoferes++;
    $totalReservas += count($lista);

    $items = '';
    foreach ($lista as $r) {
        $items .= sprintf(
            '<li>Reserva <strong>#%d</strong> (%d cupo%s) del ride <strong>#%d</strong> "%s", creada: <em>%s</em></li>',
            $r['reserva_id'],
            $r['cantidad'],
            $r['cantidad'] === 1 ? '' : 's',
            $r['ride_id'],
            htmlspecialchars($r['ride_nombre'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($r['creado_en'], ENT_QUOTES, 'UTF-8')
        );
    }


        $html = '
        <div style="font-family:Segoe UI,Roboto,Arial,sans-serif">
        <p>Hola <strong>' . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . '</strong>,</p>
        <p>Tienes <strong>' . count($lista) . '</strong> solicitud(es) de reserva en estado <strong>pendiente</strong> con más de <strong>' . $min . ' minutos</strong>:</p>
        <ul>' . $items . '</ul>
        <p>Ingresa a tu panel para aceptarlas o rechazarlas.</p>
        <hr>
        <small>Este es un correo automático de Aventones.</small>
        </div>';


    $ok = _enviar_notificacion($correo, $nombre, "Reservas pendientes (> {$min} min)", $html);
    if ($ok) {
        echo "[OK] Notificado: {$correo} (" . count($lista) . " reservas)\n";
    } else {
        fwrite(STDERR, "[ERROR] No se pudo enviar a {$correo}\n");
    }
}

echo "[HECHO] Choferes notificados: {$totalChoferes} | Reservas listadas: {$totalReservas}\n";

