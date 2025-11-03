// Realizado por Joshua Quesada y Fabio Oconitrillo
<?php
require_once __DIR__ . '/inc/conexion.php'; // Conexión a la base de datos

// Define el límite de antigüedad (30 min = 1800 seg) para reservas pendientes
$limite = date('Y-m-d H:i:s', time() - 1800);

// Consulta: obtiene choferes con reservas "pendientes" más antiguas que el límite
$q = $mysqli->prepare("SELECT r.usuario_id, COUNT(*) AS total 
                       FROM reservas res 
                       JOIN rides_data r ON res.ride_id=r.id 
                      WHERE res.estado='pendiente' AND res.creado_en <= ? 
                   GROUP BY r.usuario_id");
$q->bind_param("s", $limite);
$q->execute();
$r = $q->get_result();

// Itera por cada chofer con reservas pendientes y notifica por correo
while($row = $r->fetch_assoc()){
  // Obtiene correo del chofer
  $u = $mysqli->query("SELECT correo FROM usuarios WHERE id={$row['usuario_id']}")->fetch_assoc();
  $correo = $u['correo'];

  // Envía correo nativo PHP (mail)
  @mail($correo,
        "Aviso de reservas pendientes",
        "Tiene reservas pendientes en el sistema Rides.",
        "From: no-reply@rides.local");

  // Log en consola (CLI)
  echo "Notificado chofer {$row['usuario_id']} - {$correo}\n";
}
