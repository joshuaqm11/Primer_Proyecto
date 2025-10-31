<?php
require_once '../inc/funciones.php';
if (!esPasajero()) { header("Location: ../public/index.php"); exit; }

$user = $_SESSION['user'];
$msg = $err = null;

/* === Crear reserva si viene desde search.php === */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_reserva'])) {
    $rideId  = (int)($_POST['ride_id'] ?? 0);
    $cantidad = (int)($_POST['cantidad'] ?? 1);
    [$ok, $msj] = crearReserva($rideId, $user['id'], $cantidad);
    if ($ok) $msg = $msj; else $err = $msj;
}

/* === Cancelar reserva (pendiente o aceptada) === */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion']==='cancelar') {
    $reservaId = (int)($_POST['reserva_id'] ?? 0);
    [$ok, $msj] = cancelarReservaPorPasajero($reservaId, $user['id']);
    if ($ok) $msg = $msj; else $err = $msj;
}

$activas = listarReservasPasajero($user['id'], 'activas');
$pasadas = listarReservasPasajero($user['id'], 'pasadas');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Mis Reservas (Pasajero)</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <h1 class="mb-3">🧾 Mis Reservas</h1>

  <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <div class="card mb-4">
    <div class="card-header fw-bold">Reservas activas</div>
    <div class="card-body">
      <?php if ($activas && $activas->num_rows): ?>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>Ride</th><th>Trayecto</th><th>Fecha</th><th>Hora</th><th>Cant.</th><th>Estado</th><th>Vehículo</th><th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php while($r = $activas->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($r['ride_nombre']) ?></td>
                <td><?= htmlspecialchars($r['lugar_salida'].' → '.$r['lugar_llegada']) ?></td>
                <td><?= htmlspecialchars($r['fecha']) ?></td>
                <td><?= htmlspecialchars(substr($r['hora'],0,5)) ?></td>
                <td><?= (int)$r['cantidad'] ?></td>
                <td><span class="badge bg-info text-dark"><?= htmlspecialchars($r['estado']) ?></span></td>
                <td><?= $r['placa'] ? htmlspecialchars($r['placa'].' ('.$r['marca'].' '.$r['modelo'].')') : '—' ?></td>
                <td>
                  <?php if (in_array($r['estado'], ['pendiente','aceptada'])): ?>
                    <form method="post" onsubmit="return confirm('¿Cancelar esta reserva?');" class="d-inline">
                      <input type="hidden" name="accion" value="cancelar">
                      <input type="hidden" name="reserva_id" value="<?= (int)$r['id'] ?>">
                      <button class="btn btn-sm btn-outline-danger">Cancelar</button>
                    </form>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <div class="alert alert-secondary">No tienes reservas activas.</div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header fw-bold">Reservas pasadas</div>
    <div class="card-body">
      <?php if ($pasadas && $pasadas->num_rows): ?>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>Ride</th><th>Trayecto</th><th>Fecha</th><th>Hora</th><th>Cant.</th><th>Estado</th><th>Vehículo</th>
            </tr>
          </thead>
          <tbody>
            <?php while($r = $pasadas->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($r['ride_nombre']) ?></td>
                <td><?= htmlspecialchars($r['lugar_salida'].' → '.$r['lugar_llegada']) ?></td>
                <td><?= htmlspecialchars($r['fecha']) ?></td>
                <td><?= htmlspecialchars(substr($r['hora'],0,5)) ?></td>
                <td><?= (int)$r['cantidad'] ?></td>
                <td><span class="badge bg-secondary"><?= htmlspecialchars($r['estado']) ?></span></td>
                <td><?= $r['placa'] ? htmlspecialchars($r['placa'].' ('.$r['marca'].' '.$r['modelo'].')') : '—' ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <div class="alert alert-secondary">No hay reservas pasadas.</div>
      <?php endif; ?>
    </div>
  </div>

  <div class="mt-3">
    <a class="btn btn-outline-secondary" href="../pasajero/dashboard.php">⬅️ Volver al Panel</a>
  </div>
</div>
</body>
</html>
