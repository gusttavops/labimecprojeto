<?php
session_start();
include("conexao.php");

$usuario_id = $_SESSION['usuario_id'];

// Buscar mensagens do banco
$query = "SELECT m.mensagem, m.data_envio, u.nome 
          FROM mensagens m
          JOIN usuarios u ON m.usuario_id = u.id
          WHERE m.destinatario_id = ? OR m.usuario_id = ?
          ORDER BY m.data_envio DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([$usuario_id, $usuario_id]);
$mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($mensagens as $msg) {
    echo '<div class="message"><span class="sender">' . htmlspecialchars($msg['nome']) . ':</span><p class="content">' . nl2br(htmlspecialchars($msg['mensagem'])) . '</p><small>' . date('d/m/Y H:i', strtotime($msg['data_envio'])) . '</small></div>';
}
?>
