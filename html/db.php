<?php
// db.php

// 1. Intentamos leer las "Variables de Entorno" de Render (La nube)
$host = getenv('DB_HOST');
$usuario = getenv('DB_USER');
$password = getenv('DB_PASS');
$base_datos = getenv('DB_NAME');

// 2. Si las variables están vacías (significa que estás en tu PC local), usamos los valores de siempre
if (!$host) {
    $host = "db";           // O "localhost" si no usas docker-compose
    $usuario = "root";
    $password = "root";
    $base_datos = "mi_proyecto";
}

// 3. Crear la conexión
$conn = new mysqli($host, $usuario, $password, $base_datos);

// 4. Verificar errores
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>