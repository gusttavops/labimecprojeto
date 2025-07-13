<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || !isset($_SESSION['nome']) || !isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Não autenticado.']);
    exit;
}

require_once __DIR__ . '/../includes/db.php';
$current_user_id = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);

$destinatario_id = filter_var($data['destinatario_id'] ?? null, FILTER_VALIDATE_INT);
$assunto = trim($data['assunto'] ?? '');
$corpo_mensagem = trim($data['corpo_mensagem'] ?? '');

if (!$destinatario_id || empty($assunto) || empty($corpo_mensagem)) {
    echo json_encode(['error' => 'Dados incompletos para enviar a mensagem.']);
    exit;
}

// Opcional: Verificar se o destinatário existe
try {
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
    $stmt->execute([$destinatario_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['error' => 'Destinatário não encontrado.']);
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro ao verificar destinatário: " . $e->getMessage());
    echo json_encode(['error' => 'Erro interno ao verificar destinatário.']);
    exit;
}


try {
    $stmt = $pdo->prepare("
        INSERT INTO mensagens (remetente_id, destinatario_id, assunto, corpo_mensagem)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$current_user_id, $destinatario_id, $assunto, $corpo_mensagem]);

    echo json_encode(['success' => true, 'message_id' => $pdo->lastInsertId()]);

} catch (PDOException $e) {
    error_log("Erro ao enviar mensagem: " . $e->getMessage());
    echo json_encode(['error' => 'Erro ao enviar mensagem.']);
}
?>