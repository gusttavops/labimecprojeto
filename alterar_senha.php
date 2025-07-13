<?php
// Mensagens de feedback (sucesso/erro)
$status = $_GET['status'] ?? '';
$msg = $_GET['msg'] ?? '';

if ($status == 'success_password') {
    echo '<p class="message success">Senha alterada com sucesso!</p>';
} elseif ($status == 'error_password') {
    $errorMessage = 'Erro ao alterar senha. Tente novamente.';
    if ($msg == 'empty_fields') {
        $errorMessage = 'Por favor, preencha todos os campos.';
    } elseif ($msg == 'passwords_mismatch') {
        $errorMessage = 'A nova senha e a confirmação não correspondem.';
    } elseif ($msg == 'short_password') {
        $errorMessage = 'A nova senha deve ter no mínimo 6 caracteres.';
    } elseif ($msg == 'incorrect_current_password') {
        $errorMessage = 'A senha atual está incorreta.';
    }
    echo '<p class="message error">' . $errorMessage . '</p>';
}
?>
<h2>ALTERAR SENHA</h2>
<form action="process_config.php" method="POST">
    <input type="hidden" name="action" value="update_password">
    <label for="senha_atual">Senha Atual:</label>
    <input type="password" id="senha_atual" name="current_password" required>
    <label for="nova_senha">Nova Senha:</label>
    <input type="password" id="nova_senha" name="new_password" required>
    <label for="confirmar_senha">Confirmar Nova Senha:</label>
    <input type="password" id="confirmar_senha" name="confirm_password" required>
    <button type="submit">Salvar</button>
</form>