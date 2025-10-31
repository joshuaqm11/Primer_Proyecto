<?php
require_once __DIR__ . '/inc/conexion.php';
$limite = date('Y-m-d H:i:s', time() - 1800);
$q = $mysqli->prepare("SELECT r.usuario_id, COUNT(*) AS total FROM reservas res JOIN rides_data r ON res.ride_id=r.id WHERE res.estado='pendiente' AND res.creado_en <= ? GROUP BY r.usuario_id");
$q->bind_param("s", $limite);
$q->execute();
$r = $q->get_result();

while($row = $r->fetch_assoc()){
  $u = $mysqli->query("SELECT correo FROM usuarios WHERE id={$row['usuario_id']}")->fetch_assoc();
  $correo = $u['correo'];
  @mail($correo, "Aviso de reservas pendientes", "Tiene reservas pendientes en el sistema Rides.", "From: no-reply@rides.local");
  echo "Notificado chofer {$row['usuario_id']} - {$correo}\n";
}
