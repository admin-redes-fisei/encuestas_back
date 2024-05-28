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

    // Prepara la consulta SQL para el INSERT en la tabla facultades
    $consultaFacultad = $pdo->prepare("INSERT INTO facultades (fac_nombre, fac_siglas, fac_estado, fac_eliminado, fac_fecha_creacion) 
    VALUES (:fac_nombre, :fac_siglas, :fac_estado, 0, NOW())");

    // Enlaza los parámetros
    $consultaFacultad->bindParam(':fac_nombre', $datos['fac_nombre']);
    $consultaFacultad->bindParam(':fac_siglas', $datos['fac_siglas']);
    $consultaFacultad->bindParam(':fac_estado', $datos['fac_estado']);

    // Ejecuta la consulta para insertar la facultad
    if ($consultaFacultad->execute()) {
        // Obtener el ID de la facultad recién creada
        $idFacultad = $pdo->lastInsertId();
        $empleabilidadAlias = "empleabilidad-" . $datos['fac_siglas'];
        $demandaAlias = "demanda-estudiantes-" . $datos['fac_siglas'];

        // Prepara la consulta SQL para insertar los formularios relacionados a la facultad
        $consultaFormulario = $pdo->prepare("INSERT INTO formularios (for_nombre, for_alias, for_tipo, for_estado, for_facultad_pertenece, for_fecha_creacion) 
        VALUES ('Encuesta de Empleabilidad', :empleabilidad, 'empresas', 1, :for_facultad_pertenece, NOW()),
        ('Encuesta de Demanda Estudiantil', :demanda, 'estudiantes', 1, :for_facultad_pertenece, NOW())");

        $consultaFormulario->bindParam(':empleabilidad', $empleabilidadAlias);
        $consultaFormulario->bindParam(':demanda', $demandaAlias);
        $consultaFormulario->bindParam(':for_facultad_pertenece', $idFacultad);
        $consultaFormulario->execute();

        http_response_code(201); // Created
        echo json_encode(array("mensaje" => "OK"));
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(array("error" => "Error al crear la facultad"));
    }
} else {
    // Método no permitido
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("error" => "Método no permitido"));
}
?>
