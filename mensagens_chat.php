<?php
// api/mensagens_chat.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Usuário não autenticado.']);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$partner_id = $_GET['partner_id'] ?? null;

if (empty($partner_id)) {
    echo json_encode(['error' => 'ID do parceiro de chat não fornecido.']);
    exit;
}

require_once __DIR__ . '/../includes/db.php'; // Ajuste o caminho

try {
    $stmt = $pdo->prepare("
        SELECT
            id,
            remetente_id,
            destinatario_id,
            corpo_mensagem,
            data_envio,
            lida
        FROM
            mensagens_chat
        WHERE
            (remetente_id = ? AND destinatario_id = ?)
            OR (remetente_id = ? AND destinatario_id = ?)
        ORDER BY
            data_envio ASC
    ");
    $stmt->execute([$current_user_id, $partner_id, $partner_id, $current_user_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatar a data para exibição no frontend
    foreach ($messages as &$msg) {
        $msg['data_envio_formatado'] = (new DateTime($msg['data_envio']))->format('d/m/Y H:i');
    }

    echo json_encode(['success' => true, 'messages' => $messages]);

} catch (PDOException $e) {
    error_log("Erro ao obter mensagens de chat em mensagens_chat.php: " . $e->getMessage());
    echo json_encode(['error' => 'Erro interno do servidor ao carregar mensagens.']);
}
?>