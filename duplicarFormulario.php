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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener el ID del formulario enviado desde el cliente
    $formulario = json_decode(file_get_contents("php://input"), true);

    try {
        // Iniciar una transacción
        $pdo->beginTransaction();

        // Duplicar el formulario
        $sql_duplicar_formulario = "INSERT INTO formularios (for_nombre, for_alias, for_tipo, for_estado, for_facultad_pertenece, for_fecha_creacion)
                                    SELECT CONCAT(for_nombre, ' - copia'), CONCAT(for_alias, '-copia'), for_tipo, 0, for_facultad_pertenece, NOW()
                                    FROM formularios WHERE for_id = :formulario_id";
        $stmt = $pdo->prepare($sql_duplicar_formulario);
        $stmt->bindParam(':formulario_id', $formulario["for_id"]);
        $stmt->execute();
        $nuevo_formulario_id = $pdo->lastInsertId();

        // Obtener las secciones del formulario original
        $sql_secciones = "SELECT * FROM secciones 
        WHERE sec_formulario_pertenece = :formulario_id AND sec_estado = 1 AND sec_eliminado = 0";
        $stmt = $pdo->prepare($sql_secciones);
        $stmt->bindParam(':formulario_id', $formulario["for_id"]);
        $stmt->execute();
        $secciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($secciones as $seccion) {
            // Duplicar cada sección
            $sql_duplicar_seccion = "INSERT INTO secciones (sec_numero, sec_nombre, sec_estado, sec_eliminado, sec_formulario_pertenece, sec_fecha_creacion)
                                     VALUES (:sec_numero, :sec_nombre, 1, 0, :nuevo_formulario_id, NOW())";
            $stmt = $pdo->prepare($sql_duplicar_seccion);
            $stmt->bindParam(':nuevo_formulario_id', $nuevo_formulario_id);
            $stmt->bindParam(':sec_numero', $seccion['sec_numero']);
            $stmt->bindParam(':sec_nombre', $seccion['sec_nombre']);
            $stmt->execute();
            $nueva_seccion_id = $pdo->lastInsertId();

            // Obtener las preguntas de la sección original
            $sql_preguntas = "SELECT * FROM preguntas WHERE pre_seccion_pertenece = :seccion_id AND pre_estado = 1 AND pre_eliminado = 0";
            $stmt = $pdo->prepare($sql_preguntas);
            $stmt->bindParam(':seccion_id', $seccion['sec_id']);
            $stmt->execute();
            $preguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($preguntas as $pregunta) {
                // Duplicar cada pregunta
                $sql_duplicar_pregunta = "INSERT INTO preguntas (pre_numero, pre_alias, pre_titulo, pre_texto, pre_tipo, pre_url_imagen,
                pre_tipo_imagen, pre_tooltip_texto, pre_tooltip_imagen, pre_es_abierta, pre_es_obligatoria,
                pre_estado, pre_eliminado, pre_fecha_creacion, pre_seccion_pertenece)
                                          VALUES (:pre_numero, :pre_alias, :pre_titulo, :pre_texto, :pre_tipo, :pre_url_imagen,
                                          :pre_tipo_imagen, :pre_tooltip_texto, :pre_tooltip_imagen, :pre_es_abierta, :pre_es_obligatoria,
                                          1, 0, NOW(), :nueva_seccion_id)";
                $stmt = $pdo->prepare($sql_duplicar_pregunta);
                $stmt->bindParam(':nueva_seccion_id', $nueva_seccion_id);
                $stmt->bindParam(':pre_numero', $pregunta['pre_numero']);
                $stmt->bindParam(':pre_alias', $pregunta['pre_alias']);
                $stmt->bindParam(':pre_titulo', $pregunta['pre_titulo']);
                $stmt->bindParam(':pre_texto', $pregunta['pre_texto']);
                $stmt->bindParam(':pre_tipo', $pregunta['pre_tipo']);
                $stmt->bindParam(':pre_url_imagen', $pregunta['pre_url_imagen']);
                $stmt->bindParam(':pre_tipo_imagen', $pregunta['pre_tipo_imagen']);
                $stmt->bindParam(':pre_tooltip_texto', $pregunta['pre_tooltip_texto']);
                $stmt->bindParam(':pre_tooltip_imagen', $pregunta['pre_tooltip_imagen']);
                $stmt->bindParam(':pre_es_abierta', $pregunta['pre_es_abierta']);
                $stmt->bindParam(':pre_es_obligatoria', $pregunta['pre_es_obligatoria']);
                $stmt->execute();
                $nueva_pregunta_id = $pdo->lastInsertId();

                // Obtener las opciones de la pregunta original
                $sql_opciones = "SELECT * FROM opciones WHERE opc_pregunta_pertenece = :pregunta_id AND opc_eliminado = 0";
                $stmt = $pdo->prepare($sql_opciones);
                $stmt->bindParam(':pregunta_id', $pregunta['pre_id']);
                $stmt->execute();
                $opciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($opciones as $opcion) {
                    // Duplicar cada opción
                    $sql_duplicar_opcion = "INSERT INTO opciones (opc_numero, opc_label, opc_padre, opc_tooltip_texto, 
                    opc_tooltip_imagen, opc_eliminado, opc_fecha_creacion, opc_pregunta_pertenece)
                                            VALUES (:opc_numero, :opc_label, :opc_padre, :opc_tooltip_texto, :opc_tooltip_imagen, 
                                            0, NOW(), :nueva_pregunta_id)";
                    $stmt = $pdo->prepare($sql_duplicar_opcion);
                    $stmt->bindParam(':nueva_pregunta_id', $nueva_pregunta_id);
                    $stmt->bindParam(':opc_numero', $opcion['opc_numero']);
                    $stmt->bindParam(':opc_label', $opcion['opc_label']);
                    $stmt->bindParam(':opc_padre', $opcion['opc_padre']);
                    $stmt->bindParam(':opc_tooltip_texto', $opcion['opc_tooltip_texto']);
                    $stmt->bindParam(':opc_tooltip_imagen', $opcion['opc_tooltip_imagen']);
                    $stmt->execute();
                }
            }
        }

        // Confirmar la transacción
        $pdo->commit();

        // Respuesta exitosa
        http_response_code(200);
        echo json_encode(array("mensaje" => "OK"));
    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(array("error" => "Error al duplicar el formulario: " . $e->getMessage()));
    }
} else {
    // Método no permitido
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("error" => "Método no permitido"));
}
?>
