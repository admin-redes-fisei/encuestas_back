<?php
// Incluir el archivo de conexión
include 'conexion.php';

// Habilitar CORS
header('Access-Control-Allow-Origin: *');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    http_response_code(200);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "DELETE") {
    // Obtener el ID del formulario enviado desde el cliente
    $input = json_decode(file_get_contents("php://input"), true);
    $formulario_id = $input['for_id'];

    try {
        // Iniciar una transacción
        $pdo->beginTransaction();

        // Verificar si alguna pregunta del formulario tiene respuestas
        $sql_verificar_respuestas = "SELECT COUNT(*)FROM respuestas WHERE res_formulario_pertenece = :formulario_id";
        $stmt = $pdo->prepare($sql_verificar_respuestas);
        $stmt->bindParam(':formulario_id', $formulario_id);
        $stmt->execute();
        $total_respuestas = $stmt->fetchColumn();

        // Verificar si alguna opción del formulario está en opciones_otra
        $sql_verificar_opciones = "SELECT COUNT(*) FROM opciones_otra o
                                   JOIN preguntas p ON o.otr_pregunta_pertenece = p.pre_id
                                   JOIN secciones s ON p.pre_seccion_pertenece = s.sec_id
                                   WHERE s.sec_formulario_pertenece = :formulario_id";
        $stmt = $pdo->prepare($sql_verificar_opciones);
        $stmt->bindParam(':formulario_id', $formulario_id);
        $stmt->execute();
        $total_opciones = $stmt->fetchColumn();

        if ($total_respuestas == 0 && $total_opciones == 0) {
            // Eliminar las opciones del formulario
            $sql_eliminar_opciones = "DELETE FROM opciones WHERE opc_pregunta_pertenece IN (
                                          SELECT pre_id FROM preguntas WHERE pre_seccion_pertenece IN (
                                              SELECT sec_id FROM secciones WHERE sec_formulario_pertenece = :formulario_id
                                          )
                                      )";
            $stmt = $pdo->prepare($sql_eliminar_opciones);
            $stmt->bindParam(':formulario_id', $formulario_id);
            $stmt->execute();

            // Eliminar las preguntas del formulario
            $sql_eliminar_preguntas = "DELETE FROM preguntas WHERE pre_seccion_pertenece IN (
                                           SELECT sec_id FROM secciones WHERE sec_formulario_pertenece = :formulario_id
                                       )";
            $stmt = $pdo->prepare($sql_eliminar_preguntas);
            $stmt->bindParam(':formulario_id', $formulario_id);
            $stmt->execute();

            // Eliminar las secciones del formulario
            $sql_eliminar_secciones = "DELETE FROM secciones WHERE sec_formulario_pertenece = :formulario_id";
            $stmt = $pdo->prepare($sql_eliminar_secciones);
            $stmt->bindParam(':formulario_id', $formulario_id);
            $stmt->execute();

            // Eliminar el formulario
            $sql_eliminar_formulario = "DELETE FROM formularios WHERE for_id = :formulario_id";
            $stmt = $pdo->prepare($sql_eliminar_formulario);
            $stmt->bindParam(':formulario_id', $formulario_id);
            $stmt->execute();

            // Confirmar la transacción
            $pdo->commit();

            // Respuesta exitosa
            http_response_code(200);
            echo json_encode(array("mensaje" => "OK"));
        } else {
            // Revertir la transacción si hay relaciones
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(array("error" => "No se puede eliminar el formulario porque tiene preguntas con respuestas o opciones relacionadas"));
        }
    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(array("error" => "Error al eliminar el formulario: " . $e->getMessage()));
    }
} else {
    // Método no permitido
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("error" => "Método no permitido"));
}
?>
