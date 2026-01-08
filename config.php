<?php
// config.php
$host = 'localhost';
$user = 'root';
$pass = ''; // Se tiver senha no seu XAMPP, coloque aqui
$db   = 'van_escolar';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}
?>