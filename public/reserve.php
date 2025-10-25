<?php
require_once '../inc/funciones.php';

if (!esPasajero()) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ride_id = intval($_POST['ride_id']);
    $cantidad = intval($_POST['cantidad']);

    $stmt = $mysqli->prepare("INSERT INTO reservas (ride_id, pasajero_id, cantidad) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $ride_id, $_SESSION['user']['id'], $cantidad);
    if ($stmt->execute()) {
        $msg = "Reserva creada correctamente. Estado: pendiente.";
    } else {
        $msg = "Error al crear la reserva.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Reservar Ride</title></head>
<body>
<h2>Reserva</h2>
<p><?= $msg ?? 'No hay acción realizada.' ?></p>
<p><a href="search.php">Volver a búsqueda</a></p>
</body>
</html>

