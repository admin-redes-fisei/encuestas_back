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

if ($_SERVER["REQUEST_METHOD"] == "GET") {

    if (isset($_GET['formulario_id'])) {
        $formulario_id = $_GET['formulario_id'];

        // Consulta para obtener los datos del formulario
        $consultaFormulario = $pdo->prepare("SELECT * FROM formularios WHERE for_id = :formulario_id");
        $consultaFormulario->bindParam(':formulario_id', $formulario_id);
        $consultaFormulario->execute();
        $formulario = $consultaFormulario->fetch(PDO::FETCH_ASSOC);

        if ($formulario) {
            // Consulta para obtener las secciones del formulario
            $consultaSecciones = $pdo->prepare("SELECT * FROM secciones WHERE sec_formulario_pertenece = :formulario_id AND sec_eliminado = 0 ORDER BY sec_numero");
            $consultaSecciones->bindParam(':formulario_id', $formulario_id);
            $consultaSecciones->execute();
            $secciones = $consultaSecciones->fetchAll(PDO::FETCH_ASSOC);

            foreach ($secciones as &$seccion) {
                $seccion_id = $seccion['sec_id'];

                // Consulta para obtener las preguntas de cada sección
                $consultaPreguntas = $pdo->prepare("SELECT * FROM preguntas WHERE pre_seccion_pertenece = :seccion_id AND pre_eliminado = 0");
                $consultaPreguntas->bindParam(':seccion_id', $seccion_id);
                $consultaPreguntas->execute();
                $preguntas = $consultaPreguntas->fetchAll(PDO::FETCH_ASSOC);

                foreach ($preguntas as &$pregunta) {
                    $pregunta_id = $pregunta['pre_id'];

                    // Consulta para obtener las opciones de cada pregunta
                    $consultaOpciones = $pdo->prepare("SELECT * FROM opciones WHERE opc_pregunta_pertenece = :pregunta_id ORDER BY opc_eliminado, opc_padre, opc_numero");
                    $consultaOpciones->bindParam(':pregunta_id', $pregunta_id);
                    $consultaOpciones->execute();
                    $opciones = $consultaOpciones->fetchAll(PDO::FETCH_ASSOC);

                    $pregunta['opciones'] = $opciones;
                }

                $seccion['preguntas'] = $preguntas;
            }

            $formulario['secciones'] = $secciones;

            // Devuelve los resultados como JSON
            echo json_encode($formulario);
        } else {
            echo json_encode(array("mensaje" => "Formulario no encontrado"));
        }
    } else {
        echo json_encode(array("mensaje" => "ID de formulario no proporcionado"));
    }
} else {
    // Método no permitido
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("mensaje" => "Método no permitido"));
}

?>
