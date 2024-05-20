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
    $sec_id = $datos['sec_id'];

    // Prepara la consulta SQL para verificar si existen respuestas relacionadas
    $consultaVerificacion = $pdo->prepare("
        SELECT COUNT(*) FROM respuestas r
        JOIN preguntas p ON r.res_pregunta_pertenece = p.pre_id
        WHERE p.pre_seccion_pertenece = :sec_id
    ");

    // Enlaza los parámetros
    $consultaVerificacion->bindParam(':sec_id', $sec_id);

    // Ejecuta la consulta de verificación
    $consultaVerificacion->execute();
    $numRespuestas = $consultaVerificacion->fetchColumn();

    if ($numRespuestas == 0) {
        // Prepara la consulta SQL para el UPDATE
        $consultaActualizacion = $pdo->prepare("UPDATE secciones SET sec_eliminado = 1 WHERE sec_id = :sec_id");

        // Enlaza los parámetros
        $consultaActualizacion->bindParam(':sec_id', $sec_id);

        // Ejecuta la consulta de actualización
        if ($consultaActualizacion->execute()) {
            http_response_code(201); // Created
            echo json_encode(array("mensaje" => "OK"));
        } else {
            http_response_code(500); // Internal Server Error
            echo json_encode(array("error" => "Error al eliminar la seccion"));
        }
    } else {
        // Hay respuestas relacionadas, no se puede eliminar la sección
        http_response_code(400); // Bad Request
        echo json_encode(array("error" => "No se puede eliminar la sección porque tiene respuestas relacionadas"));
    }
} else {
    // Método no permitido
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("error" => "Método no permitido"));
}

?>
