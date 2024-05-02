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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtiene los datos enviados por el cliente
    $datos = json_decode(file_get_contents("php://input"), true);

    // Genera el hash de la contraseña
    $hashed_password = password_hash($datos['usu_clave'], PASSWORD_DEFAULT);

    // Prepara la consulta SQL para el INSERT
    $consulta = $pdo->prepare("UPDATE usuarios SET usu_clave = :usu_clave WHERE usu_id = :usu_id");

    // Enlaza los parámetros
    $consulta->bindParam(':usu_clave', $hashed_password);
    $consulta->bindParam(':usu_id', $datos['usu_id']);

    // Ejecuta la consulta
    if ($consulta->execute()) {
        http_response_code(201); // Created
        echo json_encode(array("mensaje" => "OK"));
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(array("error" => "Error al editar el usuario"));
    }
} else {
    // Método no permitido
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("error" => "Método no permitido"));
}

?>
