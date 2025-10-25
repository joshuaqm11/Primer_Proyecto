<?php
session_start();
$_SESSION = [];           // Vaciar la sesión
session_destroy();        // Destruir la sesión
header('Location: index.php'); // Redirigir al login
exit;

