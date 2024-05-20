<?php
// Incluir el archivo de conexión a la base de datos
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
    if (isset($_GET['id'])) {
        
        $id_formulario = $_GET['id'];
        $valores_filtro = isset($_GET['valores_filtro']) ? $_GET['valores_filtro'] : [];

        $sql_filtro_res = 1;
        $sql_filtro_otr = 1;
        $longitud_sql_filtro_res = 0;
        if (!empty($valores_filtro)) {
            $sql_filtro_res = '';
            $sql_filtro_otr = '';
            foreach ($valores_filtro as $valor) {
                $sql_filtro_res .= " res_texto = '$valor' OR";
                $sql_filtro_otr .= " otr_respuesta_texto = '$valor' OR";
            }

            $sql_filtro_res = rtrim($sql_filtro_res, ' OR');
            $sql_filtro_otr = rtrim($sql_filtro_otr, ' OR');
            $longitud_sql_filtro_res = count($valores_filtro);
        }

        // Consulta SQL completa
        $sql_respuestas = "SELECT f.for_nombre AS nombre_encuesta, 
                   (
                        SELECT COUNT(DISTINCT res_encuestado_id)
                        FROM respuestas
                        WHERE res_formulario_pertenece = :id_encuesta
                        AND res_encuestado_id IN (
                            SELECT res_encuestado_id
                            FROM respuestas 
                            WHERE $sql_filtro_res
                            GROUP BY res_encuestado_id
                            ";
        $sql_respuestas .= $longitud_sql_filtro_res > 0 ? "HAVING COUNT(DISTINCT res_texto) = :parametros" : "";         
        $sql_respuestas .= "
                )) AS total_encuestados,
                r.res_pregunta_pertenece AS id_pregunta, 
                p.pre_titulo AS titulo_pregunta, p.pre_texto AS texto_pregunta, p.pre_tipo AS tipo_pregunta, 
                r.res_opcion_pertenece AS id_opcion, r.res_texto AS texto_opcion, op.opc_padre AS padre_opcion,
                COUNT(r.res_opcion_pertenece) AS numero_selecciones
                FROM respuestas r
                JOIN formularios f ON f.for_id = r.res_formulario_pertenece
                JOIN preguntas p ON p.pre_id = r.res_pregunta_pertenece
                JOIN opciones op ON op.opc_id = r.res_opcion_pertenece
                WHERE r.res_formulario_pertenece = :id_encuesta
                ";
        if ($sql_filtro_res !== 1) {
            $sql_respuestas .= " AND r.res_encuestado_id IN (
                        SELECT res_encuestado_id
                        FROM respuestas
                        WHERE $sql_filtro_res
                        GROUP BY res_encuestado_id
                        ";
            $sql_respuestas .= $longitud_sql_filtro_res > 0 ? "HAVING COUNT(DISTINCT res_texto) = :parametros" : "";
            $sql_respuestas .=")";
        }
        $sql_respuestas .= "
        GROUP BY r.res_pregunta_pertenece, r.res_opcion_pertenece, r.res_texto, op.opc_padre
        ORDER BY r.res_pregunta_pertenece, r.res_opcion_pertenece";
        
        $stmt_respuestas = $pdo->prepare($sql_respuestas);
        $stmt_respuestas->bindParam(':id_encuesta', $id_formulario, PDO::PARAM_INT);
        $stmt_respuestas->bindParam(':parametros', $longitud_sql_filtro_res, PDO::PARAM_INT);
        $stmt_respuestas->execute();

        $resultados_respuestas = array();
        if ($stmt_respuestas->rowCount() > 0) {
            while ($row = $stmt_respuestas->fetch(PDO::FETCH_ASSOC)) {
                $resultados_respuestas[] = $row;
            }
        }

        // Consulta SQL otros
        $sql_otras = "SELECT f.for_nombre AS nombre_encuesta, 
                    (
                        SELECT COUNT(DISTINCT o.otr_encuestado_id)
                        FROM opciones_otra_limpio o
                        JOIN preguntas p ON p.pre_id = o.otr_pregunta_pertenece
                        JOIN secciones s ON p.pre_seccion_pertenece = s.sec_id
                        JOIN formularios f ON f.for_id = s.sec_formulario_pertenece
                        WHERE f.for_id = :id_encuesta
                        AND o.otr_encuestado_id IN (
                            SELECT otr_encuestado_id
                            FROM opciones_otra_limpio 
                            WHERE $sql_filtro_otr
                            GROUP BY otr_encuestado_id
                            ";
        $sql_otras .= $longitud_sql_filtro_res > 0 ? "HAVING COUNT(DISTINCT otr_respuesta_texto) = :parametros" : "";         
        $sql_otras .= "
                )
                ) AS total_encuestados, 
                o.otr_pregunta_pertenece AS id_pregunta, 
                p.pre_titulo AS titulo_pregunta, p.pre_texto AS texto_pregunta, p.pre_tipo AS tipo_pregunta, 
                0 AS id_opcion, o.otr_respuesta_texto AS texto_opcion, NULL AS padre_opcion,
                COUNT(TRIM(o.otr_respuesta_texto)) AS numero_selecciones
                FROM opciones_otra_limpio o
                JOIN preguntas p ON p.pre_id = o.otr_pregunta_pertenece
                JOIN secciones s ON p.pre_seccion_pertenece = s.sec_id
                JOIN formularios f ON f.for_id = s.sec_formulario_pertenece
                WHERE f.for_id = :id_encuesta ";
        if ($sql_filtro_otr !== 1) {
            $sql_otras .= "AND o.otr_encuestado_id IN (
                        SELECT otr_encuestado_id
                        FROM opciones_otra_limpio
                        WHERE $sql_filtro_otr
                        GROUP BY otr_encuestado_id
                        ";
            if ($longitud_sql_filtro_res > 0) {
                $sql_otras .= " HAVING COUNT(DISTINCT otr_respuesta_texto) = :parametros ";
            };
            $sql_otras .= ")
            ";
        };
        $sql_otras .= "GROUP BY o.otr_pregunta_pertenece, o.otr_respuesta_texto
        ORDER BY o.otr_pregunta_pertenece";
        
        $stmt_otras = $pdo->prepare($sql_otras);
        $stmt_otras->bindParam(':id_encuesta', $id_formulario, PDO::PARAM_INT);
        if ($longitud_sql_filtro_res > 0) {
            $stmt_otras->bindParam(':parametros', $longitud_sql_filtro_res, PDO::PARAM_INT);
        }
        $stmt_otras->execute();

        $resultados_otras = array();
        if ($stmt_otras->rowCount() > 0) {
            while ($row = $stmt_otras->fetch(PDO::FETCH_ASSOC)) {
                $resultados_otras[] = $row;
            }
        }
        
        $resultados_completos = array_merge($resultados_respuestas, $resultados_otras);

        if (!empty($resultados_completos)) {
            $reorganizedData = array(
                'nombre_encuesta' => $resultados_completos[0]['nombre_encuesta'],
                'total_encuestados' => $resultados_completos[0]['total_encuestados'],
                'preguntas' => array()
            );

            foreach ($resultados_completos as $item) {
                $questionId = $item['id_pregunta'];

                $questionExists = false;
                foreach ($reorganizedData['preguntas'] as &$question) {
                    if ($question['id_pregunta'] === $questionId) {
                        $id_opcion = $item['id_opcion'] != 0 ? $item['id_opcion'] : mt_rand(1, 100);
                        $question['opciones'][] = array(
                            'id_opcion' => $id_opcion,
                            'texto_opcion' => $item['texto_opcion'],
                            'padre_opcion' => $item['padre_opcion'],
                            'numero_selecciones' => $item['numero_selecciones']
                        );
                        $questionExists = true;
                        break;
                    }
                }
                unset($question);

                if (!$questionExists) {
                    $id_opcion = $item['id_opcion'] != 0 ? $item['id_opcion'] : mt_rand(1, 100);
                    $reorganizedData['preguntas'][] = array(
                        'id_pregunta' => $questionId,
                        'titulo_pregunta' => $item['titulo_pregunta'],
                        'texto_pregunta' => $item['texto_pregunta'],
                        'tipo_pregunta' => $item['tipo_pregunta'],
                        'opciones' => array(
                            array(
                                'id_opcion' => $id_opcion,
                                'texto_opcion' => $item['texto_opcion'],
                                'padre_opcion' => $item['padre_opcion'],
                                'numero_selecciones' => $item['numero_selecciones']
                            )
                        )
                    );
                }
            };

            http_response_code(200);
            echo json_encode($reorganizedData);  
        }else{
            http_response_code(204); // No Content
            echo json_encode($resultados_completos);
        }

             
    } else {
        // Parámetros faltantes
        http_response_code(400);
        echo json_encode(array("error" => "Faltan parámetros"));
    }
} else {
    // Método no permitido
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("error" => "Método no permitido"));
}
?>
