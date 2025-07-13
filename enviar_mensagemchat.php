<?php
// api/enviar_mensagemchat.php
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
$destinatario_id = $input['destinatario_id'] ?? null;
$corpo_mensagem = $input['corpo_mensagem'] ?? null;

if (empty($destinatario_id) || empty($corpo_mensagem)) {
    echo json_encode(['success' => false, 'error' => 'Destinatário ou corpo da mensagem não fornecidos.']);
    exit;
}

$remetente_id = $_SESSION['user_id'];

require_once __DIR__ . '/../includes/db.php'; // Ajuste o caminho

try {
    // Opcional: Verificar se o remetente bloqueou o destinatario ou vice-versa
    $stmt_check_blocked = $pdo->prepare("SELECT COUNT(*) FROM bloqueios WHERE (bloqueador_id = ? AND bloqueado_id = ?) OR (bloqueador_id = ? AND bloqueado_id = ?)");
    $stmt_check_blocked->execute([$remetente_id, $destinatario_id, $destinatario_id, $remetente_id]);
    if ($stmt_check_blocked->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'error' => 'Não é possível enviar mensagem. Usuário bloqueado.']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO mensagens_chat (remetente_id, destinatario_id, corpo_mensagem, data_envio, lida) VALUES (?, ?, ?, NOW(), 0)");
    $stmt->execute([$remetente_id, $destinatario_id, $corpo_mensagem]);

    echo json_encode(['success' => true, 'message' => 'Mensagem enviada com sucesso.']);

} catch (PDOException $e) {
    error_log("Erro ao enviar mensagem de chat em enviar_mensagemchat.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro interno do servidor ao enviar mensagem.']);
}
?>