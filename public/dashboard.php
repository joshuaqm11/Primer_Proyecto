<?php
require_once '../inc/funciones.php'; // ruta correcta desde public/dashboard.php

// Solo admin puede acceder
if (!esAdmin()) {
    header('Location: index.php');
    exit;
}

global $mysqli;

// Manejo de acciones: activar, desactivar o crear admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Crear nuevo admin
    if (isset($_POST['crear_admin'])) {
        $nombre = trim($_POST['nombre']);
        $apellido = trim($_POST['apellido']);
        $correo = trim($_POST['correo']);
        $password = $_POST['password'];
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $mysqli->prepare("INSERT INTO usuarios (nombre, apellido, correo, password_hash, tipo, estado) VALUES (?, ?, ?, ?, 'admin', 'activo')");
        $stmt->bind_param("ssss", $nombre, $apellido, $correo, $hash);
        $stmt->execute();
    }

    // Cambiar estado de usuario
    if (isset($_POST['cambiar_estado'])) {
        $user_id = $_POST['user_id'];
        $nuevo_estado = $_POST['estado'];
        $stmt = $mysqli->prepare("UPDATE usuarios SET estado = ? WHERE id = ?");
        $stmt->bind_param("si", $nuevo_estado, $user_id);
        $stmt->execute();
    }
}

// Obtener lista de usuarios
$result = $mysqli->query("SELECT id, nombre, apellido, correo, tipo, estado FROM usuarios ORDER BY tipo, nombre");
$usuarios = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Dashboard Admin - Rides</title>
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Panel de Administración</h1>
        <a href="logout.php" class="btn btn-danger">Cerrar sesión</a>
    </div>

    <h3>Usuarios Registrados</h3>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($usuarios as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido']) ?></td>
                    <td><?= htmlspecialchars($u['correo']) ?></td>
                    <td><?= $u['tipo'] ?></td>
                    <td><?= $u['estado'] ?></td>
                    <td>
                        <form method="post" class="d-flex gap-2">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <select name="estado" class="form-select form-select-sm">
                                <option value="activo" <?= $u['estado']=='activo'?'selected':'' ?>>Activo</option>
                                <option value="pendiente" <?= $u['estado']=='pendiente'?'selected':'' ?>>Pendiente</option>
                                <option value="inactivo" <?= $u['estado']=='inactivo'?'selected':'' ?>>Inactivo</option>
                            </select>
                            <button type="submit" name="cambiar_estado" class="btn btn-sm btn-primary">Cambiar</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h3 class="mt-5">Crear Nuevo Administrador</h3>
    <form method="post" class="row g-3">
        <div class="col-md-3">
            <input type="text" name="nombre" class="form-control" placeholder="Nombre" required>
        </div>
        <div class="col-md-3">
            <input type="text" name="apellido" class="form-control" placeholder="Apellido" required>
        </div>
        <div class="col-md-3">
            <input type="email" name="correo" class="form-control" placeholder="Correo" required>
        </div>
        <div class="col-md-3">
            <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
        </div>
        <div class="col-12">
            <button type="submit" name="crear_admin" class="btn btn-success">Crear Admin</button>
        </div>
    </form>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
