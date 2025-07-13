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
        SELECT
            c.id,
            u.id AS remetente_id,
            u.nome AS remetente_nome,
            u.foto_perfil AS remetente_foto_perfil,
            c.data_criacao
        FROM
            contatos c
        JOIN
            usuarios u ON u.id = c.remetente_id
        WHERE
            c.destinatario_id = :user_id AND c.status = 'pendente'
        ORDER BY
            c.data_criacao DESC
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $invites = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'invites' => $invites]);

} catch (PDOException $e) {
    error_log("Erro ao obter convites pendentes: " . $e->getMessage());
    echo json_encode(['error' => 'Erro interno do servidor.']);
}
?>