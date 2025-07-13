<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Usuário não autenticado.']);
    exit;
}

require_once __DIR__ . '/includes/db.php';

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT
            u.id,
            u.nome,
            u.foto_perfil
        FROM
            usuarios u
        WHERE
            u.id != :user_id_1
            AND u.id NOT IN (
                SELECT destinatario_id FROM contatos WHERE remetente_id = :user_id_2
                UNION
                SELECT remetente_id FROM contatos WHERE destinatario_id = :user_id_3
            )
        ORDER BY
            u.nome ASC
    ");
    $stmt->bindParam(':user_id_1', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id_2', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id_3', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'suggestions' => $suggestions]);

} catch (PDOException $e) {
    error_log("Erro ao obter sugestões de contatos: " . $e->getMessage());
    echo json_encode(['error' => 'Erro interno do servidor.']);
}
