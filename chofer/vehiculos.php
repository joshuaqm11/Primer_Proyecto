<?php
require_once '../inc/funciones.php';
if (!esChofer()) { 
    header("Location: ../public/index.php"); 
    exit; 
}

global $mysqli;
$user = $_SESSION['user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $placa = $_POST['placa'];
    $marca = $_POST['marca'];
    $modelo = $_POST['modelo'];
    $anio = $_POST['anio'];
    $color = $_POST['color'];
    $capacidad = $_POST['capacidad'];

    $stmt = $mysqli->prepare("INSERT INTO vehiculos (usuario_id, placa, marca, modelo, anio, color, capacidad) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssi", $user['id'], $placa, $marca, $modelo, $anio, $color, $capacidad);
    $stmt->execute();
}

$res = $mysqli->query("SELECT * FROM vehiculos WHERE usuario_id = {$user['id']}");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mis Vehículos - Rides</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">🚗 Vehículos de <?= htmlspecialchars($user['nombre']) ?></h2>
        <a href="../public/logout.php" class="btn btn-outline-danger btn-sm">Cerrar sesión</a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            Agregar Vehículo
        </div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Placa</label>
                    <input name="placa" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Marca</label>
                    <input name="marca" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Modelo</label>
                    <input name="modelo" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Año</label>
                    <input name="anio" type="number" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Color</label>
                    <input name="color" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Capacidad</label>
                    <input name="capacidad" type="number" class="form-control" required>
                </div>
                <div class="col-12">
                    <button class="btn btn-success mt-3">Agregar Vehículo</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-secondary text-white">
            Vehículos Registrados
        </div>
        <div class="card-body">
            <?php if ($res->num_rows > 0): ?>
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Placa</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Año</th>
                        <th>Color</th>
                        <th>Capacidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($v = $res->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($v['placa']) ?></td>
                        <td><?= htmlspecialchars($v['marca']) ?></td>
                        <td><?= htmlspecialchars($v['modelo']) ?></td>
                        <td><?= htmlspecialchars($v['anio']) ?></td>
                        <td><?= htmlspecialchars($v['color']) ?></td>
                        <td><?= htmlspecialchars($v['capacidad']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="text-muted">No tienes vehículos registrados.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>

