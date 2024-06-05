<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

include 'conexion.php';

try {
    // Verificar si se proporcionó el ID de la facultad en la solicitud
    if (isset($_GET['facultad_id'])) {
        $facultad_id = $_GET['facultad_id'];

        // Consulta SQL para obtener las preguntas y opciones de respuesta del formulario específico de la facultad
        $sql = "SELECT f.for_id, f.for_nombre, COUNT(DISTINCT r.res_encuestado_id) AS cantidad_respuestas
                FROM formularios f
                LEFT JOIN respuestas r ON r.res_formulario_pertenece = f.for_id
                WHERE f.for_facultad_pertenece = :facultad_id AND f.for_estado = 1
                GROUP BY f.for_id";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':facultad_id', $facultad_id);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Respuesta con los formularios de la facultad en formato JSON
        http_response_code(200);
        echo json_encode($rows);
    } else {
        // ID de facultad faltante en la solicitud
        http_response_code(400);
        echo json_encode(["error" => "ID de facultad faltante en la solicitud"]);
    }
} catch (PDOException $e) {
    // Error al obtener los formularios de la facultad
    http_response_code(500);
    echo json_encode(["error" => "Error al obtener los formularios de la facultad"]);
}
?>
