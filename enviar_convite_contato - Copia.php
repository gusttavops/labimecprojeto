<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Usuário não autenticado.']);
    exit;
}

require_once __DIR__ . '/includes/db.php';

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$destinatario_id = $input['destinatario_id'] ?? null;

if (!$destinatario_id) {
    echo json_encode(['error' => 'ID do destinatário não fornecido.']);
    exit;
}

if ($destinatario_id == $user_id) {
    echo json_encode(['error' => 'Você não pode enviar um convite para si mesmo.']);
    exit;
}

try {
    // Verificar se já existe um convite pendente ou contato aceito entre os dois
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM contatos
        WHERE (remetente_id = :user_id AND destinatario_id = :destinatario_id)
           OR (remetente_id = :destinatario_id AND destinatario_id = :user_id)
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':destinatario_id', $destinatario_id, PDO::PARAM_INT);
    $stmt->execute();
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['error' => 'Já existe um convite ou contato com este usuário.']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO contatos (remetente_id, destinatario_id, status, data_criacao, data_atualizacao)
        VALUES (:remetente_id, :destinatario_id, 'pendente', NOW(), NOW())
    ");
    $stmt->bindParam(':remetente_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':destinatario_id', $destinatario_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Convite enviado com sucesso.']);
    } else {
        echo json_encode(['error' => 'Não foi possível enviar o convite.']);
    }

} catch (PDOException $e) {
    error_log("Erro ao enviar convite de contato: " . $e->getMessage());
    echo json_encode(['error' => 'Erro interno do servidor.']);
}
?>