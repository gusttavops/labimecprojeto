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
$convite_id = $input['convite_id'] ?? null;

if (!$convite_id) {
    echo json_encode(['error' => 'ID do convite não fornecido.']);
    exit;
}

try {
    // Verifica se o convite existe e se o usuário logado é o destinatário
    $stmt = $pdo->prepare("
        UPDATE contatos
        SET status = 'recusado', data_atualizacao = NOW()
        WHERE id = :convite_id AND destinatario_id = :user_id AND status = 'pendente'
    ");
    $stmt->bindParam(':convite_id', $convite_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

    if ($stmt->execute() && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Convite recusado com sucesso.']);
    } else {
        echo json_encode(['error' => 'Convite não encontrado ou você não tem permissão para recusá-lo, ou já foi aceito/recusado.']);
    }

} catch (PDOException $e) {
    error_log("Erro ao recusar convite de contato: " . $e->getMessage());
    echo json_encode(['error' => 'Erro interno do servidor.']);
}
?>