<?php
// Aumentando o limite de memória para 512MB
ini_set('memory_limit', '512M');

// Defina suas credenciais de banco de dados
$host = 'localhost';
$db = 'sistema_login';
$user = 'root';
$pass = '';

try {
    // Criando a conexão PDO
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Consulta no banco de dados para buscar mensagens
    $stmt = $pdo->prepare("SELECT * FROM mensagens");
    $stmt->execute();


} catch (PDOException $e) {
    echo "Erro na conexão: " . $e->getMessage();
}
?>
