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

    if (isset($_GET['formulario_id'])) {
        $formulario_id = $_GET['formulario_id'];

        // Consulta para obtener los datos del formulario
        $consultaFormulario = $pdo->prepare("SELECT for_alias FROM formularios
        WHERE for_id != :formulario_id");
        $consultaFormulario->bindParam(':formulario_id', $formulario_id);
        $consultaFormulario->execute();
        $formulario = $consultaFormulario->fetchAll(PDO::FETCH_ASSOC);

        // Devuelve los resultados como JSON
        http_response_code(200);
        echo json_encode($formulario);
    } else {
        echo json_encode(array("mensaje" => "ID de formulario no proporcionado"));
    }
} else {
    // Método no permitido
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("mensaje" => "Método no permitido"));
}

?>
