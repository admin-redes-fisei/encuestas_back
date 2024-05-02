<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

$formularioId = $_GET['id'];

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
    $sql = "WITH autoincrement AS (
                SELECT s.sec_formulario_pertenece, f.for_nombre, r.otr_pregunta_pertenece, p.pre_alias, p.pre_titulo, p.pre_texto , @rownum := @rownum + 1 AS id_autoincremental, TRIM(UPPER(r.otr_respuesta_texto)) AS respuesta_texto_limpiado, COUNT(TRIM(UPPER(r.otr_respuesta_texto))) AS count_respuesta
                FROM opciones_otra r
                JOIN preguntas p ON p.pre_id = r.otr_pregunta_pertenece
                JOIN secciones s ON p.pre_seccion_pertenece = s.sec_id
                JOIN formularios f ON f.for_id = s.sec_formulario_pertenece
                JOIN 
                    (SELECT @rownum := 0) AS dummy
                WHERE f.for_id = ?
                GROUP BY s.sec_formulario_pertenece, r.otr_pregunta_pertenece, respuesta_texto_limpiado
                ORDER BY s.sec_formulario_pertenece, r.otr_pregunta_pertenece, respuesta_texto_limpiado
            )
            SELECT * FROM (
                SELECT s.sec_formulario_pertenece, f.for_nombre, re.res_pregunta_pertenece, p.pre_alias, p.pre_titulo, p.pre_texto, re.res_opcion_pertenece, re.res_texto, COUNT(re.res_opcion_pertenece) AS count_respuesta
                FROM respuestas re 
                JOIN preguntas p ON re.res_pregunta_pertenece = p.pre_id
                JOIN secciones s ON p.pre_seccion_pertenece = s.sec_id
                JOIN formularios f ON f.for_id = s.sec_formulario_pertenece
                WHERE f.for_id = ?
                GROUP BY s.sec_formulario_pertenece, re.res_pregunta_pertenece, re.res_opcion_pertenece, re.res_texto
                ORDER BY s.sec_formulario_pertenece, re.res_pregunta_pertenece, re.res_opcion_pertenece
            ) AS consulta_principal
            UNION ALL
            SELECT * FROM autoincrement;"
            ;

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ss", $formularioId,$formularioId);
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
