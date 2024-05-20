<?php
// Datos de conexión a la base de datos
$host = 'localhost';
$port = '3308';
$dbname = 'encuestasdb';
$username = 'root';
$password = '';

/*$host = 'localhost';
$dbname = 'encuestasdb';
$username = 'laravel';
$password = 'HatunSoft@2023';*/

try {
    // Conexión PDO
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    //$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
