// Realizado por Joshua Quesada y Fabio Oconitrillo
<?php
require_once '../inc/funciones.php'; // incluye conexión y funciones, ya inicia sesión automáticamente

$error = '';

// Si ya hay sesión iniciada, la reiniciamos para evitar arrastrar datos previos
if (isset($_SESSION['user'])) {
    session_unset();
    session_destroy();
    session_start();
}

// Procesa el login al enviar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);   // Correo ingresado
    $password = $_POST['password'];   // Contraseña en texto plano

    // Busca usuario por correo
    $user = obtenerUsuarioPorEmail($email);

    // Verifica usuario y contraseña (hash en BD)
    if ($user && password_verify($password, $user['password_hash'])) {
        // Valida estado del usuario antes de permitir acceso
        if ($user['estado'] !== 'activo') {
            $error = "Cuenta no activa: " . $user['estado'];
        } else {
            // Guarda todo el registro en sesión
            $_SESSION['user'] = $user;

            // Redirección según rol
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
                    // Fallback: página pública de búsqueda
                    header('Location: search.php');
                    break;
            }
            exit;
        }
    } else {
        // Mensaje genérico para no revelar si el correo existe
        $error = "Correo o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Login - Rides</title>
<!-- Bootstrap CSS desde CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card mt-5 shadow-sm">
        <div class="card-body">
          <h2 class="card-title text-center mb-4">Login - Rides</h2>

          <!-- Alerta de error -->
          <?php if (!empty($error)) : ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

          <!-- Formulario de inicio de sesión -->
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
          <!-- Enlaces rápidos de registro y búsqueda pública -->
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

<!-- Bootstrap JS (bundle con Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
