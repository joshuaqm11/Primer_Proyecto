// Realizado por Joshua Quesada y Fabio Oconitrillo
<?php
session_start();             // Inicia la sesión para poder manipularla
$_SESSION = [];              // Vaciar todas las variables de la sesión
session_destroy();           // Destruir la sesión actual en el servidor
header('Location: index.php'); // Redirigir al login
exit;                        // Terminar ejecución inmediatamente
