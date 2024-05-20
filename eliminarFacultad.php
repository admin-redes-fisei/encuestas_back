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

    // Verificar la existencia de relaciones con carreras o usuarios
    $consultaRelaciones = $pdo->prepare("SELECT COUNT(*) AS total_relaciones FROM carreras WHERE car_facultad_pertenece = :fac_id OR 
    EXISTS (SELECT 1 FROM usuarios WHERE usu_facultad_pertenece = :fac_id)");
    $consultaRelaciones->bindParam(':fac_id', $datos['fac_id']);
    $consultaRelaciones->execute();
    $resultadoRelaciones = $consultaRelaciones->fetch(PDO::FETCH_ASSOC);

    if ($resultadoRelaciones['total_relaciones'] == 0) {
        $consulta = $pdo->prepare("UPDATE facultades SET fac_eliminado = 1 WHERE fac_id = :fac_id");
        $consulta->bindParam(':fac_id', $datos['fac_id']);
        $consulta->execute(); 

        // Eliminar formularios relacionados
        $consultaEliminarFormularios = $pdo->prepare("DELETE FROM formularios WHERE for_facultad_pertenece = :fac_id");
        $consultaEliminarFormularios->bindParam(':fac_id', $datos['fac_id']);
        $consultaEliminarFormularios->execute();

        http_response_code(200); // OK
        echo json_encode(array("mensaje" => "OK"));
    } else {
        // Hay relaciones, no se puede eliminar
        http_response_code(400); // Bad Request
        echo json_encode(array("error" => "No se puede eliminar la facultad porque existen relaciones con carreras o usuarios"));
    }
} else {
    // Método no permitido
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("error" => "Método no permitido"));
}

?>
