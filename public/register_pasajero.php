// Realizado por Joshua Quesada y Fabio Oconitrillo
<?php
require_once '../inc/funciones.php';
require_once '../inc/mailer.php'; // Usa PHPMailer mediante la función helper enviarCorreo()

$tipo = 'pasajero'; // Rol fijo que se registrará con este formulario
$msg = '';
$err = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $mysqli;

    // === Captura de datos del formulario ===
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

    /* 2) Verifica si ya existe un usuario con ese correo (evita duplicados) */
    if (!$err) {
        $stmt = $mysqli->prepare("SELECT 1 FROM usuarios WHERE correo = ? LIMIT 1");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $err[] = "El correo ya está registrado. Intente con otro.";
        }
        $stmt->close();
    }

    /* Si hubo errores hasta aquí, no crea la cuenta */
    if (!$err) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT); // Hash seguro

        /* 3) Inserta el usuario con estado 'pendiente' */
        $stmt = $mysqli->prepare("
            INSERT INTO usuarios
            (nombre, apellido, cedula, fecha_nacimiento, correo, telefono, password_hash, tipo, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')
        ");
        $stmt->bind_param("ssssssss", $nombre, $apellido, $cedula, $fecha_n, $correo, $telefono, $password_hash, $tipo);

        $uid = null;
        try {
            $stmt->execute();
            $uid = $stmt->insert_id; // ID del nuevo usuario
        } catch (mysqli_sql_exception $e) {
            if ((int)$e->getCode() === 1062) {
                $err[] = "El correo ya está registrado. Intente con otro.";
            } else {
                throw $e;
            }
        } finally {
            $stmt->close();
        }

        /* Si el INSERT fue correcto, continúa con foto opcional y token de activación */
        if (!$err) {
            /* 4) Subida de foto */
            if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $maxBytes = 3 * 1024 * 1024;
                if ($_FILES['foto']['size'] <= $maxBytes && is_uploaded_file($_FILES['foto']['tmp_name'])) {

                    // Detección de tipo de imagen segura
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
                        // Carpeta destino y nombre único
                        $absDir = 'C:\\ISW-613\\httpdocs\\Primer_proyecto\\uploads\\fotos_usuarios';
                        if (!is_dir($absDir)) { @mkdir($absDir, 0775, true); }

                        $filename = $uid . '_' . time() . '.' . $ext;
                        $absPath  = $absDir . DIRECTORY_SEPARATOR . $filename;

                        if (move_uploaded_file($_FILES['foto']['tmp_name'], $absPath)) {
                            // Guarda ruta relativa para servir por HTTP
                            $relativePath = 'uploads/fotos_usuarios/' . $filename;

                            $stmt = $mysqli->prepare("UPDATE usuarios SET foto = ? WHERE id = ?");
                            $stmt->bind_param("si", $relativePath, $uid);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }
            }

            /* 5) Genera token de activación + envía correo con enlace absoluto */
            $token = bin2hex(random_bytes(16));
            $stmt = $mysqli->prepare("INSERT INTO activation_tokens (usuario_id, token) VALUES (?, ?)");
            $stmt->bind_param("is", $uid, $token);
            $stmt->execute();
            $stmt->close();

            $BASE_URL = base_url_public(); // Construye base
            $link = $BASE_URL . "/activate.php?token=" . urlencode($token);

            // Cuerpo del correo de activación (HTML)
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

            // Envía el correo (si falla, no interrumpe el flujo de registro)
            [$okMail, $msgMail] = enviarCorreo($correo, $nombre.' '.$apellido, $subject, $html);
            // if (!$okMail) error_log($msgMail);

            $msg = "✅ Cuenta creada. Revise su correo para activarla.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registro de Pasajero - Rides</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <!-- Card principal del formulario -->
      <div class="card shadow-lg border-0 rounded-4">
        <div class="card-header bg-primary text-white text-center py-3">
          <h3 class="mb-0">Registro de Pasajero</h3>
        </div>
        <div class="card-body">
          <!-- Muestra errores de validación -->
          <?php if ($err): ?>
            <div class="alert alert-danger"><ul><?php foreach ($err as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div>
          <?php endif; ?>
          <!-- Mensaje de éxito -->
          <?php if (!empty($msg)): ?>
            <div class="alert alert-success text-center"><?= htmlspecialchars($msg) ?></div>
          <?php endif; ?>

          <!-- Formulario de registro -->
          <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
              <label class="form-label">Nombre</label>
              <input name="nombre" type="text" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Apellido</label>
              <input name="apellido" type="text" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Número de cédula</label>
              <input name="cedula" type="text" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Fecha de nacimiento</label>
              <input name="fecha_nacimiento" type="date" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Correo electrónico</label>
              <input type="email" name="correo" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Número de teléfono</label>
              <input name="telefono" type="text" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Fotografía personal</label>
              <input type="file" name="foto" class="form-control" accept=".jpg,jpeg,png,webp,gif">
            </div>
            <div class="mb-3">
              <label class="form-label">Contraseña</label>
              <input type="password" name="password" class="form-control" minlength="4" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Repetir contraseña</label>
              <input type="password" name="password2" class="form-control" minlength="4" required>
            </div>
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
