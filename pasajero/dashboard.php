// Realizado por Joshua Quesada y Fabio Oconitrillo
<?php
require_once '../inc/funciones.php';                 // Helpers

// Guardia de acceso: solo usuarios con rol PASAJERO
if (!esPasajero()) { header("Location: ../public/index.php"); exit; }

// Refresca los datos del usuario en sesión
if (function_exists('refrescarUsuarioEnSesion')) {
    refrescarUsuarioEnSesion();
}
$user = $_SESSION['user'];

// Construye URL relativa de la foto para mostrar en la navbar
$fotoUrl = !empty($user['foto']) ? '../' . ltrim($user['foto'], '/') : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Panel del Pasajero - Rides</title>
<!-- Bootstrap + Icons desde CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- BARRA DE NAVEGACIÓN SUPERIOR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-success">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#">Rides - Pasajero</a>
    <div class="d-flex">
      <span class="navbar-text text-white me-3 d-flex align-items-center gap-2">
        <?php if ($fotoUrl): ?>
          <!-- Muestra foto de usuario si existe -->
          <img src="<?= htmlspecialchars($fotoUrl) ?>" 
               class="rounded-circle border border-light"
               style="width:32px;height:32px;object-fit:cover;" alt="Foto">
        <?php else: ?>
          <!-- Ícono genérico si no hay foto -->
          <i class="bi bi-person-circle fs-4"></i>
        <?php endif; ?>
        <!-- Nombre del usuario -->
        <?= htmlspecialchars($user['nombre']) ?>
      </span>

      <!-- Accesos rápidos -->
      <a href="../public/perfil.php" class="btn btn-outline-light btn-sm me-2">Mi Perfil</a>
      <a href="../public/logout.php" class="btn btn-outline-light btn-sm">Cerrar sesión</a>
    </div>
  </div>
</nav>

<!-- CONTENIDO PRINCIPAL: accesos a búsqueda y reservas del pasajero -->
<div class="container py-5">
  <h2 class="mb-4 text-center">Panel del Pasajero</h2>
  <div class="row g-4">

    <!-- Acceso a buscador de rides -->
    <div class="col-md-6">
      <div class="card text-center shadow-sm border-0">
        <div class="card-body">
          <i class="bi bi-search display-4 text-success"></i>
          <h5 class="card-title mt-3">Buscar Rides</h5>
          <p class="card-text">Encuentra y reserva rides disponibles.</p>
          <a href="../public/search.php" class="btn btn-success">Ir</a>
        </div>
      </div>
    </div>

    <!-- Acceso a las reservas del pasajero -->
    <div class="col-md-6">
      <div class="card text-center shadow-sm border-0">
        <div class="card-body">
          <i class="bi bi-calendar2-check display-4 text-primary"></i>
          <h5 class="card-title mt-3">Mis Reservas</h5>
          <p class="card-text">Consulta o cancela tus reservas activas.</p>
          <a href="reservas.php" class="btn btn-primary">Ir</a>
        </div>
      </div>
    </div>

  </div>

  <!-- Acción de cerrar sesión destacada al final -->
  <hr class="my-5">
  <div class="text-center">
    <a href="../public/logout.php" class="btn btn-outline-danger">
      <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
    </a>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
