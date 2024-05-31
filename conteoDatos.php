<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

$id = $_GET['id'];

include 'conexion.php';

try {
    $filters = isset($_GET['filter']) ? explode(',', $_GET['filter']) : [];
    $filteredData = [];

    if (!empty($filters)) {
        foreach ($filters as $filter) {
            $sqlEncuesta = "SELECT for_nombre, COUNT(DISTINCT r.res_encuestado_id) AS total_encuestados
                            FROM respuestas r
                            JOIN formularios f ON f.for_id = r.res_formulario_pertenece
                        LEFT JOIN opciones_otra op ON r.res_encuestado_id = op.otr_encuestado_id
                        WHERE r.res_formulario_pertenece = :id AND (r.res_texto = :filter OR TRIM(op.otr_respuesta_texto) = :filter )
                        GROUP BY f.for_nombre;";
            $stmtEncuesta = $pdo->prepare($sqlEncuesta);
            $stmtEncuesta->bindParam(':id', $id);
            $stmtEncuesta->bindParam(':filter', $filter);
            $stmtEncuesta->execute();
            $encuestaInfo = $stmtEncuesta->fetch(PDO::FETCH_ASSOC);
            $filteredData[] = [
                "nombre_encuesta" => $encuestaInfo['for_nombre'],
                "total_encuestados" => $encuestaInfo['total_encuestados'],
                "preguntas" => []
            ];
        }
    }else{
        $sqlEncuesta = "SELECT for_nombre, COUNT(DISTINCT r.res_encuestado_id) AS total_encuestados
                            FROM respuestas r
                            JOIN formularios f ON f.for_id = r.res_formulario_pertenece 
                            WHERE r.res_formulario_pertenece = :id 
                            GROUP BY f.for_nombre;";
        $stmtEncuesta = $pdo->prepare($sqlEncuesta);
        $stmtEncuesta->bindParam(':id', $id);
        $stmtEncuesta->execute();
        $encuestaInfo = $stmtEncuesta->fetch(PDO::FETCH_ASSOC);
        
        $filteredData[] = [
            "nombre_encuesta" => $encuestaInfo['for_nombre'],
            "total_encuestados" => $encuestaInfo['total_encuestados'],
            "preguntas" => []
        ];
    }
    

    // Obtener preguntas y respuestas
    $sqlPreguntas = "WITH respuestas_filtradas AS (
                        SELECT DISTINCT res_encuestado_id
                        FROM respuestas";
    $sqlPreguntas .= isset($_GET['filter']) ? "
    WHERE res_texto = :filter " : "";
    $sqlPreguntas .= " ), otras_filtradas AS (
                        SELECT DISTINCT otr_encuestado_id
                        FROM opciones_otra ";
    $sqlPreguntas .= isset($_GET['filter']) ? "
    WHERE TRIM(otr_respuesta_texto) = :filter " : "";
    $sqlPreguntas .= " ), autoincrement AS (
                        SELECT s.sec_formulario_pertenece, f.for_nombre, r.otr_pregunta_pertenece AS id_pregunta, p.pre_alias, p.pre_titulo, p.pre_texto, p.pre_tipo , @rownum := @rownum + 1 AS id_opcion, TRIM(r.otr_respuesta_texto) AS respuesta_texto, '' as opc_padre, COUNT(TRIM(r.otr_respuesta_texto)) AS count_respuesta
                        FROM opciones_otra r
                        JOIN preguntas p ON p.pre_id = r.otr_pregunta_pertenece
                        JOIN secciones s ON p.pre_seccion_pertenece = s.sec_id
                        JOIN formularios f ON f.for_id = s.sec_formulario_pertenece
                        JOIN 
                            (SELECT @rownum := 0) AS dummy
                        WHERE f.for_id = :id AND (r.otr_encuestado_id IN (SELECT res_encuestado_id FROM respuestas_filtradas) OR 
                        r.otr_encuestado_id IN (SELECT otr_encuestado_id FROM otras_filtradas))
                        GROUP BY s.sec_formulario_pertenece, r.otr_pregunta_pertenece, respuesta_texto
                        ORDER BY s.sec_formulario_pertenece, r.otr_pregunta_pertenece, respuesta_texto
                    )
                    SELECT * FROM (
                        SELECT s.sec_formulario_pertenece, f.for_nombre, re.res_pregunta_pertenece AS id_pregunta, p.pre_alias, p.pre_titulo, p.pre_texto, p.pre_tipo, re.res_opcion_pertenece AS id_opcion, re.res_texto AS respuesta_texto, op.opc_padre, COUNT(re.res_opcion_pertenece) AS count_respuesta
                        FROM respuestas re 
                        JOIN preguntas p ON re.res_pregunta_pertenece = p.pre_id
                        JOIN secciones s ON p.pre_seccion_pertenece = s.sec_id
                        JOIN opciones op ON op.opc_id = re.res_opcion_pertenece
                        JOIN formularios f ON f.for_id = s.sec_formulario_pertenece
                        WHERE f.for_id = :id AND (re.res_encuestado_id IN (SELECT res_encuestado_id FROM respuestas_filtradas) OR 
                        re.res_encuestado_id IN (SELECT otr_encuestado_id FROM otras_filtradas))
                        GROUP BY s.sec_formulario_pertenece, re.res_pregunta_pertenece, re.res_opcion_pertenece, re.res_texto, op.opc_padre
                        ORDER BY s.sec_formulario_pertenece, re.res_pregunta_pertenece, re.res_opcion_pertenece
                    ) AS consulta_principal
                    UNION ALL
                    SELECT * FROM autoincrement 
                    ORDER BY id_pregunta;";
    
    $stmtPreguntas = $pdo->prepare($sqlPreguntas);
    $stmtPreguntas->bindParam(':id', $id);
    if (isset($_GET['filter'])) {
        $filter = $_GET['filter'];
        $stmtPreguntas->bindParam(':filter', $_GET['filter']);
    }
    $stmtPreguntas->execute();
    $preguntasRespuestas = $stmtPreguntas->fetchAll(PDO::FETCH_ASSOC);

    // Estructurar la respuesta en formato JSON
    $response = [
        "nombre_encuesta" => $encuestaInfo['for_nombre'],
        "total_encuestados" => $encuestaInfo['total_encuestados'],
        "preguntas" => []
    ];

    $currentPregunta = null; 
    $opciones = [];
    foreach ($preguntasRespuestas as $row) {
        if ($row['id_pregunta'] != $currentPregunta) {
            if ($currentPregunta !== null) {
                $lastIndex = count($response["preguntas"]) - 1;
                $response["preguntas"][$lastIndex]["opciones"] = $opciones;
            }
            // Nueva pregunta encontrada, agregarla al array de preguntas
            $pregunta = [
                "id_pregunta" => $row['id_pregunta'],
                "alias_pregunta" => $row['pre_alias'],
                "titulo_pregunta" => $row['pre_titulo'],
                "texto_pregunta" => $row['pre_texto'],
                "tipo_pregunta" => $row['pre_tipo'],
                "opciones" => []
            ];
            $response["preguntas"][] = $pregunta;

            $currentPregunta = $row['id_pregunta'];
            $opciones = [];
        }

        $opcion = [
            "id_opcion" => $row['id_opcion'],
            "texto_opcion" => $row['respuesta_texto'],
            "padre_opcion" => $row['opc_padre'],
            "numero_selecciones" => $row['count_respuesta']
        ];
        $opciones[] = $opcion;
        
        // Estructura de cada opción de respuesta
    }
    
    $lastIndex = count($response["preguntas"]) - 1;
    $response["preguntas"][$lastIndex]["opciones"] = $opciones;
    

    // Respuesta con las preguntas en formato JSON
    http_response_code(200);
    echo json_encode($response);

} catch (PDOException $e) {
    // Error al obtener los datos de la encuesta
    http_response_code(500);
    echo json_encode(["error" => "Error al obtener los datos de la encuesta en la línea " . $e->getLine() . ": " . $e->getMessage()]);
}
?>
