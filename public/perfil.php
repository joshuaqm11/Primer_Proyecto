// Realizado por Joshua Quesada y Fabio Oconitrillo
<?php
require_once '../inc/funciones.php'; // Carga helpers

// Solo permite acceso con sesión activa
if (!isLoggedIn()) { header("Location: ./index.php"); exit; }

// Refresca los datos del usuario en sesión
refrescarUsuarioEnSesion();
$user = $_SESSION['user'];

$msg = $err = null;

/* =========================================================
   Guardado de datos personales
   - Actualización de campos básicos y foto de perfil
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'perfil') {
    $nombre    = trim($_POST['nombre'] ?? '');
    $apellido  = trim($_POST['apellido'] ?? '');
    $cedula    = trim($_POST['cedula'] ?? '');
    $fecha_nac = trim($_POST['fecha_nacimiento'] ?? '');
    $correo    = trim($_POST['correo'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');

    // Subida de foto si fue enviada
    $rutaFoto = null;
    if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $rutaFoto = subirFotoUsuario($_FILES['foto']); // Devuelve ruta relativa (uploads/fotos_usuarios/...)
    }

    // Actualiza perfil en BD y refleja en sesión si fue exitoso
    [$ok, $msj] = actualizarPerfilUsuario($user['id'], $nombre, $apellido, $cedula, $fecha_nac, $correo, $telefono, $rutaFoto);
    if ($ok) {
        refrescarUsuarioEnSesion();  // Vuelve a cargar datos del usuario
        $user = $_SESSION['user'];
        $msg = $msj;                 // Mensaje de éxito
    } else {
        $err = $msj;                 // Mensaje de error
    }
}

/* =========================================================
   Cambio de contraseña (formulario "password")
   - Verifica confirmación y longitud mínima
   - Usa verificación de contraseña actual y hash seguro
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'password') {
    $passActual = $_POST['pass_actual'] ?? '';
    $passNueva  = $_POST['pass_nueva'] ?? '';
    $passConf   = $_POST['pass_conf']   ?? '';

    // Validaciones básicas en servidor
    if ($passNueva !== $passConf) {
        $err = "Las contraseñas nuevas no coinciden.";
    } elseif (strlen($passNueva) < 4) {
        $err = "La nueva contraseña debe tener al menos 4 caracteres.";
    } else {
        // Intenta actualizar password en BD
        [$ok, $msj] = cambiarPasswordUsuario($user['id'], $passActual, $passNueva);
        if ($ok) $msg = $msj; else $err = $msj;
    }
}

// Construye URL relativa a la foto actual
$fotoUrl = !empty($user['foto']) ? '../'.ltrim($user['foto'],'/') : null;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Mi Perfil</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap + Icons para estilos -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Barra superior con nombre/foto de usuario y botón de logout -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#">Rides - Perfil</a>
    <div class="d-flex align-items-center gap-2">
      <span class="navbar-text text-white me-2 d-flex align-items-center gap-2">
        <?php if ($fotoUrl): ?>
          <img src="<?= htmlspecialchars($fotoUrl) ?>" class="rounded-circle border border-light" style="width:32px;height:32px;object-fit:cover;">
        <?php else: ?>
          <i class="bi bi-person-circle fs-4"></i>
        <?php endif; ?>
        <?= htmlspecialchars($user['nombre'] ?? '') ?>
      </span>
      <a href="./logout.php" class="btn btn-outline-light btn-sm">Cerrar sesión</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <h2 class="mb-3">Mi Perfil</h2>

  <!-- Mensajes de resultado (éxito / error) -->
  <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <div class="row g-4">
    <!-- =================== Formulario de datos personales =================== -->
    <div class="col-lg-7">
      <div class="card shadow-sm">
        <div class="card-header fw-bold">Datos personales</div>
        <div class="card-body">
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="form" value="perfil">

            <div class="row g-3">
              <!-- Campos básicos del perfil -->
              <div class="col-md-6">
                <label class="form-label">Nombre</label>
                <input name="nombre" class="form-control" value="<?= htmlspecialchars($user['nombre'] ?? '') ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Apellido</label>
                <input name="apellido" class="form-control" value="<?= htmlspecialchars($user['apellido'] ?? '') ?>" required>
              </div>

              <div class="col-md-6">
                <label class="form-label">Cédula</label>
                <input name="cedula" class="form-control" value="<?= htmlspecialchars($user['cedula'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Fecha de nacimiento</label>
                <input type="date" name="fecha_nacimiento" class="form-control" value="<?= htmlspecialchars($user['fecha_nacimiento'] ?? '') ?>">
              </div>

              <div class="col-md-6">
                <label class="form-label">Correo</label>
                <input type="email" name="correo" class="form-control" value="<?= htmlspecialchars($user['correo'] ?? '') ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Teléfono</label>
                <input name="telefono" class="form-control" value="<?= htmlspecialchars($user['telefono'] ?? '') ?>">
              </div>

              <!-- Subida de foto -->
              <div class="col-12">
                <label class="form-label">Foto de perfil</label>
                <input type="file" name="foto" class="form-control" accept=".jpg,.jpeg,.png,.webp,.gif">
                <small class="text-muted">Max 3MB. Se reemplazará la foto actual.</small>
              </div>
            </div>

            <!-- Acciones del formulario -->
            <div class="mt-3 d-flex gap-2">
              <button class="btn btn-primary">Guardar cambios</button>
              <!-- Enlace dinámico a panel según rol actual -->
              <a class="btn btn-outline-secondary" href="<?= (esChofer() ? '../chofer/dashboard.php' : (esPasajero() ? '../pasajero/dashboard.php' : './index.php')) ?>">Volver</a>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- =================== Cambio de contraseña + vista de foto =================== -->
    <div class="col-lg-5">
      <div class="card shadow-sm">
        <div class="card-header fw-bold">Cambiar contraseña</div>
        <div class="card-body">
          <form method="post">
            <input type="hidden" name="form" value="password">
            <div class="mb-3">
              <label class="form-label">Contraseña actual</label>
              <input type="password" name="pass_actual" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Nueva contraseña</label>
              <input type="password" name="pass_nueva" class="form-control" minlength="4" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Confirmar nueva contraseña</label>
              <input type="password" name="pass_conf" class="form-control" minlength="4" required>
            </div>
            <button class="btn btn-warning">Actualizar contraseña</button>
          </form>
        </div>
      </div>

      <!-- Muestra de la foto actual en tamaño mayor -->
      <div class="card shadow-sm mt-3">
        <div class="card-header fw-bold">Foto actual</div>
        <div class="card-body text-center">
          <?php if ($fotoUrl): ?>
            <img src="<?= htmlspecialchars($fotoUrl) ?>" class="rounded-circle" style="width:120px;height:120px;object-fit:cover;">
          <?php else: ?>
            <div class="text-muted">Sin foto</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

</div>
</body>
</html>
