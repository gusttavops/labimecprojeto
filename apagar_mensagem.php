<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || !isset($_SESSION['nome']) || !isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Não autenticado.']);
    exit;
}

require_once __DIR__ . '/includes/db.php';
$current_user_id = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);

$message_id = filter_var($data['message_id'] ?? null, FILTER_VALIDATE_INT);
$box_type = filter_var($data['box'] ?? null, FILTER_SANITIZE_STRING); // 'entrada' ou 'enviadas'

if (!$message_id || !in_array($box_type, ['entrada', 'enviadas'])) {
    echo json_encode(['error' => 'Dados inválidos para apagar mensagem.']);
    exit;
}

try {
    $field_to_update = '';
    if ($box_type === 'entrada') {
        $field_to_update = 'apagada_por_destinatario';
    } else { // 'enviadas'
        $field_to_update = 'apagada_por_remetente';
    }

    $stmt = $pdo->prepare("
        UPDATE mensagens
        SET {$field_to_update} = TRUE
        WHERE id = ? AND (remetente_id = ? OR destinatario_id = ?)
    ");
    $stmt->execute([$message_id, $current_user_id, $current_user_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Mensagem não encontrada ou você não tem permissão para apagá-la.']);
    }

} catch (PDOException $e) {
    error_log("Erro ao apagar mensagem: " . $e->getMessage());
    echo json_encode(['error' => 'Erro ao apagar mensagem.']);
}
?>