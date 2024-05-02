<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

include 'conexion.php';

try {
    // Consulta SQL para obtener las preguntas y opciones de respuesta del formulario específico
    $sql = "SELECT f.for_id, f.for_nombre, COUNT(DISTINCT r.res_encuestado_id) AS cantidad_respuestas
            FROM formularios f
            JOIN respuestas r ON r.res_formulario_pertenece = f.for_id
            GROUP BY f.for_id;";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Respuesta con las preguntas en formato JSON
    http_response_code(200);
    echo json_encode($rows);

} catch (PDOException $e) {
    // Error al obtener las preguntas
    http_response_code(500);
    echo json_encode(["error" => "Error al obtener las preguntas"]);
}
?>
