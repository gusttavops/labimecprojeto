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
$contact_user_id = $input['contact_user_id'] ?? null; // O ID do usuário do contato a ser removido

if (!$contact_user_id) {
    echo json_encode(['error' => 'ID do contato a ser removido não fornecido.']);
    exit;
}

try {
    // Remove o registro de contato, independentemente de quem enviou/aceitou,
    // desde que ambos os IDs de usuário estejam envolvidos e o status seja 'aceito'.
    $stmt = $pdo->prepare("
        DELETE FROM contatos
        WHERE ((remetente_id = :user_id AND destinatario_id = :contact_user_id)
            OR (remetente_id = :contact_user_id AND destinatario_id = :user_id))
        AND status = 'aceito'
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':contact_user_id', $contact_user_id, PDO::PARAM_INT);

    if ($stmt->execute() && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Contato removido com sucesso.']);
    } else {
        echo json_encode(['error' => 'Contato não encontrado ou você não tem permissão para removê-lo.']);
    }

} catch (PDOException $e) {
    error_log("Erro ao remover contato: " . $e->getMessage());
    echo json_encode(['error' => 'Erro interno do servidor.']);
}
?>