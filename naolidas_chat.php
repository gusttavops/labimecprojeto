<?php
// api/naolidas_chat.php

session_start();
header('Content-Type: application/json');

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Usuário não autenticado.']);
    exit;
}

$current_user_id = $_SESSION['user_id'];

// Inclui a conexão com o banco de dados
// CERTIFIQUE-SE DE QUE O CAMINHO ESTÁ CORRETO PARA O SEU SETUP!
// Este caminho deve ser o mesmo que em 'obter_mensagens_nao_lidasbatepapo.php'
require_once __DIR__ . '/../includes/db.php';

try {
    // Consulta para contar mensagens de chat não lidas
    // Assumindo que sua tabela de mensagens de chat se chama 'mensagens_chat'
    // e possui as colunas 'destinatario_id' e 'lida'
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM mensagens_chat WHERE destinatario_id = ? AND lida = 0");
    $stmt->execute([$current_user_id]);
    $unread_chat_count = $stmt->fetchColumn();

    echo json_encode(['success' => true, 'unread_chat_count' => (int)$unread_chat_count]);

} catch (PDOException $e) {
    // Loga o erro para depuração (verifique seu error.log do Apache)
    error_log("Erro ao obter contagens de chat não lidas em naolidas_chat.php: " . $e->getMessage());
    echo json_encode(['error' => 'Erro interno do servidor ao obter contagens de chat.']);
}
?>