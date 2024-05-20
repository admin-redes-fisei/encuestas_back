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
    $consulta = $pdo->prepare("UPDATE facultades SET fac_nombre = :fac_nombre, fac_siglas = :fac_siglas,
    fac_estado = :fac_estado WHERE fac_id = :fac_id");

    // Enlaza los parámetros
    $consulta->bindParam(':fac_nombre', $datos['fac_nombre']);
    $consulta->bindParam(':fac_siglas', $datos['fac_siglas']);
    $consulta->bindParam(':fac_estado', $datos['fac_estado']);
    $consulta->bindParam(':fac_id', $datos['fac_id']);

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
