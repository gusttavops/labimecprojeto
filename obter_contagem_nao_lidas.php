<?php
session_start();
header('Content-Type: application/json');

// Verifica se o usuário está logado e se o user_id está na sessão
if (!isset($_SESSION['usuario']) || !isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Usuário não autenticado.']);
    exit;
}

$user_id = $_SESSION['user_id'];

require_once __DIR__ . '/../includes/db.php'; // Ajuste o caminho conforme necessário

try {
    // Consulta para contar mensagens não lidas na caixa de entrada do usuário
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total_nao_lidas FROM mensagens WHERE destinatario_id = ? AND lida = 0 AND destinatario_apagou = 0");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['unread_count' => (int)$result['total_nao_lidas']]);

} catch (PDOException $e) {
    error_log("Erro ao obter contagem de não lidas: " . $e->getMessage());
    echo json_encode(['error' => 'Erro interno do servidor ao obter contagem.']);
}
?>