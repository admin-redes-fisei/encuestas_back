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

    // Busca el último opc_numero de la pregunta
    $consultaUltimoNumero = $pdo->prepare("SELECT MAX(opc_numero) as max_opc_numero FROM opciones WHERE opc_pregunta_pertenece = :opc_pregunta_pertenece");
    $consultaUltimoNumero->bindParam(':opc_pregunta_pertenece', $datos['opc_pregunta_pertenece']);
    $consultaUltimoNumero->execute();
    $resultado = $consultaUltimoNumero->fetch(PDO::FETCH_ASSOC);

    $ultimoNumero = $resultado['max_opc_numero'];
    $nuevoNumero = $ultimoNumero ? $ultimoNumero + 1 : 1;

    // Prepara la consulta SQL para el INSERT
    $consulta = $pdo->prepare("INSERT INTO opciones (opc_numero, opc_label, opc_padre, opc_tooltip_texto, opc_tooltip_imagen,
    opc_eliminado, opc_fecha_creacion, opc_pregunta_pertenece) 
    VALUES (:opc_numero, :opc_label, :opc_padre, :opc_tooltip_texto, :opc_tooltip_imagen, 0, NOW(), :opc_pregunta_pertenece)");

    // Enlaza los parámetros
    $consulta->bindParam(':opc_numero', $nuevoNumero);
    $consulta->bindParam(':opc_label', $datos['opc_label']);
    $consulta->bindParam(':opc_padre', $datos['opc_padre']);
    $consulta->bindParam(':opc_tooltip_texto', $datos['opc_tooltip_texto']);
    $consulta->bindParam(':opc_tooltip_imagen', $datos['opc_tooltip_imagen']);
    $consulta->bindParam(':opc_pregunta_pertenece', $datos['opc_pregunta_pertenece']);

    // Ejecuta la consulta
    if ($consulta->execute()) {
        http_response_code(201); // Created
        echo json_encode(array("mensaje" => "OK"));
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(array("error" => "Error al crear la opcion"));
    }
} else {
    // Método no permitido
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("error" => "Método no permitido"));
}
?>
