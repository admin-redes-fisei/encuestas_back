<?php
// Incluye la conexión a la base de datos
include 'conexion.php';

// Habilitar CORS
header('Access-Control-Allow-Origin: *');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    http_response_code(200);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    // Prepara la consulta SQL
    $consulta = $pdo->prepare("SELECT * FROM usuarios WHERE usu_eliminado = 0");
    $consulta->execute();

    // Obtiene los resultados de la consulta
    $usuarios = $consulta->fetchAll(PDO::FETCH_ASSOC);

    // Devuelve los resultados como JSON
    echo json_encode($usuarios);
} else {
    // Método no permitido
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("mensaje" => "Método no permitido"));
}

?>