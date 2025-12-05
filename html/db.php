<?php
// db.php
$host = "db";       // <--- IMPORTANTE: Antes era "localhost", ahora es el nombre del servicio en Docker
$usuario = "root";
$password = "root"; // La contraseña que pusimos en el docker-compose
$base_datos = "mi_proyecto";

$conn = new mysqli($host, $usuario, $password, $base_datos);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>