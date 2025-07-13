<?php
// api/excluir_chat_conversabatepapo.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método de requisição inválido.']);
    exit;
}

// Usando $_POST pois o frontend envia como application/x-www-form-urlencoded
$partner_id = $_POST['partner_id'] ?? null;

if (empty($partner_id)) {
    echo json_encode(['success' => false, 'error' => 'ID do parceiro não fornecido.']);
    exit;
}

$current_user_id = $_SESSION['user_id'];

require_once __DIR__ . '/../includes/db.php'; // Ajuste o caminho

try {
    // Exclui todas as mensagens entre o usuário logado e o parceiro
    $stmt = $pdo->prepare("DELETE FROM mensagens_chat WHERE (remetente_id = ? AND destinatario_id = ?) OR (remetente_id = ? AND destinatario_id = ?)");
    $stmt->execute([$current_user_id, $partner_id, $partner_id, $current_user_id]);

    echo json_encode(['success' => true, 'message' => 'Conversa excluída com sucesso.']);

} catch (PDOException $e) {
    error_log("Erro ao excluir conversa em excluir_chat_conversabatepapo.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro interno do servidor.']);
}
?>