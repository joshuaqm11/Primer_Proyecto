<?php
require_once '../inc/funciones.php';

$tipo = 'pasajero'; // En register_chofer.php será 'chofer'
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $mysqli;

    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $correo = trim($_POST['correo']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Insertar usuario como pendiente
    $stmt = $mysqli->prepare("INSERT INTO usuarios (nombre, apellido, correo, password_hash, tipo, estado) VALUES (?, ?, ?, ?, ?, 'pendiente')");
    $stmt->bind_param("sssss", $nombre, $apellido, $correo, $password, $tipo);
    $stmt->execute();

    // Generar token de activación
    $token = bin2hex(random_bytes(16));
    $uid = $stmt->insert_id;
    $stmt = $mysqli->prepare("INSERT INTO activation_tokens (usuario_id, token) VALUES (?, ?)");
    $stmt->bind_param("is", $uid, $token);
    $stmt->execute();

    // Enlace de activación
    $link = "http://localhost/Primer_proyecto/public/activate.php?token=$token";
    @mail($correo, "Activar cuenta Rides", "Active su cuenta aquí:\n$link", "From: no-reply@rides.local");

    $msg = "✅ Cuenta creada correctamente. Revise su correo para activarla.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registro de Pasajero - Rides</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow-lg border-0 rounded-4">
        <div class="card-header bg-primary text-white text-center py-3">
          <h3 class="mb-0">Registro de Pasajero</h3>
        </div>
        <div class="card-body">
          <?php if (!empty($msg)): ?>
            <div class="alert alert-success text-center"><?= $msg ?></div>
          <?php endif; ?>

          <form method="post">
            <div class="mb-3">
              <label class="form-label">Nombre</label>
              <input name="nombre" type="text" class="form-control" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Apellido</label>
              <input name="apellido" type="text" class="form-control" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Correo electrónico</label>
              <input type="email" name="correo" class="form-control" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Contraseña</label>
              <input type="password" name="password" class="form-control" required>
            </div>

            <button class="btn btn-primary w-100">Registrar</button>
          </form>
        </div>
        <div class="card-footer text-center">
          <a href="index.php" class="text-decoration-none">⬅️ Volver al login</a>
        </div>
      </div>
    </div>
  </div>
</div>

</body>
</html>

