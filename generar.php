// Realizado por Joshua Quesada y Fabio Oconitrillo
<?php
// Genera un hash seguro de la contraseña "1234" usando BCRYPT.
// Útil para insertar contraseñas en la base de datos
// Cada ejecución generará un hash distinto debido al salt aleatorio incorporado.

echo password_hash('1234', PASSWORD_BCRYPT);
