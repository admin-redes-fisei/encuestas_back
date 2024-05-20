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
    $consulta = $pdo->prepare("UPDATE opciones SET opc_numero = :opc_numero, opc_label = :opc_label, 
    opc_padre = :opc_padre, opc_tooltip_texto = :opc_tooltip_texto, opc_tooltip_imagen = :opc_tooltip_imagen,
    opc_pregunta_pertenece = :opc_pregunta_pertenece
    WHERE opc_id = :opc_id");

    // Enlaza los parámetros
    $consulta->bindParam(':opc_numero', $datos['opc_numero']);
    $consulta->bindParam(':opc_label', $datos['opc_label']);
    $consulta->bindParam(':opc_padre', $datos['opc_padre']);
    $consulta->bindParam(':opc_tooltip_texto', $datos['opc_tooltip_texto']);
    $consulta->bindParam(':opc_tooltip_imagen', $datos['opc_tooltip_imagen']);
    $consulta->bindParam(':opc_pregunta_pertenece', $datos['opc_pregunta_pertenece']);
    $consulta->bindParam(':opc_id', $datos['opc_id']);

    // Ejecuta la consulta
    if ($consulta->execute()) {
        http_response_code(201); // Created
        echo json_encode(array("mensaje" => "OK"));
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(array("error" => "Error al editar la opcion"));
    }
} else {
    // Método no permitido
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("error" => "Método no permitido"));
}

?>
