<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || !isset($_SESSION['nome']) || !isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Não autenticado.']);
    exit;
}

require_once __DIR__ . '/../includes/db.php';
$current_user_id = $_SESSION['user_id'];

try {
    // Obter todos os usuários, excluindo o usuário logado
    $stmt = $pdo->prepare("SELECT id, nome, usuario FROM usuarios WHERE id != ? ORDER BY nome ASC");
    $stmt->execute([$current_user_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'users' => $users]);

} catch (PDOException $e) {
    error_log("Erro ao obter lista de usuários: " . $e->getMessage());
    echo json_encode(['error' => 'Erro ao carregar usuários.']);
}
?>