<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

try {
    // Conexión a la base de datos MySQL
    //$servername = "172.21.123.36";
    //$username = "laravel";
    //$password = "HatunSoft@2023";
    //$dbname = "encuestasdb";
    $servername = "localhost";
    $port = "3308";
    $username = "root";
    $password = "";
    $dbname = "encuestasdb";

    $mysqli = new mysqli($servername, $username, $password, $dbname, $port);
    //$mysqli = new mysqli($servername, $username, $password, $dbname);

    // Verificar la conexión
    if ($mysqli->connect_error) {
        die("Error en la conexión a la base de datos: " . $mysqli->connect_error);
    }

    // Consulta SQL para obtener las preguntas y opciones de respuesta del formulario específico
    $sql = "SELECT f.for_id, f.for_nombre, COUNT(DISTINCT r.res_encuestado_id) AS cantidad_respuestas
            FROM formularios f
            JOIN respuestas r ON r.res_formulario_pertenece = f.for_id
            GROUP BY f.for_id;";

    $stmt = $mysqli->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);

    // Cerrar la conexión a la base de datos
    $stmt->close();
    $mysqli->close();

    // Respuesta con las preguntas en formato JSON
    http_response_code(200);
    echo json_encode($rows);

} catch (Exception $e) {
    // Error al obtener las preguntas
    http_response_code(500);
    echo json_encode(["error" => "Error al obtener las preguntas"]);
}

?>
