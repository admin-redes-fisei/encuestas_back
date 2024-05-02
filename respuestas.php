<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

include 'conexion.php';

$userId = md5(uniqid('', true));

try {
    $data = json_decode(file_get_contents('php://input'), true);

    function insertOtrosRespuestas($pdo, $respuesta, $userId) {
        $stmt = $pdo->prepare("INSERT INTO opciones_otra (otr_encuestado_id, otr_pregunta_pertenece, otr_respuesta_texto) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $respuesta['pregunta_id'], $respuesta['respuesta_otra_texto']]);
    }

    function insertRespuestas($pdo, $respuesta, $userId, $ip_usuario) {
        $stmt = $pdo->prepare("INSERT INTO respuestas (res_encuestado_id, res_formulario_pertenece, res_pregunta_pertenece, res_opcion_pertenece, res_texto, res_encuestado_ip) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $respuesta['formulario_id'], $respuesta['pregunta_id'], $respuesta['opcion_id'], $respuesta['respuesta_texto'], $ip_usuario]);
    }

    // Verificar si el arreglo 'otros' está presente en los datos
    if (isset($data['otros'])) {
        foreach ($data['otros'] as $respuesta) {
            if ($respuesta !== "") {
                insertOtrosRespuestas($pdo, $respuesta, $userId);
            }
        }
        unset($data['otros']);
    }

    function contiene_sub_arreglos($arreglo) {
        foreach ($arreglo as $elemento) {
            if (is_array($elemento)) {
                return true;
            }
        }
        return false;
    }

    foreach ($data as $key => $value) {
        if (contiene_sub_arreglos($value)) {
            foreach ($value as $respuesta) {
                insertRespuestas($pdo, $respuesta, $userId, $_SERVER['REMOTE_ADDR']);
            }
        } else {
            insertRespuestas($pdo, $value, $userId, $_SERVER['REMOTE_ADDR']);
        }
    }

    http_response_code(201);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error al procesar las respuestas"]);
}
?>
