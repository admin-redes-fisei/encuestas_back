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

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    // Verificar si se proporcionaron los parámetros necesarios
    if (isset($_GET['facultad_id']) && isset($_GET['tipo_formulario'])) {
        $facultad_id = $_GET['facultad_id'];
        $tipo_formulario = $_GET['tipo_formulario'];

        // Prepara la consulta SQL con los parámetros proporcionados
        $consulta = $pdo->prepare("SELECT for_id
        FROM formularios
        WHERE for_facultad_pertenece = :facultad_id AND for_tipo = :tipo_formulario");
        $consulta->bindParam(':facultad_id', $facultad_id);
        $consulta->bindParam(':tipo_formulario', $tipo_formulario);
        $consulta->execute();

        // Obtiene el resultado de la consulta
        $formulario = $consulta->fetch(PDO::FETCH_ASSOC);

        // Verifica si se encontró el formulario
        if ($formulario) {
            // Devuelve el for_id como JSON
            echo json_encode($formulario);
        } else {
            // No se encontró el formulario con las características especificadas
            http_response_code(404); // Not Found
            echo json_encode(array("mensaje" => "Formulario no encontrado"));
        }
    } else {
        // Parámetros faltantes
        http_response_code(400); // Bad Request
        echo json_encode(array("mensaje" => "Faltan parámetros"));
    }
} else {
    // Método no permitido
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("mensaje" => "Método no permitido"));
}
?>
