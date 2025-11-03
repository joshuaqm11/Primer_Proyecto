// Realizado por Joshua Quesada y Fabio Oconitrillo
<?php
// Incluye funciones y helpers globales
require_once '../inc/funciones.php';

// Guardia de acceso: si NO es chofer, redirige al inicio público y termina el script
if (!esChofer()) { header("Location: ../public/index.php"); exit; }

// Obtiene los datos del usuario autenticado desde la sesión
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Panel del Chofer - Rides</title>

<!-- Bootstrap CSS y Bootstrap Icons desde CDN para estilos e iconografía -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- NAVBAR: barra superior con foto/nombre de usuario y acciones rápidas -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    <!-- Marca/Sección izquierda -->
    <a class="navbar-brand fw-bold" href="#">Rides - Chofer</a>

    <!-- Sección derecha: info de usuario + botones -->
    <div class="d-flex">
      <!-- Muestra foto de perfil si existe; de lo contrario un ícono genérico -->
      <span class="navbar-text text-white me-3 d-flex align-items-center gap-2">
  <?php if (!empty($user['foto'])): ?>
    <img src="../<?= htmlspecialchars($user['foto']) ?>" 
         alt="Foto" 
         class="rounded-circle border border-light" 
         style="width:32px;height:32px;object-fit:cover;">
  <?php else: ?>
    <i class="bi bi-person-circle fs-4"></i>
  <?php endif; ?>
  <!-- Nombre del usuario autenticado -->
  <?= htmlspecialchars($user['nombre']) ?>
</span>
      </span>

        <!-- Enlace al perfil del usuario -->
      <a href="../public/perfil.php" class="btn btn-outline-light btn-sm me-2">Mi Perfil</a>

        <!-- Enlace para cerrar sesión -->
      <a href="../public/logout.php" class="btn btn-outline-light btn-sm">Cerrar sesión</a>
    </div>
  </div>
</nav>

<!-- CONTENIDO PRINCIPAL: accesos rápidos del chofer -->
<div class="container py-5">
  <h2 class="mb-4 text-center">Panel del Chofer</h2>

  <!-- Tarjetas de navegación: Vehículos / Rides / Reservas -->
  <div class="row g-4">

    <!-- Atajo a la gestión de vehículos del chofer -->
    <div class="col-md-4">
      <div class="card text-center shadow-sm border-0">
        <div class="card-body">
          <i class="bi bi-car-front display-4 text-primary"></i>
          <h5 class="card-title mt-3">Mis Vehículos</h5>
          <p class="card-text">Administra tus vehículos registrados.</p>
          <a href="vehiculos.php" class="btn btn-primary">Ir</a>
        </div>
      </div>
    </div>

    <!-- Atajo a la gestión de rides (viajes) creados por el chofer -->
    <div class="col-md-4">
      <div class="card text-center shadow-sm border-0">
        <div class="card-body">
          <i class="bi bi-geo-alt display-4 text-success"></i>
          <h5 class="card-title mt-3">Mis Rides</h5>
          <p class="card-text">Crea, modifica o elimina tus viajes.</p>
          <a href="rides.php" class="btn btn-success">Ir</a>
        </div>
      </div>
    </div>

    <!-- Atajo a la administración de reservas recibidas por el chofer -->
    <div class="col-md-4">
      <div class="card text-center shadow-sm border-0">
        <div class="card-body">
          <i class="bi bi-calendar-check display-4 text-warning"></i>
          <h5 class="card-title mt-3">Reservas Recibidas</h5>
          <p class="card-text">Administra las reservas que te han solicitado.</p>
          <a href="reservas.php" class="btn btn-warning text-white">Ir</a>
        </div>
      </div>
    </div>

  </div>

  
</div>

<!-- Bundle de Bootstrap (incluye Popper) para componentes interactivos -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
