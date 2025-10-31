<?php
require_once '../inc/funciones.php';

$token = trim($_GET['token'] ?? '');

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Activación de cuenta</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
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
if ($token === '') {
  echo '<div class="alert alert-danger">Token vacío o inválido.</div>';
} else {
  global $mysqli;

  // Buscar el token
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
    echo '<div class="alert alert-danger">El token no existe o ya fue utilizado.</div>';
  } else {
    // Activar al usuario si no está activo
    if ($row['estado'] !== 'activo') {
      $uid = (int)$row['usuario_id'];

      $stmt = $mysqli->prepare("UPDATE usuarios SET estado='activo' WHERE id=?");
      $stmt->bind_param("i", $uid);
      $stmt->execute();
      $stmt->close();
    }

    // Consumir el token
    $stmt = $mysqli->prepare("DELETE FROM activation_tokens WHERE token=?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->close();

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
