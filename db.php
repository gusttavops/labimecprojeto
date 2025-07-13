<?php
// Define o fuso horário padrão para todas as funções de data/hora
date_default_timezone_set('America/Fortaleza'); // Ou 'America/Sao_Paulo' se preferir

$host = 'localhost';
$db   = 'sistema_login';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    error_log("Erro de conexão com o banco de dados: " . $e->getMessage());
    // Em produção, você pode querer exibir uma mensagem mais amigável ao usuário
    // ou redirecioná-lo para uma página de erro.
    die("Erro de conexão com o banco de dados. Por favor, tente novamente mais tarde.");
}
?>