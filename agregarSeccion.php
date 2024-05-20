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
    $consulta = $pdo->prepare("INSERT INTO secciones (sec_numero, sec_nombre, sec_estado, sec_eliminado, sec_formulario_pertenece, sec_fecha_creacion) 
    VALUES (:sec_numero, :sec_nombre, 1, 0, :sec_formulario_pertenece, NOW())");

    // Enlaza los parámetros
    $consulta->bindParam(':sec_numero', $datos['sec_numero']);
    $consulta->bindParam(':sec_nombre', $datos['sec_nombre']);
    $consulta->bindParam(':sec_formulario_pertenece', $datos['sec_formulario_pertenece']);

    // Ejecuta la consulta
    if ($consulta->execute()) {
        http_response_code(201); // Created
        echo json_encode(array("mensaje" => "OK"));
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(array("error" => "Error al crear la carrera"));
    }
} else {
    // Método no permitido
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("error" => "Método no permitido"));
}

?>
