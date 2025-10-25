<?php
require_once '../inc/funciones.php';

$origen = $_GET['origen'] ?? '';
$destino = $_GET['destino'] ?? '';
$order = $_GET['order'] ?? 'fecha_desc';

$query = "SELECT r.*, u.nombre AS chofer FROM rides_data r 
          JOIN usuarios u ON r.usuario_id = u.id 
          WHERE r.fecha >= CURDATE()";
$params = [];
$types = "";

if ($origen !== '') {
    $query .= " AND r.lugar_salida LIKE ?";
    $params[] = "%$origen%";
    $types .= "s";
}
if ($destino !== '') {
    $query .= " AND r.lugar_llegada LIKE ?";
    $params[] = "%$destino%";
    $types .= "s";
}

$query .= ($order === 'fecha_asc') 
  ? " ORDER BY r.fecha ASC, r.hora ASC" 
  : " ORDER BY r.fecha DESC, r.hora DESC";

$stmt = $mysqli->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Buscar Rides Disponibles</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
  <div class="card shadow-lg border-0 rounded-4">
    <div class="card-header bg-primary text-white text-center py-3">
      <h3 class="mb-0">🚗 Buscar Rides Disponibles</h3>
    </div>
    <div class="card-body">
      <form method="get" class="row g-3 mb-4">
        <div class="col-md-4">
          <input name="origen" class="form-control" placeholder="Lugar de salida" value="<?= htmlspecialchars($origen) ?>">
        </div>
        <div class="col-md-4">
          <input name="destino" class="form-control" placeholder="Lugar de llegada" value="<?= htmlspecialchars($destino) ?>">
        </div>
        <div class="col-md-3">
          <select name="order" class="form-select">
            <option value="fecha_desc" <?= ($order === 'fecha_desc' ? 'selected' : '') ?>>Más recientes</option>
            <option value="fecha_asc" <?= ($order === 'fecha_asc' ? 'selected' : '') ?>>Más antiguos</option>
          </select>
        </div>
        <div class="col-md-1 text-end">
          <button class="btn btn-primary w-100">Buscar</button>
        </div>
      </form>

      <?php if ($result->num_rows > 0): ?>
        <?php while($r = $result->fetch_assoc()): ?>
          <div class="card mb-3 border-0 shadow-sm">
            <div class="card-body">
              <h5 class="card-title text-primary"><?= htmlspecialchars($r['lugar_salida']) ?> → <?= htmlspecialchars($r['lugar_llegada']) ?></h5>
              <p class="card-text mb-1">
                <strong>Fecha:</strong> <?= $r['fecha'] ?> | <strong>Hora:</strong> <?= $r['hora'] ?><br>
                <strong>Costo:</strong> $<?= $r['costo'] ?> | <strong>Espacios:</strong> <?= $r['espacios'] ?><br>
                <strong>Chofer:</strong> <?= htmlspecialchars($r['chofer']) ?>
              </p>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="alert alert-warning text-center">No se encontraron rides disponibles.</div>
      <?php endif; ?>

      <div class="text-center mt-4">
        <a href="index.php" class="btn btn-outline-secondary">⬅️ Volver al inicio</a>
      </div>
    </div>
  </div>
</div>

</body>
</html>

