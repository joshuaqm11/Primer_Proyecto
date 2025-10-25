<?php
require_once '../inc/funciones.php';
if (!isset($_GET['token'])) die("Token no recibido.");

$token = $_GET['token'];
$stmt = $mysqli->prepare("SELECT usuario_id FROM activation_tokens WHERE token=?");
$stmt->bind_param("s", $token);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) die("Token inválido.");

$id = $row['usuario_id'];
$mysqli->query("UPDATE usuarios SET estado='activo' WHERE id=$id");
$mysqli->query("DELETE FROM activation_tokens WHERE usuario_id=$id");

echo "✅ Cuenta activada. <a href='index.php'>Ingresar</a>";

