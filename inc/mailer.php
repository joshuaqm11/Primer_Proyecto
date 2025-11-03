// Realizado por Joshua Quesada y Fabio Oconitrillo
<?php

// Envío de correos usando PHPMailer con SMTP (Gmail).

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Incluye clases principales de PHPMailer
require_once __DIR__ . '/../vendor/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../vendor/PHPMailer/SMTP.php';
require_once __DIR__ . '/../vendor/PHPMailer/Exception.php';

// Configuración base de la app/SMTP
define('APP_NAME', 'Rides');          // Etiqueta/branding del remitente
define('SMTP_HOST', 'smtp.gmail.com'); // Host SMTP (Gmail)
define('SMTP_USER', 'prograweb1234@gmail.com'); // Usuario remitente (correo desde el que se envía)

// Contraseña de aplicación de Gmail
define('SMTP_PASS', 'sjaiprywfidpjmye');

define('SMTP_PORT', 587);        // Puerto SMTP
define('SMTP_SECURE', 'tls');    // Capa de seguridad

/**
 * Envía un correo HTML usando PHPMailer via SMTP.
 *
 * @param string $paraEmail  Correo del destinatario
 * @param string $paraNombre Nombre del destinatario
 * @param string $asunto     Asunto del correo
 * @param string $htmlBody   Cuerpo del correo en HTML
 * @param string $altBody    Cuerpo alternativo en texto plano
 * @return array             [bool ok, string mensaje]
 */
function enviarCorreo($paraEmail, $paraNombre, $asunto, $htmlBody, $altBody = '')
{
    $mail = new PHPMailer(true); // Instancia con excepciones activadas
    try {
        // === Config SMTP ===
        $mail->isSMTP();                         // Indica que se utilizará SMTP
        $mail->Host       = SMTP_HOST;           // Servidor SMTP
        $mail->SMTPAuth   = true;                // Autenticación SMTP
        $mail->Username   = SMTP_USER;           // Usuario (correo remitente)
        $mail->Password   = SMTP_PASS;           // Contraseña de app
        $mail->SMTPSecure = SMTP_SECURE;         // Seguridad TLS/SSL
        $mail->Port       = SMTP_PORT;           // Puerto correspondiente

        // Si hay problemas con certificados en Windows, se debe descomentar el bloque siguiente:
        // $mail->SMTPOptions = [
        //     'ssl' => [
        //         'verify_peer' => false,
        //         'verify_peer_name' => false,
        //         'allow_self_signed' => true
        //     ]
        // ];

        // === Encabezados y destinatarios ===
        // Remitente (quién envía)
        $mail->setFrom(SMTP_USER, APP_NAME . ' - Activación');
        // Responder a (reply-to)
        $mail->addReplyTo(SMTP_USER, APP_NAME);
        // Destinatario principal
        $mail->addAddress($paraEmail, $paraNombre ?: $paraEmail);

        // === Contenido ===
        $mail->isHTML(true);                     // Habilita HTML
        $mail->Subject = $asunto;                // Asunto
        $mail->Body    = $htmlBody;              // Cuerpo HTML
        $mail->AltBody = $altBody ?: strip_tags($htmlBody); // Alternativo en texto

        // Envía el correo
        $mail->send();
        return [true, 'Correo enviado'];
    } catch (Exception $e) {
        // Devuelve detalle desde PHPMailer ante fallo
        return [false, 'Error enviando correo: ' . $mail->ErrorInfo];
    }
}
