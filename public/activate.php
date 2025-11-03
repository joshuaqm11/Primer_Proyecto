// Realizado por Joshua Quesada y Fabio Oconitrillo
<?php
require_once '../inc/funciones.php'; // Carga helpers y conexión

$token = trim($_GET['token'] ?? ''); // Token recibido por URL desde el correo de activación

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Activación de cuenta</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Estilos Bootstrap CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-7">
      <div class="card shadow-sm">
        <div class="card-header fw-bold">Activación de cuenta</div>
        <div class="card-body">
<?php
// Validación rápida: token presente
if ($token === '') {
  echo '<div class="alert alert-danger">Token vacío o inválido.</div>';
} else {
  global $mysqli;

  // Busca el token en la tabla de activaciones y trae datos del usuario
  $stmt = $mysqli->prepare("
    SELECT at.usuario_id, u.estado, u.correo
      FROM activation_tokens at
      JOIN usuarios u ON u.id = at.usuario_id
     WHERE at.token = ?
     LIMIT 1
  ");
  $stmt->bind_param("s", $token);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  $stmt->close();

  if (!$row) {
    // Token inexistente o ya consumido
    echo '<div class="alert alert-danger">El token no existe o ya fue utilizado.</div>';
  } else {
    // Si el usuario aún no está activo, lo activa
    if ($row['estado'] !== 'activo') {
      $uid = (int)$row['usuario_id'];

      // Actualiza estado del usuario a 'activo'
      $stmt = $mysqli->prepare("UPDATE usuarios SET estado='activo' WHERE id=?");
      $stmt->bind_param("i", $uid);
      $stmt->execute();
      $stmt->close();
    }

    // Consume el token (lo elimina para que no pueda reutilizarse)
    $stmt = $mysqli->prepare("DELETE FROM activation_tokens WHERE token=?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->close();

    // Mensaje final y acceso al login
    echo '<div class="alert alert-success">
            ¡Tu cuenta ha sido activada correctamente! Ya puedes iniciar sesión.
          </div>';
    echo '<a href="./index.php" class="btn btn-primary">Ir al login</a>';
  }
}
?>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
