<?php
require_once '../inc/funciones.php';
if (!esChofer()) { header("Location: ../public/index.php"); exit; }
$user = $_SESSION['user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $accion = $_POST['accion'];
    if (in_array($accion, ['aceptada','rechazada'])) {
        $stmt = $mysqli->prepare("UPDATE reservas SET estado=? WHERE id=?");
        $stmt->bind_param("si", $accion, $id);
        $stmt->execute();
    }
}

$res = $mysqli->query("SELECT res.*, r.nombre AS ride_nombre, u.nombre AS pasajero 
                       FROM reservas res 
                       JOIN rides_data r ON res.ride_id = r.id 
                       JOIN usuarios u ON res.pasajero_id = u.id 
                       WHERE r.usuario_id = {$user['id']}
                       ORDER BY res.creado_en DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Reservas Recibidas</title></head>
<body>
<h2>Reservas de mis rides</h2>
<ul>
<?php while($r = $res->fetch_assoc()): ?>
  <li>
    <strong><?=htmlspecialchars($r['ride_nombre'])?></strong> - 
    Pasajero: <?=htmlspecialchars($r['pasajero'])?> - 
    Estado: <?=$r['estado']?>
    <form method="post" style="display:inline;">
      <input type="hidden" name="id" value="<?=$r['id']?>">
      <button name="accion" value="aceptada">Aceptar</button>
      <button name="accion" value="rechazada">Rechazar</button>
    </form>
  </li>
<?php endwhile; ?>
</ul>
</body>
</html>
