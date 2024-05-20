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

    // Consulta para verificar si existen relaciones en las tablas respuestas, opciones_otra, y opciones
    $consultaRespuestas = $pdo->prepare("SELECT COUNT(*) as total FROM respuestas WHERE res_pregunta_pertenece = :pre_id");
    $consultaRespuestas->bindParam(':pre_id', $datos['pre_id']);
    $consultaRespuestas->execute();
    $resultadoRespuestas = $consultaRespuestas->fetch(PDO::FETCH_ASSOC);

    $consultaOpcionesOtra = $pdo->prepare("SELECT COUNT(*) as total FROM opciones_otra WHERE otr_pregunta_pertenece = :pre_id");
    $consultaOpcionesOtra->bindParam(':pre_id', $datos['pre_id']);
    $consultaOpcionesOtra->execute();
    $resultadoOpcionesOtra = $consultaOpcionesOtra->fetch(PDO::FETCH_ASSOC);

    $consultaOpciones = $pdo->prepare("SELECT COUNT(*) as total FROM opciones WHERE opc_pregunta_pertenece = :pre_id");
    $consultaOpciones->bindParam(':pre_id', $datos['pre_id']);
    $consultaOpciones->execute();
    $resultadoOpciones = $consultaOpciones->fetch(PDO::FETCH_ASSOC);

    if ($resultadoRespuestas['total'] == 0 && $resultadoOpcionesOtra['total'] == 0 && $resultadoOpciones['total'] == 0) {
        // Si no existen relaciones, realiza un UPDATE para marcar la pregunta como eliminada
        $consulta = $pdo->prepare("UPDATE preguntas SET pre_eliminado = 1 WHERE pre_id = :pre_id");
    } else {
        // Si existen relaciones, devuelve un error
        http_response_code(400); // Bad Request
        echo json_encode(array("error" => "No se puede eliminar la pregunta porque tiene relaciones en otras tablas"));
        exit();
    }

    // Enlaza los parámetros
    $consulta->bindParam(':pre_id', $datos['pre_id']);

    // Ejecuta la consulta
    if ($consulta->execute()) {
        http_response_code(201); // Created
        echo json_encode(array("mensaje" => "OK"));
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(array("error" => "Error al eliminar la pregunta"));
    }
} else {
    // Método no permitido
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("error" => "Método no permitido"));
}
?>
