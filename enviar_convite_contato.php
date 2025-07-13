<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Usuário não autenticado.']);
    exit;
}

require_once __DIR__ . '/includes/db.php'; // Certifique-se de que este caminho está correto para sua conexão PDO

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
    // CORREÇÃO: Usando nomes de parâmetros únicos para evitar SQLSTATE[HY093]: Invalid parameter number
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM contatos
        WHERE (remetente_id = :user_id_a AND destinatario_id = :destinatario_id_a)
           OR (remetente_id = :destinatario_id_b AND destinatario_id = :user_id_b)
    ");
    $stmt->bindParam(':user_id_a', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':destinatario_id_a', $destinatario_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id_b', $user_id, PDO::PARAM_INT); // O valor é o mesmo, mas o placeholder é diferente
    $stmt->bindParam(':destinatario_id_b', $destinatario_id, PDO::PARAM_INT); // O valor é o mesmo, mas o placeholder é diferente
    $stmt->execute();
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['error' => 'Já existe um convite ou contato com este usuário.']);
        exit;
    }

    // A query de INSERT permanece a mesma, pois nela os parâmetros são usados apenas uma vez.
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
    // É crucial que esta linha esteja configurada para registrar em um arquivo de log acessível
    error_log("Erro ao enviar convite de contato: " . $e->getMessage());
    echo json_encode(['error' => 'Erro interno do servidor.']);
}
?>