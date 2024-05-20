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

    // Consulta para verificar si existen respuestas relacionadas con la opción
    $consultaRespuestas = $pdo->prepare("SELECT COUNT(*) as total FROM respuestas WHERE res_opcion_pertenece = :opc_id");
    $consultaRespuestas->bindParam(':opc_id', $datos['opc_id']);
    $consultaRespuestas->execute();
    $resultado = $consultaRespuestas->fetch(PDO::FETCH_ASSOC);

    if ($resultado['total'] > 0) {
        // Si existen respuestas, realiza un UPDATE
        $consulta = $pdo->prepare("UPDATE opciones SET opc_eliminado = 1 WHERE opc_id = :opc_id");
    } else {
        // Si no existen respuestas, realiza un DELETE
        $consulta = $pdo->prepare("DELETE FROM opciones WHERE opc_id = :opc_id");
    }

    // Enlaza los parámetros
    $consulta->bindParam(':opc_id', $datos['opc_id']);

    // Ejecuta la consulta
    if ($consulta->execute()) {
        http_response_code(201); // Created
        echo json_encode(array("mensaje" => "OK"));
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(array("error" => "Error al eliminar la opción"));
    }
} else {
    // Método no permitido
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("error" => "Método no permitido"));
}
?>
