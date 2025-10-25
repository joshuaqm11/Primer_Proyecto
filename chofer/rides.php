<?php
require_once '../inc/funciones.php';
if (!esChofer()) { header("Location: ../public/index.php"); exit; }
$user = $_SESSION['user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $salida = $_POST['lugar_salida'];
    $llegada = $_POST['lugar_llegada'];
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $costo = $_POST['costo'];
    $espacios = $_POST['espacios'];

    $stmt = $mysqli->prepare("INSERT INTO rides_data (usuario_id, nombre, lugar_salida, lugar_llegada, fecha, hora, costo, espacios) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssdi", $user['id'], $nombre, $salida, $llegada, $fecha, $hora, $costo, $espacios);
    $stmt->execute();

    $msg = "✅ Ride creado exitosamente.";
}

$res = $mysqli->query("SELECT * FROM rides_data WHERE usuario_id = {$user['id']} ORDER BY fecha DESC, hora DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mis Rides</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
  <div class="card shadow-lg border-0 rounded-4">
    <div class="card-header bg-primary text-white text-center py-3">
      <h3 class="mb-0">🚗 Rides de <?= htmlspecialchars($user['nombre']) ?></h3>
    </div>
    <div class="card-body">

      <?php if (!empty($msg)): ?>
        <div class="alert alert-success text-center"><?= $msg ?></div>
      <?php endif; ?>

      <h5 class="text-secondary mb-3">Crear nuevo ride</h5>
      <form method="post" class="row g-3 mb-4">
        <div class="col-md-6">
          <input name="nombre" class="form-control" placeholder="Nombre del ride" required>
        </div>
        <div class="col-md-6">
          <input name="lugar_salida" class="form-control" placeholder="Lugar de salida" required>
        </div>
        <div class="col-md-6">
          <input name="lugar_llegada" class="form-control" placeholder="Lugar de llegada" required>
        </div>
        <div class="col-md-3">
          <input type="date" name="fecha" class="form-control" required>
        </div>
        <div class="col-md-3">
          <input type="time" name="hora" class="form-control" required>
        </div>
        <div class="col-md-3">
          <input type="number" name="costo" class="form-control" placeholder="Costo (₡)" step="0.01">
        </div>
        <div class="col-md-3">
          <input type="number" name="espacios" class="form-control" placeholder="Espacios" min="1" required>
        </div>
        <div class="col-12 text-end">
          <button class="btn btn-success px-4">➕ Crear Ride</button>
        </div>
      </form>

      <hr>

      <h5 class="text-secondary mb-3">Rides actuales</h5>
      <?php if ($res->num_rows > 0): ?>
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle">
            <thead class="table-primary">
              <tr>
                <th>Nombre</th>
                <th>Salida</th>
                <th>Llegada</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Costo (₡)</th>
                <th>Espacios</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($r = $res->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($r['nombre']) ?></td>
                  <td><?= htmlspecialchars($r['lugar_salida']) ?></td>
                  <td><?= htmlspecialchars($r['lugar_llegada']) ?></td>
                  <td><?= htmlspecialchars($r['fecha']) ?></td>
                  <td><?= htmlspecialchars($r['hora']) ?></td>
                  <td>₡<?= number_format($r['costo'], 2, ',', '.') ?></td>
                  <td><?= htmlspecialchars($r['espacios']) ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="alert alert-warning text-center">Aún no has creado rides.</div>
      <?php endif; ?>

      <div class="text-center mt-4">
        <a href="../public/index.php" class="btn btn-outline-secondary">⬅️ Volver al inicio</a>
      </div>

    </div>
  </div>
</div>

</body>
</html>
