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

    // Busca el último pre_numero de la pregunta
    $consultaUltimoNumero = $pdo->prepare("SELECT MAX(pre_numero) as max_pre_numero FROM preguntas 
    WHERE pre_seccion_pertenece = :pre_seccion_pertenece");
    $consultaUltimoNumero->bindParam(':pre_seccion_pertenece', $datos['pre_seccion_pertenece']);
    $consultaUltimoNumero->execute();
    $resultado = $consultaUltimoNumero->fetch(PDO::FETCH_ASSOC);

    $ultimoNumero = $resultado['max_pre_numero'];
    $nuevoNumero = $ultimoNumero ? $ultimoNumero + 1 : 1;

    // Prepara la consulta SQL para el INSERT
    $consulta = $pdo->prepare("INSERT INTO preguntas (pre_numero, pre_alias, pre_titulo, pre_texto, pre_tipo, 
    pre_url_imagen, pre_tipo_imagen, pre_tooltip_texto, pre_tooltip_imagen, pre_es_abierta, pre_es_obligatoria, 
    pre_estado, pre_eliminado, pre_fecha_creacion, pre_seccion_pertenece) 
    VALUES (:pre_numero, :pre_alias, :pre_titulo, :pre_texto, :pre_tipo, :pre_url_imagen, :pre_tipo_imagen, 
    :pre_tooltip_texto, :pre_tooltip_imagen, :pre_es_abierta, :pre_es_obligatoria, :pre_estado, 0, NOW(), :pre_seccion_pertenece)");

    // Enlaza los parámetros
    $consulta->bindParam(':pre_numero', $nuevoNumero);
    $consulta->bindParam(':pre_alias', $datos['pre_alias']);
    $consulta->bindParam(':pre_titulo', $datos['pre_titulo']);
    $consulta->bindParam(':pre_texto', $datos['pre_texto']);
    $consulta->bindParam(':pre_tipo', $datos['pre_tipo']);
    $consulta->bindParam(':pre_url_imagen', $datos['pre_url_imagen']);
    $consulta->bindParam(':pre_tipo_imagen', $datos['pre_tipo_imagen']);
    $consulta->bindParam(':pre_tooltip_texto', $datos['pre_tooltip_texto']);
    $consulta->bindParam(':pre_tooltip_imagen', $datos['pre_tooltip_imagen']);
    $consulta->bindParam(':pre_es_abierta', $datos['pre_es_abierta']);
    $consulta->bindParam(':pre_es_obligatoria', $datos['pre_es_obligatoria']);
    $consulta->bindParam(':pre_estado', $datos['pre_estado']);
    $consulta->bindParam(':pre_seccion_pertenece', $datos['pre_seccion_pertenece']);

    // Ejecuta la consulta
    if ($consulta->execute()) {
        http_response_code(201); // Created
        echo json_encode(array("mensaje" => "OK"));
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(array("error" => "Error al crear la pregunta"));
    }
} else {
    // Método no permitido
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("error" => "Método no permitido"));
}
?>
