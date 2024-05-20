<?php
include 'conexion.php';

// Habilitar CORS
header('Access-Control-Allow-Origin: *');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    http_response_code(200);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtiene los datos enviados por el cliente
    $datos = json_decode(file_get_contents("php://input"), true);

    // Prepara la consulta SQL para el INSERT
    $consulta = $pdo->prepare("UPDATE secciones SET sec_nombre = :sec_nombre, sec_numero = :sec_numero,
    sec_estado = :sec_estado WHERE sec_id = :sec_id");

    // Enlaza los parámetros
    $consulta->bindParam(':sec_nombre', $datos['sec_nombre']);
    $consulta->bindParam(':sec_numero', $datos['sec_numero']);
    $consulta->bindParam(':sec_estado', $datos['sec_estado']);
    $consulta->bindParam(':sec_id', $datos['sec_id']);

    // Ejecuta la consulta
    if ($consulta->execute()) {
        http_response_code(201); // Created
        echo json_encode(array("mensaje" => "OK"));
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(array("error" => "Error al eliminar la facultad"));
    }
} else {
    // Método no permitido
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("error" => "Método no permitido"));
}

?>
