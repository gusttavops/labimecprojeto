<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || !isset($_SESSION['nome']) || !isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Não autenticado.']);
    exit;
}

require_once __DIR__ . '/../includes/db.php';
$current_user_id = $_SESSION['user_id'];
$message_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$caixa = filter_input(INPUT_GET, 'caixa', FILTER_SANITIZE_STRING); // Para saber se é inbox ou sent, para permissão e marcar como lida

if (!$message_id) {
    echo json_encode(['error' => 'ID da mensagem inválido.']);
    exit;
}

try {
    // Busca os detalhes da mensagem
    $stmt = $pdo->prepare("
        SELECT
            m.id,
            m.assunto,
            m.corpo_mensagem,
            m.data_envio,
            m.lida,
            m.remetente_id,
            u_rem.nome AS remetente_nome,
            m.destinatario_id,
            u_dest.nome AS destinatario_nome
        FROM
            mensagens m
        JOIN
            usuarios u_rem ON m.remetente_id = u_rem.id
        JOIN
            usuarios u_dest ON m.destinatario_id = u_dest.id
        WHERE
            m.id = ? AND (m.destinatario_id = ? OR m.remetente_id = ?)
    ");
    $stmt->execute([$message_id, $current_user_id, $current_user_id]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$message) {
        echo json_encode(['error' => 'Mensagem não encontrada ou acesso negado.']);
        exit;
    }

    // Se for mensagem na caixa de entrada do usuário logado e não estiver lida, marca como lida
    if ($caixa === 'entrada' && $message['destinatario_id'] == $current_user_id && !$message['lida']) {
        $update_stmt = $pdo->prepare("UPDATE mensagens SET lida = TRUE WHERE id = ?");
        $update_stmt->execute([$message_id]);
        $message['lida'] = true; // Atualiza o status na resposta para o frontend
    }

    $date = new DateTime($message['data_envio']);
    $message['data_envio_formatado'] = $date->format('d/m/Y H:i'); // Formato completo para detalhes

    echo json_encode(['success' => true, 'message' => $message]);

} catch (PDOException $e) {
    error_log("Erro ao obter detalhes da mensagem: " . $e->getMessage());
    echo json_encode(['error' => 'Erro ao carregar detalhes da mensagem.']);
}
?>