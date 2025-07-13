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
            contatos c
        JOIN
            usuarios u ON u.id = c.destinatario_id
        WHERE
            c.remetente_id = :user_id1 AND c.status = 'aceito'
        UNION
        SELECT
            u.id,
            u.nome,
            u.foto_perfil
        FROM
            contatos c
        JOIN
            usuarios u ON u.id = c.remetente_id
        WHERE
            c.destinatario_id = :user_id2 AND c.status = 'aceito'
        ORDER BY nome ASC
    ");
    $stmt->bindParam(':user_id1', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id2', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'contacts' => $contacts]);

} catch (PDOException $e) {
    error_log("Erro ao obter meus contatos: " . $e->getMessage());
    echo json_encode(['error' => 'Erro interno do servidor.']);
}
