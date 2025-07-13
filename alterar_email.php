<?php
// Mensagens de feedback (sucesso/erro)
$status = $_GET['status'] ?? '';
$msg = $_GET['msg'] ?? '';

if ($status == 'success_email') {
    echo '<p class="message success">E-mail alterado com sucesso!</p>';
} elseif ($status == 'error_email') {
    $errorMessage = 'Erro ao alterar e-mail. Tente novamente.';
    if ($msg == 'email_exists') {
        $errorMessage = 'Este e-mail já está em uso por outro usuário.';
    }
    echo '<p class="message error">' . $errorMessage . '</p>';
}

// Carregar o e-mail atual do banco de dados
$current_email = "Carregando...";
if ($user_id) {
    try {
        $stmt = $pdo->prepare("SELECT email FROM usuarios WHERE id = :id");
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user_data) {
            $current_email = htmlspecialchars($user_data['email']);
        }
    } catch (PDOException $e) {
        error_log("Erro ao carregar email: " . $e->getMessage());
        $current_email = "Erro ao carregar email.";
    }
}
?>
<h2>ALTERAR E-MAIL</h2>
<form action="process_config.php" method="POST">
    <input type="hidden" name="action" value="update_email">
    <label for="novo_email">E-mail Atual: <?php echo $current_email; ?></label>
    <input type="email" id="novo_email" name="new_email" placeholder="Novo E-mail" required value="<?php echo $current_email; ?>">
    <button type="submit">Salvar</button>
</form>