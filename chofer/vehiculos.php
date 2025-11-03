// Realizado por Joshua Quesada y Fabio Oconitrillo
<?php
require_once '../inc/funciones.php'; // Helpers globales
if (!esChofer()) { header("Location: ../public/index.php"); exit; } // Guardia: solo chofer
global $mysqli; // Conexión global

$user = $_SESSION['user']; // Usuario autenticado
$msg = $err = null;        // Mensajes de feedback para la UI

/** ================================
 *  CREATE / UPDATE / DELETE (CRUD)
 *  Manejo de formularios por POST
 *  ================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? 'crear'; // crear|editar|eliminar

    if ($accion === 'crear') {
        // Lectura/saneo básico de campos del vehículo
        $placa = trim($_POST['placa']);
        $marca = trim($_POST['marca']);
        $modelo = trim($_POST['modelo']);
        $anio = intval($_POST['anio']);
        $color = trim($_POST['color']);
        $cap = intval($_POST['capacidad']);
        $fotoPath = subirFotoVehiculo($_FILES['foto'] ?? null); // Subida/almacenamiento de foto 

        // Inserta vehículo asociado al usuario
        if (crearVehiculo($user['id'], $placa, $marca, $modelo, $anio, $color, $cap, $fotoPath)) {
            $msg = "✅ Vehículo agregado";
        } else {
            $err = "❌ No se pudo agregar el vehículo (¿placa duplicada?).";
        }
    }

    if ($accion === 'editar') {
        // Actualización de datos del vehículo propio
        $id = intval($_POST['id']);
        $placa = trim($_POST['placa']);
        $marca = trim($_POST['marca']);
        $modelo = trim($_POST['modelo']);
        $anio = intval($_POST['anio']);
        $color = trim($_POST['color']);
        $cap = intval($_POST['capacidad']);
        $fotoPath = subirFotoVehiculo($_FILES['foto'] ?? null); // Reemplazo de foto si se envía

        if (actualizarVehiculo($id, $user['id'], $placa, $marca, $modelo, $anio, $color, $cap, $fotoPath)) {
            $msg = "✅ Vehículo actualizado";
        } else {
            $err = "❌ No se pudo actualizar el vehículo.";
        }
    }

    if ($accion === 'eliminar') {
        // Eliminación de vehículo propio (según política de la función: lógica/física)
        $id = intval($_POST['id']);
        if (eliminarVehiculo($id, $user['id'])) {
            $msg = "🗑️ Vehículo eliminado";
        } else {
            $err = "❌ No se pudo eliminar el vehículo.";
        }
    }
}

// Consulta listado de vehículos del chofer para la tabla
$vehiculos = listarVehiculosUsuario($user['id']);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Mis Vehículos</title>
  <!-- Bootstrap CSS para estilos y layout responsivo -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <h1 class="mb-3">🚗 Mis Vehículos</h1>

  <!-- Mensajes de operación (éxito/error) con escape para prevenir XSS -->
  <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <!-- ================== FORMULARIO: AGREGAR VEHÍCULO ================== -->
  <div class="card mb-4">
    <div class="card-header fw-bold">Agregar Vehículo</div>
    <div class="card-body">
      <form method="post" enctype="multipart/form-data" class="row g-3">
        <input type="hidden" name="accion" value="crear">
        <div class="col-md-3">
          <label class="form-label">Placa</label>
          <input name="placa" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Marca</label>
          <input name="marca" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Modelo</label>
          <input name="modelo" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Año</label>
          <input name="anio" type="number" min="1980" max="2100" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Color</label>
          <input name="color" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Capacidad de Asientos</label>
          <input name="capacidad" type="number" min="1" max="99" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Fotografía (opcional)</label>
          <input name="foto" type="file" accept="image/*" class="form-control">
        </div>
        <div class="col-12">
          <button class="btn btn-primary">Guardar Vehículo</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ================== LISTADO + EDICIÓN INLINE ================== -->
  <div class="card">
    <div class="card-header fw-bold">Listado</div>
    <div class="card-body">
      <?php if ($vehiculos && $vehiculos->num_rows): ?>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>Foto</th><th>Placa</th><th>Marca</th><th>Modelo</th><th>Año</th><th>Color</th><th>Capacidad</th><th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php while($v = $vehiculos->fetch_assoc()): ?>
            <tr>
              <!-- Muestra miniatura si hay ruta de foto -->
              <td><?php if ($v['foto']): ?><img src="../<?= htmlspecialchars($v['foto']) ?>" style="height:48px;border-radius:6px"><?php endif; ?></td>
              <td><?= htmlspecialchars($v['placa']) ?></td>
              <td><?= htmlspecialchars($v['marca']) ?></td>
              <td><?= htmlspecialchars($v['modelo']) ?></td>
              <td><?= (int)$v['anio'] ?></td>
              <td><?= htmlspecialchars($v['color']) ?></td>
              <td><?= (int)$v['capacidad'] ?></td>
              <td class="d-flex gap-2">
                <!-- Toggle para formulario de edición -->
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#edit<?= $v['id'] ?>">Editar</button>
                <!-- Eliminación con confirmación del navegador -->
                <form method="post" onsubmit="return confirm('¿Eliminar vehículo?');" class="d-inline">
                  <input type="hidden" name="accion" value="eliminar">
                  <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                </form>
              </td>
            </tr>
            <!-- Formulario de edición inline -->
            <tr class="collapse" id="edit<?= $v['id'] ?>">
              <td colspan="8">
                <form method="post" enctype="multipart/form-data" class="row g-3">
                  <input type="hidden" name="accion" value="editar">
                  <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                  <div class="col-md-3">
                    <label class="form-label">Placa</label>
                    <input name="placa" class="form-control" value="<?= htmlspecialchars($v['placa']) ?>" required>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Marca</label>
                    <input name="marca" class="form-control" value="<?= htmlspecialchars($v['marca']) ?>" required>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Modelo</label>
                    <input name="modelo" class="form-control" value="<?= htmlspecialchars($v['modelo']) ?>" required>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Año</label>
                    <input name="anio" type="number" min="1980" max="2100" class="form-control" value="<?= (int)$v['anio'] ?>" required>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Color</label>
                    <input name="color" class="form-control" value="<?= htmlspecialchars($v['color']) ?>" required>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Capacidad</label>
                    <input name="capacidad" type="number" min="1" max="99" class="form-control" value="<?= (int)$v['capacidad'] ?>" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Foto (opcional)</label>
                    <input name="foto" type="file" accept="image/*" class="form-control">
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
        <!-- Mensaje cuando el chofer aún no registra vehículos -->
        <div class="alert alert-warning">No hay vehículos registrados.</div>
      <?php endif; ?>

      <!-- Enlace de retorno al panel principal del chofer -->
      <div class="mt-3">
        <a class="btn btn-outline-secondary" href="../chofer/dashboard.php">⬅️ Volver</a>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
