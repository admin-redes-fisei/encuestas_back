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

// Obtén los datos del usuario y contraseña del POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $aud_org = "9beb7af4-2e55-47f1-99e1-1f1d313ce88a";
    $data = json_decode(file_get_contents('php://input'), true);

    if ($data !== null && isset($data['username']) && isset($data['password']) && isset($data['aud'])) {
        $usuario = $data['username'];
        $contrasena = $data['password'];
        $aud = $data['aud'];

        if ($aud == $aud_org) {
            $consulta = $pdo->prepare("SELECT usu_nombres, usu_apellidos, usu_correo, usu_estado, usu_permisos FROM usuarios WHERE usu_correo = ?");
            $consulta->execute([$usuario]);
            
        }else {
            $consulta = $pdo->prepare("SELECT usu_nombres, usu_apellidos, usu_correo, usu_estado, usu_permisos, usu_clave FROM usuarios WHERE (usu_correo = ? OR usu_usuario = ?)");
            $consulta->execute([$usuario, $usuario]);
        }

        // Verifica si se encontró el usuario
        if ($consulta->rowCount() > 0) {
            $datos_usuario = $consulta->fetch(PDO::FETCH_ASSOC);
            $hash_guardado = $datos_usuario['usu_clave'];
                
            // Compara la contraseña en texto plano con el hash almacenado usando password_verify
            $contrasena_correcta = password_verify($contrasena, $hash_guardado);

            if ($contrasena_correcta) {
                // Genera el token (simulado)
                $token = generateToken($usuario);

                $response = array(
                    "user" => $datos_usuario,
                    "token" => $token
                );
                    
                // Devuelve los datos del usuario como JSON
                echo json_encode($response);
                exit(); // Importante salir del script después de enviar la respuesta
            }
        }
        
        // Usuario no encontrado o contraseña incorrecta
        http_response_code(401); // Unauthorized
        echo json_encode(array("mensaje" => "Usuario o contraseña incorrectos"));
    } else {
        http_response_code(400); // Bad Request
        echo json_encode(array("mensaje" => "Datos incompletos o incorrectos"));
    }
} else {
    // Método no permitido
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("mensaje" => "Método no permitido"));
}

function generateToken($usuario)
{
    return base64_encode($usuario); // Simulación básica de token
}
?>
