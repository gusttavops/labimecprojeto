<?php
// api/marcar_lida_chatbatepapo.php
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

$input = json_decode(file_get_contents('php://input'), true);
$partner_id = $input['partner_id'] ?? null;

if (empty($partner_id)) {
    echo json_encode(['success' => false, 'error' => 'ID do parceiro não fornecido.']);
    exit;
}

$current_user_id = $_SESSION['user_id'];

require_once __DIR__ . '/../includes/db.php'; // Ajuste o caminho

try {
    // Marca como lida as mensagens enviadas pelo parceiro para o usuário logado
    $stmt = $pdo->prepare("UPDATE mensagens_chat SET lida = 1 WHERE remetente_id = ? AND destinatario_id = ? AND lida = 0");
    $stmt->execute([$partner_id, $current_user_id]);

    echo json_encode(['success' => true, 'message' => 'Mensagens marcadas como lidas.']);

} catch (PDOException $e) {
    error_log("Erro ao marcar chat como lido em marcar_lida_chatbatepapo.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro interno do servidor.']);
}
?>