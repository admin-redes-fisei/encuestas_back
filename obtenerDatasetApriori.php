<?php
// Incluir el archivo de conexión
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
    // Obtener el ID del formulario enviado desde el cliente
    $formulario_id = $_GET['formulario_id'];

    // Preparar la consulta SQL para obtener las preguntas del formulario
    $sql_preguntas = "SELECT pregunta_id FROM (
                    SELECT r.res_pregunta_pertenece AS pregunta_id, p.pre_numero as numero
                    FROM respuestas r
                    LEFT JOIN preguntas p ON r.res_pregunta_pertenece = p.pre_id
                    WHERE r.res_formulario_pertenece = :formulario_id
                    UNION
                    SELECT o.otr_pregunta_pertenece AS pregunta_id, p2.pre_numero as numero
                    FROM opciones_otra_limpio o 
                    LEFT JOIN respuestas r2 ON o.otr_encuestado_id = r2.res_encuestado_id 
                    LEFT JOIN preguntas p2 ON o.otr_pregunta_pertenece = p2.pre_id
                    WHERE r2.res_formulario_pertenece = :formulario_id
                    ) as preguntas ORDER BY numero;";

    $consulta_preguntas = $pdo->prepare($sql_preguntas);
    $consulta_preguntas->bindParam(':formulario_id', $formulario_id);
    $consulta_preguntas->execute();

    if ($consulta_preguntas->rowCount() > 0) {
        $preguntas = array();
        while ($row_pregunta = $consulta_preguntas->fetch(PDO::FETCH_ASSOC)) {
            $preguntas[] = $row_pregunta["pregunta_id"];
        }

        // Construir la consulta dinámica para obtener las respuestas
        $select_fields = array();
        $case_when_statements = array();

        $index = 1;

        foreach ($preguntas as $pregunta_id) {
            $select_fields[] = "GROUP_CONCAT(DISTINCT CASE
            WHEN r.res_pregunta_pertenece = $pregunta_id THEN COALESCE(r.res_texto, '')
            WHEN o.otr_pregunta_pertenece = $pregunta_id THEN COALESCE(o.otr_respuesta_texto, '')
            END SEPARATOR '|') AS pregunta_$index";

            $index++;
        }

        $select_clause = implode(",", $select_fields);

        $sql_consulta = "SELECT
            $select_clause
        FROM
            respuestas r
        LEFT JOIN
            opciones_otra_limpio o ON o.otr_encuestado_id = r.res_encuestado_id
        LEFT JOIN
            opciones op ON op.opc_id = r.res_opcion_pertenece
        WHERE
            r.res_formulario_pertenece = :formulario_id
        GROUP BY
            r.res_encuestado_id, r.res_encuestado_ip;";

        // Ejecutar la consulta
        $consulta_consulta = $pdo->prepare($sql_consulta);
        $consulta_consulta->bindParam(':formulario_id', $formulario_id);
        $consulta_consulta->execute();

        if ($consulta_consulta->rowCount() > 0) {
            // Generar el dataset con las respuestas
            $dataset = array();
            while ($row_consulta = $consulta_consulta->fetch(PDO::FETCH_ASSOC)) {
                $dataset[] = $row_consulta;
            }

            http_response_code(200);
            echo json_encode(array_values($dataset));
        } else {
            http_response_code(404); // Not Found
            echo json_encode(array("error" => "No se encontraron respuestas para el formulario ID $formulario_id"));
        }
    } else {
        http_response_code(404); // Not Found
        echo json_encode(array("error" => "No se encontraron preguntas para el formulario ID $formulario_id"));
    }
} else {
    // Método no permitido
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("error" => "Método no permitido"));
}
?>
