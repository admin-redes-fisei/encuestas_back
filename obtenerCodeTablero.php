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

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    // Verificar si se proporcionó el parámetro necesario
    if (isset($_GET['formulario_id'])) {
        $formulario_id = $_GET['formulario_id'];

        // Prepara la consulta SQL con el parámetro proporcionado
        $consulta = $pdo->prepare("SELECT * FROM tableros WHERE tab_formulario_pertenece = :formulario_id");
        $consulta->bindParam(':formulario_id', $formulario_id);
        $consulta->execute();

        // Obtiene el resultado de la consulta
        $tableros = $consulta->fetchAll(PDO::FETCH_ASSOC);

        // Devuelve los datos de los tableros como JSON, o un arreglo vacío si no se encontraron
        echo json_encode($tableros);
    } else {
        $tab_facultad_pertenece = $_GET['tab_facultad_pertenece'];
        // Prepara la consulta SQL con el parámetro proporcionado
        $consulta = $pdo->prepare("SELECT * FROM tableros WHERE tab_facultad_pertenece = :tab_facultad_pertenece AND tab_formulario_pertenece IS NULL");
        $consulta->bindParam(':tab_facultad_pertenece', $tab_facultad_pertenece);
        $consulta->execute();

        // Obtiene el resultado de la consulta
        $tableros = $consulta->fetchAll(PDO::FETCH_ASSOC);

        // Devuelve los datos de los tableros como JSON, o un arreglo vacío si no se encontraron
        echo json_encode($tableros);
    }
} else {
    // Método no permitido
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("mensaje" => "Método no permitido"));
}
?>
