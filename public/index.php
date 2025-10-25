<?php
require_once '../inc/funciones.php'; // incluye conexión y funciones, ya inicia sesión automáticamente

$error = '';

// Si ya hay sesión iniciada, la reiniciamos
if (isset($_SESSION['user'])) {
    session_unset();
    session_destroy();
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $user = obtenerUsuarioPorEmail($email);

    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['estado'] !== 'activo') {
            $error = "Cuenta no activa: " . $user['estado'];
        } else {
            $_SESSION['user'] = $user;

            // Redirección según tipo de usuario
            switch ($user['tipo']) {
                case 'admin':
                    header('Location: dashboard.php'); 
                    break;
                case 'chofer':
                    header('Location: ../chofer/dashboard.php'); 
                    break;
                case 'pasajero':
                    header('Location: ../pasajero/dashboard.php'); 
                    break;
                default:
                    header('Location: search.php');
                    break;
            }
            exit;
        }
    } else {
        $error = "Correo o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Login - Rides</title>
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card mt-5 shadow-sm">
        <div class="card-body">
          <h2 class="card-title text-center mb-4">Login - Rides</h2>

          <?php if (!empty($error)) : ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

          <form method="post">
            <div class="mb-3">
              <input type="email" name="email" class="form-control" placeholder="Correo" required>
            </div>
            <div class="mb-3">
              <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
            </div>
            <div class="d-grid">
              <button type="submit" class="btn btn-primary">Ingresar</button>
            </div>
          </form>

          <hr>
          <p class="text-center">
            <a href="register_pasajero.php">Registrar Pasajero</a> | 
            <a href="register_chofer.php">Registrar Chofer</a>
          </p>
          <p class="text-center">
            <a href="search.php">Buscar Rides Públicos</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

