<?php

$host = getenv('DB_HOST');
$usuario = getenv('DB_USER');
$password = getenv('DB_PASS');
$base_datos = getenv('DB_NAME');

if (!$host) {
    $host = "db";           
    $usuario = "root";
    $password = "root";
    $base_datos = "mi_proyecto";
}

$conn = new mysqli($host, $usuario, $password, $base_datos);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>