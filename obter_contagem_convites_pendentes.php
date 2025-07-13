<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Usuário não autenticado.']);
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS count
        FROM contatos
        WHERE destinatario_id = :user_id AND status = 'pendente'
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'count' => $result['count']]);

} catch (PDOException $e) {
    error_log("Erro ao obter contagem de convites pendentes: " . $e->getMessage());
    echo json_encode(['error' => 'Erro interno do servidor.']);
}
?>