<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "parqueadero_db";

// Crear conexión
$conn = new mysqli($host, $user, $password, $database);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

echo "Conexión exitosa";

// Cerrar conexión
$conn->close();
?>