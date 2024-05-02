<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

// Conexión a la base de datos MySQL
//$servername = "172.21.123.36";
//$username = "laravel";
//$password = "HatunSoft@2023";
//$dbname = "encuestasdb";
$servername = "localhost";
$port = "3308";
$username = "root";
$password = "";
$dbname = "encuestasdb";

$userId = md5(uniqid('', true));

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$data = json_decode(file_get_contents('php://input'), true);

function insertOtrosRespuestas($conn, $respuesta, $userId) {
    $stmt = $conn->prepare("INSERT INTO opciones_otra (otr_encuestado_id, otr_pregunta_pertenece, otr_respuesta_texto) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $userId, $respuesta['pregunta_id'], $respuesta['respuesta_otra_texto']);
    $stmt->execute();
    $stmt->close();
}

function insertRespuestas($conn, $respuesta, $userId, $ip_usuario) {
    $stmt = $conn->prepare("INSERT INTO respuestas (res_encuestado_id, res_formulario_pertenece, res_pregunta_pertenece, res_opcion_pertenece, res_texto, res_encuestado_ip) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siisss", $userId, $respuesta['formulario_id'], $respuesta['pregunta_id'], $respuesta['opcion_id'], $respuesta['respuesta_texto'], $ip_usuario);
    $stmt->execute();
    $stmt->close();
}

// Verificar si el arreglo 'otros' está presente en los datos
if (isset($data['otros'])) {
    foreach ($data['otros'] as $respuesta) {
        if ($respuesta !== "") {
            insertOtrosRespuestas($conn, $respuesta, $userId);
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
            insertRespuestas($conn, $respuesta, $userId, $_SERVER['REMOTE_ADDR']);
        }
    } else {
        insertRespuestas($conn, $value, $userId, $_SERVER['REMOTE_ADDR']);
    }

}

$conn->close();

http_response_code(201);
?>
