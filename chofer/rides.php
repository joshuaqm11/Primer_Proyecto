<?php
require_once '../inc/funciones.php';
if (!esChofer()) { header("Location: ../public/index.php"); exit; }
global $mysqli;

$user = $_SESSION['user'];
$msg = $err = null;

// (opcional) fija zona horaria del proyecto
if (!ini_get('date.timezone')) {
    date_default_timezone_set('America/Costa_Rica');
}

$vehiculos = listarVehiculosUsuario($user['id']);

/** CREATE / UPDATE / DELETE */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? 'crear';

    if ($accion === 'crear') {
        $vehiculoId = ($_POST['vehiculo_id'] === '' ? null : intval($_POST['vehiculo_id']));
        $nombre = trim($_POST['nombre']);
        $salida = trim($_POST['lugar_salida']);
        $llegada = trim($_POST['lugar_llegada']);
        $fecha = trim($_POST['fecha']);
        $hora = trim($_POST['hora']);
        $costo = floatval($_POST['costo']);
        $espacios = intval($_POST['espacios']);

        // ✅ Validar fecha/hora futura
        $dt = DateTime::createFromFormat('Y-m-d H:i', "$fecha $hora");
        $ahora = new DateTime();
        if (!$dt) {
            $err = "Fecha u hora inválida. Usa un formato correcto.";
        } elseif ($dt <= $ahora) {
            $err = "No puedes crear un ride en una fecha u hora pasada.";
        }

        // ✅ Validar capacidad efectiva (capacidad - 1)
        if (!$err) {
            if (empty($vehiculoId)) {
                $err = "Debes seleccionar un vehículo para crear el ride.";
            } else {
                $cap = capacidadVehiculo($vehiculoId, $user['id']); // capacidad total del vehículo
                if ($cap === null) {
                    $err = "Vehículo inválido o no pertenece al usuario.";
                } else {
                    $maxEspacios = max(0, (int)$cap - 1); // una plaza es del conductor
                    if ($maxEspacios < 1) {
                        $err = "El vehículo no tiene plazas disponibles para pasajeros.";
                    } elseif ($espacios > $maxEspacios) {
                        $err = "Los espacios ($espacios) exceden el máximo permitido ($maxEspacios). ";
                             
                    }
                }
            }
        }

        if (!$err) {
            if (crearRide($user['id'], $vehiculoId, $nombre, $salida, $llegada, $fecha, $hora, $costo, $espacios)) {
                $msg = "✅ Ride creado";
            } else {
                $err = "❌ No se pudo crear el ride.";
            }
        }
    }

    if ($accion === 'editar') {
        $id = intval($_POST['id']);
        $vehiculoId = ($_POST['vehiculo_id'] === '' ? null : intval($_POST['vehiculo_id']));
        $nombre = trim($_POST['nombre']);
        $salida = trim($_POST['lugar_salida']);
        $llegada = trim($_POST['lugar_llegada']);
        $fecha = trim($_POST['fecha']);
        $hora = trim($_POST['hora']);
        $costo = floatval($_POST['costo']);
        $espacios = intval($_POST['espacios']);

        // ✅ Validar fecha/hora futura
        $dt = DateTime::createFromFormat('Y-m-d H:i', "$fecha $hora");
        $ahora = new DateTime();
        if (!$dt) {
            $err = "Fecha u hora inválida. Usa un formato correcto.";
        } elseif ($dt <= $ahora) {
            $err = "No puedes guardar un ride con fecha u hora pasada. ";
        }

        // ✅ Validar capacidad efectiva (capacidad - 1)
        if (!$err) {
            if (empty($vehiculoId)) {
                $err = "Debes seleccionar un vehículo para editar el ride.";
            } else {
                $cap = capacidadVehiculo($vehiculoId, $user['id']);
                if ($cap === null) {
                    $err = "Vehículo inválido o no pertenece al usuario.";
                } else {
                    $maxEspacios = max(0, (int)$cap - 1);
                    if ($maxEspacios < 1) {
                        $err = "El vehículo no tiene plazas disponibles para pasajeros.";
                    } elseif ($espacios > $maxEspacios) {
                        $err = "Los espacios ($espacios) exceden el máximo permitido ($maxEspacios). "
                             . "Regla: capacidad del vehículo ($cap) menos 1 plaza del conductor.";
                    }
                }
            }
        }

        if (!$err) {
            if (actualizarRide($id, $user['id'], $vehiculoId, $nombre, $salida, $llegada, $fecha, $hora, $costo, $espacios)) {
                $msg = "✅ Ride actualizado";
            } else {
                $err = "❌ No se pudo actualizar el ride.";
            }
        }
    }

    if ($accion === 'eliminar') {
        $id = intval($_POST['id']);
        if (eliminarRide($id, $user['id'])) {
            $msg = "🗑️ Ride eliminado";
        } else {
            $err = "❌ No se pudo eliminar el ride.";
        }
    }
}

$lista = listarRidesUsuario($user['id']);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Mis Rides</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <h1 class="mb-3">🚌 Mis Rides</h1>

  <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <div class="card mb-4">
    <div class="card-header fw-bold">Crear Ride</div>
    <div class="card-body">
      <form method="post" class="row g-3">
        <input type="hidden" name="accion" value="crear">
        <div class="col-md-4">
          <label class="form-label">Nombre</label>
          <input name="nombre" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Lugar de salida</label>
          <input name="lugar_salida" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Lugar de llegada</label>
          <input name="lugar_llegada" class="form-control" required>
        </div>

        <div class="col-md-3">
          <label class="form-label">Fecha</label>
          <input name="fecha" type="date" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Hora</label>
          <input name="hora" type="time" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Costo por espacio</label>
          <input name="costo" type="number" step="0.01" min="0" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Espacios</label>
          <input name="espacios" type="number" min="1" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Vehículo</label>
          <select name="vehiculo_id" class="form-select" required>
            <option value="">— Selecciona un vehículo —</option>
            <?php
            if ($vehiculos) {
              $vehiculos->data_seek(0);
              while($v = $vehiculos->fetch_assoc()): ?>
                <option value="<?= (int)$v['id'] ?>">
                  <?= htmlspecialchars($v['placa'].' - '.$v['marca'].' '.$v['modelo'].' ('.$v['color'].')') ?>
                </option>
              <?php endwhile;
            } ?>
          </select>
        </div>

        <div class="col-12">
          <button class="btn btn-primary">Guardar Ride</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header fw-bold">Listado</div>
    <div class="card-body">
      <?php if ($lista && $lista->num_rows): ?>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>Nombre</th><th>Trayecto</th><th>Fecha</th><th>Hora</th><th>Costo</th><th>Espacios</th><th>Vehículo</th><th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php while($r = $lista->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($r['nombre']) ?></td>
              <td><?= htmlspecialchars($r['lugar_salida'].' → '.$r['lugar_llegada']) ?></td>
              <td><?= htmlspecialchars($r['fecha']) ?></td>
              <td><?= htmlspecialchars(substr($r['hora'],0,5)) ?></td>
              <td>₡ <?= number_format((float)$r['costo'], 2) ?></td>
              <td><?= (int)$r['espacios'] ?></td>
              <td>
                <?= $r['vehiculo_id']
                    ? htmlspecialchars(($r['placa'] ?? '¿?').' ('.($r['marca'] ?? '').' '.($r['modelo'] ?? '').')')
                    : '—' ?>
              </td>
              <td class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#edit<?= $r['id'] ?>">Editar</button>
                <form method="post" onsubmit="return confirm('¿Eliminar ride?');" class="d-inline">
                  <input type="hidden" name="accion" value="eliminar">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                </form>
              </td>
            </tr>
            <tr class="collapse" id="edit<?= $r['id'] ?>">
              <td colspan="8">
                <form method="post" class="row g-3">
                  <input type="hidden" name="accion" value="editar">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <div class="col-md-4">
                    <label class="form-label">Nombre</label>
                    <input name="nombre" class="form-control" value="<?= htmlspecialchars($r['nombre']) ?>" required>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Salida</label>
                    <input name="lugar_salida" class="form-control" value="<?= htmlspecialchars($r['lugar_salida']) ?>" required>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Llegada</label>
                    <input name="lugar_llegada" class="form-control" value="<?= htmlspecialchars($r['lugar_llegada']) ?>" required>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Fecha</label>
                    <input name="fecha" type="date" class="form-control" value="<?= htmlspecialchars($r['fecha']) ?>" required>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Hora</label>
                    <input name="hora" type="time" class="form-control" value="<?= htmlspecialchars(substr($r['hora'],0,5)) ?>" required>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Costo</label>
                    <input name="costo" type="number" step="0.01" min="0" class="form-control" value="<?= htmlspecialchars($r['costo']) ?>" required>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Espacios</label>
                    <input name="espacios" type="number" min="1" class="form-control" value="<?= (int)$r['espacios'] ?>" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Vehículo</label>
                    <select name="vehiculo_id" class="form-select" required>
                      <option value="">— Selecciona un vehículo —</option>
                      <?php
                      if ($vehiculos) {
                        $vehiculos->data_seek(0);
                        while($v = $vehiculos->fetch_assoc()):
                          $sel = ((int)($r['vehiculo_id'] ?? 0) === (int)$v['id']) ? 'selected' : '';
                      ?>
                        <option value="<?= (int)$v['id'] ?>" <?= $sel ?>>
                          <?= htmlspecialchars($v['placa'].' - '.$v['marca'].' '.$v['modelo'].' ('.$v['color'].')') ?>
                        </option>
                      <?php endwhile; } ?>
                    </select>
                  </div>
                  <div class="col-12">
                    <button class="btn btn-success">Guardar Cambios</button>
                  </div>
                </form>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <div class="alert alert-warning">Aún no has creado rides.</div>
      <?php endif; ?>

      <div class="mt-3">
        <a class="btn btn-outline-secondary" href="../chofer/dashboard.php">⬅️ Volver</a>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
