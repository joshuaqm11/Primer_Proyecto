// Realizado por Joshua Quesada y Fabio Oconitrillo
<?php
require_once '../inc/funciones.php';
require_once '../inc/mailer.php'; // ← usa PHPMailer vía enviarCorreo()

$tipo = 'chofer'; // Rol fijo para este formulario de registro
$msg = '';
$err = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $mysqli;

    // ====== Captura de campos del formulario ======
    $nombre    = trim($_POST['nombre'] ?? '');
    $apellido  = trim($_POST['apellido'] ?? '');
    $cedula    = trim($_POST['cedula'] ?? '');
    $fecha_n   = trim($_POST['fecha_nacimiento'] ?? '');
    $correo    = trim($_POST['correo'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';

    /* 1) Validaciones básicas */
    if ($password !== $password2) $err[] = "Las contraseñas no coinciden.";
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) $err[] = "Correo inválido.";

    /* 2) Verificar si el correo ya existe (encapsulado) */
    if (!$err) {
        $stmt = $mysqli->prepare("SELECT 1 FROM usuarios WHERE correo = ? LIMIT 1");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            // Evita duplicidad de correos
            $err[] = "El correo ya está registrado. Intente con otro.";
        }
        $stmt->close();
    }

    /* Si hay errores, NO seguimos con creación */
    if (!$err) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT); // Hash seguro de contraseña

        /* 3) INSERT */
        $stmt = $mysqli->prepare("
            INSERT INTO usuarios
            (nombre, apellido, cedula, fecha_nacimiento, correo, telefono, password_hash, tipo, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')
        ");
        $stmt->bind_param("ssssssss", $nombre, $apellido, $cedula, $fecha_n, $correo, $telefono, $password_hash, $tipo);

        $uid = null; // Guardará el ID del nuevo usuario
        try {
            $stmt->execute();
            $uid = $stmt->insert_id; // ID autogenerado del usuario
        } catch (mysqli_sql_exception $e) {
            if ((int)$e->getCode() === 1062) {
                // Colisión de clave única (correo)
                $err[] = "El correo ya está registrado. Intente con otro.";
            } else {
                throw $e;
            }
        } finally {
            $stmt->close();
        }

        /* Si el INSERT falló, no seguimos */
        if (!$err) {
            /* 4) Subida de foto */
            if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $maxBytes = 3 * 1024 * 1024; // 3 MB
                if ($_FILES['foto']['size'] <= $maxBytes && is_uploaded_file($_FILES['foto']['tmp_name'])) {

                    // Detección de extensión segura
                    $ext = null;
                    if (class_exists('finfo')) {
                        $finfo = new finfo(FILEINFO_MIME_TYPE);
                        $mime  = $finfo->file($_FILES['foto']['tmp_name']);
                        if     ($mime === 'image/jpeg') $ext = 'jpg';
                        elseif ($mime === 'image/png')  $ext = 'png';
                        elseif ($mime === 'image/webp') $ext = 'webp';
                        elseif ($mime === 'image/gif')  $ext = 'gif';
                    }
                    if ($ext === null) {
                        $extGuess = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                        if (in_array($extGuess, ['jpg','jpeg','png','webp','gif'])) {
                            $ext = ($extGuess === 'jpeg') ? 'jpg' : $extGuess;
                        }
                    }

                    if ($ext !== null) {
                        // Ruta absoluta de almacenamiento
                        $absDir = 'C:\\ISW-613\\httpdocs\\Primer_proyecto\\uploads\\fotos_usuarios';
                        if (!is_dir($absDir)) { @mkdir($absDir, 0775, true); }

                        $filename = $uid . '_' . time() . '.' . $ext;      // Nombre único (userID_timestamp.ext)
                        $absPath  = $absDir . DIRECTORY_SEPARATOR . $filename;

                        if (move_uploaded_file($_FILES['foto']['tmp_name'], $absPath)) {
                            // Ruta relativa servible por la web
                            $relativePath = 'uploads/fotos_usuarios/' . $filename;

                            // Actualiza la foto del usuario recién creado
                            $stmt = $mysqli->prepare("UPDATE usuarios SET foto = ? WHERE id = ?");
                            $stmt->bind_param("si", $relativePath, $uid);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }
            }

            /* 5) Token + correo de activación */
            $token = bin2hex(random_bytes(16)); // Token aleatorio
            $stmt = $mysqli->prepare("INSERT INTO activation_tokens (usuario_id, token) VALUES (?, ?)");
            $stmt->bind_param("is", $uid, $token);
            $stmt->execute();
            $stmt->close();

            // Construcción del enlace absoluto hacia activate.php
            $BASE_URL = base_url_public(); // Helper
            $link = $BASE_URL . "/activate.php?token=" . urlencode($token);

            // Contenido del correo de activación
            $subject = "Activa tu cuenta en Rides";
            $html = '
              <div style="font-family:Arial,Helvetica,sans-serif;line-height:1.5">
                <h2>¡Bienvenido a Rides!</h2>
                <p>Para activar tu cuenta, haz clic en el siguiente enlace:</p>
                <p><a href="'.htmlspecialchars($link).'" target="_blank"
                      style="background:#0d6efd;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;">
                      Activar mi cuenta
                   </a></p>
                <p>Si el botón no funciona, copia y pega esta URL en tu navegador:<br>
                  '.htmlspecialchars($link).'
                </p>
              </div>
            ';

            // Envía correo usando PHPMailer
            [$okMail, $msgMail] = enviarCorreo($correo, $nombre.' '.$apellido, $subject, $html);
      

            $msg = "✅ Cuenta creada. Revise su correo para activarla.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registro de Chofer - Rides</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- Bootstrap (estilos) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <!-- Tarjeta principal del formulario -->
      <div class="card shadow-lg border-0 rounded-4">
        <div class="card-header bg-primary text-white text-center py-3">
          <h3 class="mb-0">Registro de Chofer</h3>
        </div>
        <div class="card-body">
          <!-- Muestra validaciones de servidor -->
          <?php if ($err): ?>
            <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($err as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div>
          <?php endif; ?>
          <?php if (!empty($msg)): ?>
            <div class="alert alert-success text-center"><?= htmlspecialchars($msg) ?></div>
          <?php endif; ?>

          <!-- Formulario de registro de chofer -->
          <form method="post" enctype="multipart/form-data">
            <div class="mb-3"><label class="form-label">Nombre</label>
              <input name="nombre" type="text" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Apellido</label>
              <input name="apellido" type="text" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Número de cédula</label>
              <input name="cedula" type="text" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Fecha de nacimiento</label>
              <input name="fecha_nacimiento" type="date" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Correo electrónico</label>
              <input type="email" name="correo" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Número de teléfono</label>
              <input name="telefono" type="text" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Fotografía personal</label>
              <input type="file" name="foto" class="form-control" accept=".jpg,jpeg,png,webp,gif"></div>
            <div class="mb-3"><label class="form-label">Contraseña</label>
              <input type="password" name="password" class="form-control" minlength="4" required></div>
            <div class="mb-3"><label class="form-label">Repetir contraseña</label>
              <input type="password" name="password2" class="form-control" minlength="4" required></div>
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
