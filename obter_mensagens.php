<?php
session_start();
header('Content-Type: application/json'); // Mantenha esta linha

if (!isset($_SESSION['usuario']) || !isset($_SESSION['nome']) || !isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Não autenticado.']);
    exit;
}

require_once __DIR__ . '/../includes/db.php'; // Caminho corrigido com ../
$current_user_id = $_SESSION['user_id'];

// LINHA CORRIGIDA
$caixa = filter_input(INPUT_GET, 'caixa', FILTER_UNSAFE_RAW); // ou outra forma de validação/sanitização simples para strings


$query = "";
$params = [$current_user_id];

if ($caixa === 'enviadas') {
    $query = "
        SELECT
            m.id,
            m.assunto,
            m.corpo_mensagem,
            m.data_envio,
            u_dest.nome AS destinatario_nome,
            m.lida -- Mesmo na caixa de enviados, para indicar se o destinatário leu
        FROM
            mensagens m
        JOIN
            usuarios u_dest ON m.destinatario_id = u_dest.id
        WHERE
            m.remetente_id = ? AND m.apagada_por_remetente = FALSE
        ORDER BY
            m.data_envio DESC
    ";
} else { // Padrão: 'entrada'
    $query = "
        SELECT
            m.id,
            m.assunto,
            m.corpo_mensagem,
            m.data_envio,
            m.lida,
            u_rem.nome AS remetente_nome
        FROM
            mensagens m
        JOIN
            usuarios u_rem ON m.remetente_id = u_rem.id
        WHERE
            m.destinatario_id = ? AND m.apagada_por_destinatario = FALSE
        ORDER BY
            m.data_envio DESC
    ";
}

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($messages as &$msg) {
        $date = new DateTime($msg['data_envio']);
        $now = new DateTime();
        $yesterday = (new DateTime())->modify('-1 day');

        if ($date->format('Y-m-d') === $now->format('Y-m-d')) {
            $msg['data_envio_formatado'] = $date->format('H:i');
        } elseif ($date->format('Y-m-d') === $yesterday->format('Y-m-d')) {
            $msg['data_envio_formatado'] = 'Ontem';
        } else {
            $msg['data_envio_formatado'] = $date->format('d/m/Y');
        }
    }

    echo json_encode(['success' => true, 'messages' => $messages]);

} catch (PDOException $e) {
    error_log("Erro ao obter mensagens para {$caixa}: " . $e->getMessage());
    echo json_encode(['error' => 'Erro ao carregar mensagens.']);
}
?>