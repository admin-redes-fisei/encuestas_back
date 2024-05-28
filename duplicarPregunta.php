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
    // Obtener el ID de la pregunta enviado desde el cliente
    $data = json_decode(file_get_contents("php://input"), true);
    $pregunta_id = $data['pre_id'];

    try {
        // Iniciar una transacción
        $pdo->beginTransaction();

        // Obtener la pregunta original
        $sql_pregunta = "SELECT * FROM preguntas WHERE pre_id = :pregunta_id AND pre_eliminado = 0";
        $stmt = $pdo->prepare($sql_pregunta);
        $stmt->bindParam(':pregunta_id', $pregunta_id);
        $stmt->execute();
        $pregunta = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($pregunta) {
            // Obtener el número más alto de las preguntas en la misma sección
            $sql_max_numero = "SELECT MAX(pre_numero) AS max_numero FROM preguntas WHERE pre_seccion_pertenece = :seccion_id";
            $stmt = $pdo->prepare($sql_max_numero);
            $stmt->bindParam(':seccion_id', $pregunta['pre_seccion_pertenece']);
            $stmt->execute();
            $max_numero = $stmt->fetch(PDO::FETCH_ASSOC)['max_numero'];
            $nuevo_numero = $max_numero + 1;

            // Duplicar la pregunta con el nuevo número
            $sql_duplicar_pregunta = "INSERT INTO preguntas (pre_numero, pre_alias, pre_titulo, pre_texto, pre_tipo, pre_url_imagen,
                pre_tipo_imagen, pre_tooltip_texto, pre_tooltip_imagen, pre_es_abierta, pre_es_obligatoria,
                pre_estado, pre_eliminado, pre_fecha_creacion, pre_seccion_pertenece)
                                      VALUES (:pre_numero, CONCAT(:pre_alias, '-copia'), :pre_titulo, :pre_texto, :pre_tipo, :pre_url_imagen,
                                      :pre_tipo_imagen, :pre_tooltip_texto, :pre_tooltip_imagen, :pre_es_abierta, :pre_es_obligatoria,
                                      1, 0, NOW(), :pre_seccion_pertenece)";
            $stmt = $pdo->prepare($sql_duplicar_pregunta);
            $stmt->bindParam(':pre_numero', $nuevo_numero);
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
            $stmt->bindParam(':pre_seccion_pertenece', $pregunta['pre_seccion_pertenece']);
            $stmt->execute();
            $nueva_pregunta_id = $pdo->lastInsertId();

            // Obtener las opciones de la pregunta original
            $sql_opciones = "SELECT * FROM opciones WHERE opc_pregunta_pertenece = :pregunta_id AND opc_eliminado = 0";
            $stmt = $pdo->prepare($sql_opciones);
            $stmt->bindParam(':pregunta_id', $pregunta_id);
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
        } else {
            throw new Exception("Pregunta no encontrada.");
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
        echo json_encode(array("error" => "Error al duplicar la pregunta: " . $e->getMessage()));
    }
} else {
    // Método no permitido
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("error" => "Método no permitido"));
}
?>
