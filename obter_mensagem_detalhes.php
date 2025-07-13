<?php
session_start();
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff'); // Adicionado: Cabeçalho de segurança para prevenir MIME-sniffing.

// Adicionado: Log de início para depuração.
error_log("obter_detalhes_mensagem.php: Iniciando execução.");

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    // Adicionado: Log para caso de usuário não autenticado.
    error_log("obter_detalhes_mensagem.php: Usuário não autenticado. Exibindo erro.");
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado.']);
    exit;
}

// O caminho para db.php parece correto. Certifique-se de que o arquivo existe e as credenciais estão corretas.
// O __DIR__ garante que o caminho é absoluto a partir do diretório atual do script.
require_once __DIR__ . '/../includes/db.php';

// Adicionado: Log para verificar se a conexão com o DB foi incluída.
// A variável $pdo vem de includes/db.php, então verificar se ela existe pode indicar sucesso na inclusão.
error_log("obter_detalhes_mensagem.php: db.php incluído. PDO status: " . (isset($pdo) ? 'Conectado (variavel $pdo existe)' : 'Falha na inclusao de db.php ou variavel $pdo nao existe'));


$user_id = $_SESSION['user_id']; // ID do usuário logado.
$message_id = isset($_GET['id']) ? (int)$_GET['id'] : 0; // ID da mensagem a ser visualizada, convertido para inteiro.
$caixa = isset($_GET['caixa']) ? $_GET['caixa'] : 'entrada'; // Tipo de caixa: 'entrada' ou 'enviadas', padrão 'entrada'.

// Adicionado: Log dos parâmetros recebidos via GET.
error_log("obter_detalhes_mensagem.php: Parâmetros recebidos - message_id=" . $message_id . ", caixa=" . $caixa . ", user_id_sessao=" . $user_id);


if ($message_id === 0) {
    // Adicionado: Log para ID de mensagem inválido.
    error_log("obter_detalhes_mensagem.php: ID da mensagem inválido ou ausente (message_id=0).");
    echo json_encode(['success' => false, 'error' => 'ID da mensagem inválido.']);
    exit;
}

try {
    $message = null; // Variável para armazenar os detalhes da mensagem.

    if ($caixa === 'entrada') {
        // Adicionado: Log para indicar que a busca será na caixa de entrada.
        error_log("obter_detalhes_mensagem.php: Buscando na caixa de entrada (recebidas) para message_id: " . $message_id . " e destinatario_id: " . $user_id);
        // Consulta para a caixa de entrada (mensagens recebidas)
        // JOIN com a tabela 'usuarios' para obter o nome do remetente.
        $stmt = $pdo->prepare("
            SELECT
                m.id,
                m.assunto,
                m.corpo_mensagem,
                m.data_envio,
                m.lida,
                remetente.id AS remetente_id,
                remetente.nome AS remetente_nome
            FROM
                mensagens m
            JOIN
                usuarios remetente ON m.remetente_id = remetente.id
            WHERE
                m.id = ? AND m.destinatario_id = ? AND m.destinatario_apagou = 0
            LIMIT 1
        ");
        // Executa a consulta com os parâmetros.
        $stmt->execute([$message_id, $user_id]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC); // Obtém a primeira (e única) linha como um array associativo.

        // Adicionado: Log do resultado da busca na caixa de entrada.
        error_log("obter_detalhes_mensagem.php: Resultado da busca (entrada): " . ($message ? "Mensagem encontrada." : "Mensagem não encontrada ou sem permissão."));


        // Se a mensagem for encontrada e for da caixa de entrada, marcá-la como lida
        if ($message && $message['lida'] == 0) {
            // Adicionado: Log ao marcar mensagem como lida.
            error_log("obter_detalhes_mensagem.php: Marcando mensagem " . $message_id . " como lida.");
            $stmt_mark_read = $pdo->prepare("UPDATE mensagens SET lida = 1 WHERE id = ?");
            $stmt_mark_read->execute([$message_id]);
            $message['lida'] = 1; // Atualiza o status na resposta para refletir a mudança imediatamente.
        }

    } elseif ($caixa === 'enviadas') {
        // Adicionado: Log para indicar que a busca será na caixa de enviadas.
        error_log("obter_detalhes_mensagem.php: Buscando na caixa de enviadas (enviadas por este usuario) para message_id: " . $message_id . " e remetente_id: " . $user_id);
        // Consulta para a caixa de mensagens enviadas
        // JOIN com a tabela 'usuarios' para obter o nome do destinatário.
        $stmt = $pdo->prepare("
            SELECT
                m.id,
                m.assunto,
                m.corpo_mensagem,
                m.data_envio,
                m.lida, -- 'lida' aqui se refere ao status para o DESTINATÁRIO, não para quem enviou.
                destinatario.id AS destinatario_id,
                destinatario.nome AS destinatario_nome
            FROM
                mensagens m
            JOIN
                usuarios destinatario ON m.destinatario_id = destinatario.id
            WHERE
                m.id = ? AND m.remetente_id = ? AND m.remetente_apagou = 0
            LIMIT 1
        ");
        // Executa a consulta com os parâmetros.
        $stmt->execute([$message_id, $user_id]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC); // Obtém a primeira (e única) linha como um array associativo.

        // Adicionado: Log do resultado da busca na caixa de enviadas.
        error_log("obter_detalhes_mensagem.php: Resultado da busca (enviadas): " . ($message ? "Mensagem encontrada." : "Mensagem não encontrada ou sem permissão."));

        // Para mensagens enviadas, o "remetente" é sempre o usuário logado (quem está visualizando).
        // Assim, definimos o nome do remetente para exibição na interface de detalhes.
        if ($message) {
            // Adicionado: Log ao definir remetente para mensagem enviada.
            error_log("obter_detalhes_mensagem.php: Definindo remetente para mensagem enviada (o proprio usuario).");
            $message['remetente_id'] = $user_id; // O remetente é o próprio usuário
            $message['remetente_nome'] = $_SESSION['nome']; // O nome do remetente é o nome do usuário logado
        }
    }

    if ($message) {
        // Formatar a data para exibição em um formato legível.
        // Adicionado: Log da mensagem encontrada e formatação de data.
        error_log("obter_detalhes_mensagem.php: Mensagem encontrada. Formatando data e preparando JSON de sucesso.");
        $message['data_envio_formatado'] = (new DateTime($message['data_envio']))->format('d/m/Y H:i');
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        // Adicionado: Log se a mensagem não for encontrada ou se o usuário não tiver permissão para acessá-la.
        error_log("obter_detalhes_mensagem.php: Mensagem NAO encontrada para ID " . $message_id . " na caixa " . $caixa . " ou usuario " . $user_id . " sem permissao.");
        echo json_encode(['success' => false, 'error' => 'Mensagem não encontrada ou você não tem permissão para acessá-la.']);
    }

} catch (PDOException $e) {
    // Captura exceções específicas do PDO (erros de banco de dados).
    // Adicionado: Log de erro PDO detalhado, incluindo a mensagem de erro da exceção.
    error_log("obter_detalhes_mensagem.php: ERRO PDO ao obter detalhes da mensagem (ID: {$message_id}, UserID: {$user_id}): " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro interno do servidor.']);
} catch (Exception $e) {
    // Captura outras exceções gerais que não sejam do PDO.
    // Adicionado: Log de outros erros gerais, incluindo a mensagem de erro da exceção.
    error_log("obter_detalhes_mensagem.php: ERRO Geral (nao PDO) ao obter detalhes da mensagem (ID: {$message_id}, UserID: {$user_id}): " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro interno do servidor.']);
}
// Adicionado: Log de finalização da execução do script.
error_log("obter_detalhes_mensagem.php: Finalizando execução.");
?>