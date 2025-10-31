<?php
// inc/mailer.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../vendor/PHPMailer/SMTP.php';
require_once __DIR__ . '/../vendor/PHPMailer/Exception.php';

// Ajusta tu marca + dominio base si quieres
define('APP_NAME', 'Rides');
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'prograweb1234@gmail.com');
// ¡SIN ESPACIOS!
define('SMTP_PASS', 'sjaiprywfidpjmye');
define('SMTP_PORT', 587);        // TLS 587 (o 465 para SSL)
define('SMTP_SECURE', 'tls');    // 'tls' o 'ssl'

function enviarCorreo($paraEmail, $paraNombre, $asunto, $htmlBody, $altBody = '')
{
    $mail = new PHPMailer(true);
    try {
        // SMTP
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;  // 'tls' o 'ssl'
        $mail->Port       = SMTP_PORT;    // 587 o 465

        // Opcional: si hay problemas con certificados en Windows:
        // $mail->SMTPOptions = [
        //     'ssl' => [
        //         'verify_peer' => false,
        //         'verify_peer_name' => false,
        //         'allow_self_signed' => true
        //     ]
        // ];

        // Remitente
        $mail->setFrom(SMTP_USER, APP_NAME . ' - Activación');
        $mail->addReplyTo(SMTP_USER, APP_NAME);

        // Destinatario
        $mail->addAddress($paraEmail, $paraNombre ?: $paraEmail);

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $altBody ?: strip_tags($htmlBody);

        $mail->send();
        return [true, 'Correo enviado'];
    } catch (Exception $e) {
        return [false, 'Error enviando correo: ' . $mail->ErrorInfo];
    }
}

