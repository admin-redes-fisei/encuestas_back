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

    if (isset($datos['tab_codigo']) && isset($datos['tab_facultad_pertenece'])) {
        $tab_codigo = $datos['tab_codigo'];
        $tab_facultad_pertenece = $datos['tab_facultad_pertenece'];
        $tab_formulario_pertenece = isset($datos['tab_formulario_pertenece']) ? $datos['tab_formulario_pertenece'] : null;

        if ($tab_formulario_pertenece) {
            // Verifica si ya existe un tablero con el formulario_id proporcionado
            $consulta = $pdo->prepare("SELECT COUNT(*) FROM tableros WHERE tab_formulario_pertenece = :tab_formulario_pertenece");
            $consulta->bindParam(':tab_formulario_pertenece', $tab_formulario_pertenece);
            $consulta->execute();
            $existe = $consulta->fetchColumn();

            if ($existe > 0) {
                // Si existe, actualiza el tablero
                $consulta = $pdo->prepare("UPDATE tableros 
                                           SET tab_codigo = :tab_codigo, tab_fecha_modificacion = NOW() 
                                           WHERE tab_formulario_pertenece = :tab_formulario_pertenece");
                $consulta->bindParam(':tab_formulario_pertenece', $tab_formulario_pertenece);
                $consulta->bindParam(':tab_codigo', $tab_codigo);
            } else {
                // Si no existe, inserta un nuevo registro
                $consulta = $pdo->prepare("INSERT INTO tableros (tab_codigo, tab_facultad_pertenece, tab_formulario_pertenece, tab_fecha_modificacion) 
                                           VALUES (:tab_codigo, :tab_facultad_pertenece, :tab_formulario_pertenece, NOW())");
                $consulta->bindParam(':tab_codigo', $tab_codigo);
                $consulta->bindParam(':tab_facultad_pertenece', $tab_facultad_pertenece);
                $consulta->bindParam(':tab_formulario_pertenece', $tab_formulario_pertenece);
            }
        } else {
            // Verifica si ya existe un tablero con la facultad_id proporcionada sin relación en formularios
            $consulta = $pdo->prepare("SELECT COUNT(*) FROM tableros WHERE tab_facultad_pertenece = :tab_facultad_pertenece AND tab_formulario_pertenece IS NULL");
            $consulta->bindParam(':tab_facultad_pertenece', $tab_facultad_pertenece);
            $consulta->execute();
            $existe = $consulta->fetchColumn();

            if ($existe > 0) {
                // Si existe, actualiza el tablero
                $consulta = $pdo->prepare("UPDATE tableros 
                                           SET tab_codigo = :tab_codigo, tab_fecha_modificacion = NOW() 
                                           WHERE tab_facultad_pertenece = :tab_facultad_pertenece AND (tab_formulario_pertenece IS NULL)");
                $consulta->bindParam(':tab_codigo', $tab_codigo);
                $consulta->bindParam(':tab_facultad_pertenece', $tab_facultad_pertenece);
            } else {
                // Si no existe, inserta un nuevo registro
                $consulta = $pdo->prepare("INSERT INTO tableros (tab_codigo, tab_facultad_pertenece, tab_fecha_modificacion) 
                                           VALUES (:tab_codigo, :tab_facultad_pertenece, NOW())");
                $consulta->bindParam(':tab_codigo', $tab_codigo);
                $consulta->bindParam(':tab_facultad_pertenece', $tab_facultad_pertenece);
            }
        }

        // Ejecuta la consulta
        if ($consulta->execute()) {
            http_response_code(200); // OK
            echo json_encode(array("mensaje" => "OK"));
        } else {
            http_response_code(500); // Internal Server Error
            echo json_encode(array("error" => "Error al realizar la operación"));
        }
    } else {
        // Parámetros faltantes
        http_response_code(400); // Bad Request
        echo json_encode(array("mensaje" => "Faltan parámetros"));
    }
} else {
    // Método no permitido
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("error" => "Método no permitido"));
}
?>
