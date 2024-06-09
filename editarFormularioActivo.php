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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtiene los datos enviados por el cliente
    $datos = json_decode(file_get_contents("php://input"), true);

    // Verifica si se ha enviado el id del formulario
    if (isset($datos['for_id'])) {
        $for_id = $datos['for_id'];
        $for_estado = $datos['for_estado'];

        // Inicia una transacción
        $pdo->beginTransaction();

        try {
            if ($for_estado == 1) {
            // Actualiza el estado del formulario especificado a 1 (activo)
                $consulta = $pdo->prepare("UPDATE formularios SET for_estado = 1 WHERE for_id = :for_id");
                $consulta->bindParam(':for_id', $for_id);
                $consulta->execute();

                // Obtiene el tipo y la facultad del formulario activado
                $consulta = $pdo->prepare("SELECT for_tipo, for_facultad_pertenece FROM formularios WHERE for_id = :for_id");
                $consulta->bindParam(':for_id', $for_id);
                $consulta->execute();
                $formulario = $consulta->fetch(PDO::FETCH_ASSOC);

                if ($formulario) {
                    $for_tipo = $formulario['for_tipo'];
                    $for_facultad = $formulario['for_facultad_pertenece'];

                    // Desactiva otros formularios del mismo tipo y facultad
                    $consulta = $pdo->prepare("UPDATE formularios SET for_estado = 0 WHERE for_tipo = :for_tipo AND for_facultad_pertenece = :for_facultad AND for_id != :for_id");
                    $consulta->bindParam(':for_tipo', $for_tipo);
                    $consulta->bindParam(':for_facultad', $for_facultad);
                    $consulta->bindParam(':for_id', $for_id);
                    $consulta->execute();
                }
            } else {
                $consulta = $pdo->prepare("UPDATE formularios SET for_estado = 0 WHERE for_id = :for_id");
                $consulta->bindParam(':for_id', $for_id);
                $consulta->execute();
            }
            // Confirma la transacción
            $pdo->commit();
            http_response_code(201); // Created
            echo json_encode(array("mensaje" => "OK"));
        } catch (Exception $e) {
            // En caso de error, revierte la transacción
            $pdo->rollBack();
            http_response_code(500); // Internal Server Error
            echo json_encode(array("error" => "Error al actualizar los formularios"));
        }
    } else {
        http_response_code(400); // Bad Request
        echo json_encode(array("error" => "Falta el id del formulario"));
    }
} else {
    // Método no permitido
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("error" => "Método no permitido"));
}
?>
