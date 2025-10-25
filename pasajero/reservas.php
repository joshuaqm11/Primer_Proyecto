<?php
require_once '../inc/funciones.php';
if (!esPasajero()) { 
    header("Location: ../public/index.php"); 
    exit; 
}

global $mysqli;
$user = $_SESSION['user'];

// Cancelar reserva
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $stmt = $mysqli->prepare("UPDATE reservas SET estado='cancelada' WHERE id=? AND pasajero_id=?");
    $stmt->bind_param("ii", $id, $user['id']);
    $stmt->execute();
}

// Consultar reservas del pasajero
$res = $mysqli->query("
    SELECT res.*, r.nombre AS ride_nombre 
    FROM reservas res 
    JOIN rides_data r ON res.ride_id = r.id 
    WHERE res.pasajero_id = {$user['id']}
    ORDER BY res.creado_en DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mis Reservas - Rides</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">🧳 Mis Reservas</h2>
        <a href="../public/logout.php" class="btn btn-outline-danger btn-sm">Cerrar sesión</a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            Reservas Actuales
        </div>
        <div class="card-body">
            <?php if ($res->num_rows > 0): ?>
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Ride</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($r = $res->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['ride_nombre']) ?></td>
                            <td>
                                <?php 
                                $estado = htmlspecialchars($r['estado']);
                                $badgeClass = match($estado) {
                                    'aceptada' => 'bg-success',
                                    'pendiente' => 'bg-warning text-dark',
                                    'cancelada' => 'bg-danger',
                                    default => 'bg-secondary'
                                };
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= ucfirst($estado) ?></span>
                            </td>
                            <td>
                                <?php if (in_array($r['estado'], ['pendiente','aceptada'])): ?>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger">Cancelar</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">Sin acciones</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted">No tienes reservas registradas.</p>
            <?php endif; ?>
        </div>
    </div>

    <a href="../public/search.php" class="btn btn-outline-primary">🔍 Buscar más rides</a>
</div>

</body>
</html>

