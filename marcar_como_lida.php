<?php
session_start();
header('Content-Type: application/json');

// Incluir a conexão com o banco de dados
require_once __DIR__ . '/../includes/db.php'; // Ajuste o caminho conforme necessário

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Usuário não autenticado.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['message_id'])) {
    echo json_encode(['error' => 'ID da mensagem não fornecido.']);
    exit;
}

$message_id = $input['message_id'];

try {
    // Primeiro, verifica se a mensagem pertence ao usuário logado e se é uma mensagem recebida (destinatario_id = user_id)
    $stmt = $pdo->prepare("SELECT id FROM mensagens WHERE id = ? AND destinatario_id = ? AND lida = 0");
    $stmt->execute([$message_id, $user_id]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($message) {
        // Marca a mensagem como lida
        $update_stmt = $pdo->prepare("UPDATE mensagens SET lida = 1 WHERE id = ?");
        $update_stmt->execute([$message_id]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Mensagem não encontrada, já lida, ou você não tem permissão para marcá-la como lida.']);
    }

} catch (PDOException $e) {
    error_log("Erro ao marcar mensagem como lida: " . $e->getMessage());
    echo json_encode(['error' => 'Erro interno do servidor ao marcar mensagem como lida.']);
}
?>