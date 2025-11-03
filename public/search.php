// Realizado por Joshua Quesada y Fabio Oconitrillo
<?php
require_once '../inc/funciones.php';

// Permite acceso público a la búsqueda; si hay sesión, la usamos solo para personalizar y filtrar
$logged = isLoggedIn();
$user   = $logged ? $_SESSION['user'] : null;

global $mysqli; // Conexión MySQLi compartida

// ====== Parámetros de filtro (GET) ======
$lugar_salida   = trim($_GET['salida']  ?? '');
$lugar_llegada  = trim($_GET['llegada'] ?? '');
$fecha          = trim($_GET['fecha']   ?? '');
$solo_disp      = isset($_GET['solo_disp']) ? (int)$_GET['solo_disp'] : 1; // 1 => mostrar solo rides con cupos

// ====== Construcción dinámica del WHERE con parámetros enlazados ======
// Solo rides a futuro
$cond   = " WHERE r.fecha >= CURDATE() ";
$params = [];
$types  = "";

// Si el usuario está logueado, no mostramos sus propios rides
if ($logged) {
  $cond    .= " AND r.usuario_id <> ? ";
  $params[] = $user['id'];
  $types   .= "i";
}

// Filtros por texto
if ($lugar_salida !== "") {
  $cond    .= " AND r.lugar_salida LIKE CONCAT('%', ?, '%') ";
  $params[] = $lugar_salida; 
  $types   .= "s";
}
if ($lugar_llegada !== "") {
  $cond    .= " AND r.lugar_llegada LIKE CONCAT('%', ?, '%') ";
  $params[] = $lugar_llegada; 
  $types   .= "s";
}
if ($fecha !== "") {
  // Filtro por fecha exacta (YYYY-mm-dd)
  $cond    .= " AND r.fecha = ? ";
  $params[] = $fecha; 
  $types   .= "s";
}
// Filtro por espacios disponibles
if ($solo_disp === 1) {
  $cond .= " AND r.espacios > 0 ";
}

// Consulta principal: ride + chofer + vehículo
$sql = "SELECT r.*, 
               u.nombre  AS chofer_nombre, 
               u.apellido AS chofer_apellido,
               v.placa, v.marca, v.modelo, v.color
        FROM rides_data r
        JOIN usuarios u ON u.id = r.usuario_id
   LEFT JOIN vehiculos v ON v.id = r.vehiculo_id
        $cond
    ORDER BY r.fecha ASC, r.hora ASC";

// Preparación segura de la sentencia
$stmt = $mysqli->prepare($sql);
if ($types !== "") {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();

// Imagen de perfil para navbar
$fotoUrl = ($logged && !empty($user['foto'])) ? '../'.ltrim($user['foto'],'/') : null;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Buscar Rides</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-success">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#">Rides - Búsqueda</a>
    <div class="d-flex align-items-center gap-2">
      <?php if ($logged): ?>
        <!-- Navbar con foto/nombre y botón de logout si hay sesión -->
        <span class="navbar-text text-white me-2 d-flex align-items-center gap-2">
          <?php if ($fotoUrl): ?>
            <img src="<?= htmlspecialchars($fotoUrl) ?>" class="rounded-circle border border-light"
                 style="width:32px;height:32px;object-fit:cover;" alt="Foto">
          <?php else: ?>
            <i class="bi bi-person-circle fs-4"></i>
          <?php endif; ?>
          <?= htmlspecialchars($user['nombre'] ?? '') ?>
        </span>
        <a href="../public/logout.php" class="btn btn-outline-light btn-sm">Cerrar sesión</a>
      <?php else: ?>
        <!-- Invitado: ofrece login y registro -->
        <a href="./index.php" class="btn btn-outline-light btn-sm">Iniciar sesión</a>
        <a href="./registro_pasajero.php" class="btn btn-light btn-sm ms-2">Registrarse</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="container py-4">
  <h2 class="mb-3">Buscar Rides</h2>

  <!-- Formulario de filtros (GET) -->
  <form class="row g-3 mb-4" method="get">
    <div class="col-md-4">
      <label class="form-label">Lugar de salida</label>
      <input type="text" class="form-control" name="salida" value="<?= htmlspecialchars($lugar_salida) ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Lugar de llegada</label>
      <input type="text" class="form-control" name="llegada" value="<?= htmlspecialchars($lugar_llegada) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Fecha</label>
      <input type="date" class="form-control" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
    </div>
    <div class="col-md-8 d-flex align-items-end">
      <div class="form-check">
        <!-- Mantiene el check por defecto para mostrar solo rides con cupo -->
        <input class="form-check-input" type="checkbox" id="solo_disp" name="solo_disp" value="1" <?= $solo_disp ? 'checked' : '' ?>>
        <label class="form-check-label" for="solo_disp">
          Solo con espacios disponibles
        </label>
      </div>
    </div>
    <div class="col-md-4 d-grid">
      <label class="form-label d-none d-md-block">&nbsp;</label>
      <button class="btn btn-success"><i class="bi bi-search"></i> Buscar</button>
    </div>
  </form>

  <!-- Resultados de la búsqueda -->
  <div class="card">
    <div class="card-header fw-bold">Resultados</div>
    <div class="card-body">
      <?php if ($res && $res->num_rows): ?>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>Ride</th>
              <th>Trayecto</th>
              <th>Fecha</th>
              <th>Hora</th>
              <th>Costo</th>
              <th>Espacios</th>
              <th>Vehículo</th>
              <th>Chofer</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
          <?php while($ride = $res->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($ride['nombre']) ?></td>
              <td><?= htmlspecialchars($ride['lugar_salida'].' → '.$ride['lugar_llegada']) ?></td>
              <td><?= htmlspecialchars($ride['fecha']) ?></td>
              <td><?= htmlspecialchars(substr($ride['hora'],0,5)) ?></td>
              <td>₡ <?= number_format((float)$ride['costo'],2) ?></td>
              <td><?= (int)$ride['espacios'] ?></td>
              <td><?= $ride['placa'] ? htmlspecialchars($ride['placa'].' ('.$ride['marca'].' '.$ride['modelo'].')') : '—' ?></td>
              <td><?= htmlspecialchars(($ride['chofer_nombre'] ?? '').' '.($ride['chofer_apellido'] ?? '')) ?></td>
              <td>
                <?php if (esPasajero()): ?>
                  <?php if ((int)$ride['espacios'] > 0): ?>
                    <!-- Acción de reservar: envía POST a la página de reservas del pasajero -->
                    <form method="post" action="../pasajero/reservas.php" class="d-flex gap-2">
                      <input type="hidden" name="crear_reserva" value="1">
                      <input type="hidden" name="ride_id" value="<?= (int)$ride['id'] ?>">
                      <input type="number" name="cantidad" min="1" max="<?= (int)$ride['espacios'] ?>" value="1"
                             class="form-control form-control-sm" style="width:90px">
                      <button class="btn btn-sm btn-primary">Reservar</button>
                    </form>
                  <?php else: ?>
                    <!-- Sin espacios disponibles -->
                    <span class="badge bg-danger">Sin espacios</span>
                  <?php endif; ?>
                <?php else: ?>
                  <!-- Invitado: invitar a iniciar sesión para poder reservar -->
                  <a class="btn btn-sm btn-outline-primary" href="./index.php">Inicia sesión para reservar</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <!-- Sin coincidencias con los filtros -->
        <div class="alert alert-secondary">No se encontraron rides con esos criterios.</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Navegación secundaria -->
  <div class="mt-3 d-flex gap-2">
    <?php if ($logged): ?>
      <a class="btn btn-outline-secondary" href="../pasajero/dashboard.php">⬅️ Volver al Panel</a>
    <?php else: ?>
      <a class="btn btn-outline-secondary" href="./index.php">⬅️ Volver al Login</a>
    <?php endif; ?>
    <a class="btn btn-outline-primary" href="./search.php">Limpiar filtros</a>
  </div>
</div>
</body>
</html>
