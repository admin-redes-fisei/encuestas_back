<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

// Recibe el ID del formulario
$formularioId = $_GET['alias'];

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

    // Verificar la conexión
    if ($mysqli->connect_error) {
        die("Error en la conexión a la base de datos: " . $mysqli->connect_error);
    }

    // Consulta SQL para obtener las preguntas y opciones de respuesta del formulario específico
    $sql = "SELECT f.for_id, s.sec_nombre AS seccion, p.pre_id AS id, p.pre_numero AS question_number, p.pre_alias, p.pre_titulo AS title, p.pre_texto AS question, p.pre_es_obligatoria as requerida, 
                COALESCE(JSON_ARRAYAGG(JSON_OBJECT('id', o.opc_id,'question_option', o.opc_numero,'name', 
                    p.pre_alias,'label', o.opc_label, 'padre', o.opc_padre, 'tooltip_texto', 
                    o.opc_tooltip_texto, 'tooltip_img', o.opc_tooltip_imagen)), NULL) AS options,
                p.pre_tipo AS questionType, p.pre_url_imagen, p.pre_tipo_imagen, p.pre_tooltip_texto,
                p.pre_tooltip_imagen, p.pre_es_abierta AS isOpenQuestion, p.pre_estado
            FROM encuestasdb.preguntas p 
            LEFT JOIN encuestasdb.opciones o ON p.pre_id = o.opc_pregunta_pertenece 
            JOIN encuestasdb.secciones s ON p.pre_seccion_pertenece = s.sec_id
            JOIN encuestasdb.formularios f ON s.sec_formulario_pertenece = f.for_id
            WHERE p.pre_eliminado = 0 AND (o.opc_eliminado = 0 OR o.opc_eliminado IS NULL) AND f.for_alias = ? 
            GROUP BY p.pre_id, p.pre_numero, p.pre_titulo, p.pre_alias, p.pre_tipo 
            ORDER BY s.sec_numero, p.pre_numero;";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $formularioId);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);

    // Decodificar las cadenas JSON en la respuesta
    foreach ($rows as &$row) {
        $row['options'] = json_decode($row['options']);
    }

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
