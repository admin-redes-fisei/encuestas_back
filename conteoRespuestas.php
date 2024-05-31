<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

$formularioId = $_GET['id'];

include 'conexion.php';

try {
    $sql = "WITH autoincrement AS (
                SELECT s.sec_formulario_pertenece, f.for_nombre, r.otr_pregunta_pertenece, p.pre_alias, p.pre_titulo, p.pre_texto , @rownum := @rownum + 1 AS id_autoincremental, TRIM(r.otr_respuesta_texto) AS respuesta_texto_limpiado, '' as opc_padre, COUNT(TRIM(r.otr_respuesta_texto)) AS count_respuesta
                FROM opciones_otra r
                JOIN preguntas p ON p.pre_id = r.otr_pregunta_pertenece
                JOIN secciones s ON p.pre_seccion_pertenece = s.sec_id
                JOIN formularios f ON f.for_id = s.sec_formulario_pertenece
                JOIN 
                    (SELECT @rownum := 0) AS dummy
                WHERE f.for_id = ? AND p.pre_estado = 1 AND s.sec_estado = 1
                GROUP BY s.sec_formulario_pertenece, r.otr_pregunta_pertenece, respuesta_texto_limpiado
                ORDER BY s.sec_formulario_pertenece, r.otr_pregunta_pertenece, respuesta_texto_limpiado
            )
            SELECT * FROM (
                SELECT s.sec_formulario_pertenece, f.for_nombre, re.res_pregunta_pertenece, p.pre_alias, p.pre_titulo, p.pre_texto, re.res_opcion_pertenece, re.res_texto, op.opc_padre, COUNT(re.res_opcion_pertenece) AS count_respuesta
                FROM respuestas re 
                JOIN preguntas p ON re.res_pregunta_pertenece = p.pre_id
                JOIN secciones s ON p.pre_seccion_pertenece = s.sec_id
                JOIN opciones op ON op.opc_id = re.res_opcion_pertenece
                JOIN formularios f ON f.for_id = s.sec_formulario_pertenece
                WHERE f.for_id = ? AND p.pre_estado = 1 AND s.sec_estado = 1
                GROUP BY s.sec_formulario_pertenece, re.res_pregunta_pertenece, re.res_opcion_pertenece, re.res_texto, op.opc_padre
                ORDER BY s.sec_formulario_pertenece, re.res_pregunta_pertenece, re.res_opcion_pertenece
            ) AS consulta_principal
            UNION ALL
            SELECT * FROM autoincrement;"
            ;

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$formularioId, $formularioId]);
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
