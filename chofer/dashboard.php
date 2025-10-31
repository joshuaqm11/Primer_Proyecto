<?php
require_once '../inc/funciones.php';
if (!esChofer()) { header("Location: ../public/index.php"); exit; }
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Panel del Chofer - Rides</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#">Rides - Chofer</a>
    <div class="d-flex">
      <span class="navbar-text text-white me-3 d-flex align-items-center gap-2">
  <?php if (!empty($user['foto'])): ?>
    <img src="../<?= htmlspecialchars($user['foto']) ?>" 
         alt="Foto" 
         class="rounded-circle border border-light" 
         style="width:32px;height:32px;object-fit:cover;">
  <?php else: ?>
    <i class="bi bi-person-circle fs-4"></i>
  <?php endif; ?>
  <?= htmlspecialchars($user['nombre']) ?>
</span>
      </span>

        <!-- Botón Mi Perfil -->
      <a href="../public/perfil.php" class="btn btn-outline-light btn-sm me-2">Mi Perfil</a>

        <!-- Botón Cerrar Sesión -->
      <a href="../public/logout.php" class="btn btn-outline-light btn-sm">Cerrar sesión</a>
    </div>
  </div>
</nav>

<!-- CONTENIDO -->
<div class="container py-5">
  <h2 class="mb-4 text-center">Panel del Chofer</h2>
  <div class="row g-4">

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
