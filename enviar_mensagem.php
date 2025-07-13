<?php
session_start();
include("conexao.php");

// chat.php (ou api/enviar_mensagem.php)

// ... (código existente) ...

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mensagem']) && isset($_POST['id_destinatario'])) {
    $id_remetente = $_SESSION['user_id']; // ID do usuário logado
    $id_destinatario = $_POST['id_destinatario'];
    $mensagem = $_POST['mensagem'];

    // 1. **VERIFICAÇÃO DE CONTATO**
    // Verifica se os dois usuários são contatos mútuos (ou se o remetente tem o destinatário como contato)
    // Usando 'remetente_id' e 'destinatario_id' da sua tabela 'contatos'
    $stmt_check_contact = $pdo->prepare("
        SELECT COUNT(*)
        FROM contatos
        WHERE ((remetente_id = ? AND destinatario_id = ?) OR (remetente_id = ? AND destinatario_id = ?))
        AND status = 'aceito'
    ");
    $stmt_check_contact->execute([$id_remetente, $id_destinatario, $id_destinatario, $id_remetente]);
    $is_contact = $stmt_check_contact->fetchColumn();

    if ($is_contact > 0) {
        // São contatos, pode enviar a mensagem
        // ... (código para inserir a mensagem na tabela 'mensagens') ...
    } else {
        // Não são contatos, impede o envio da mensagem
        // ... (código para exibir erro) ...
    }
}
?>
