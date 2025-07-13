<?php
// Mensagens de feedback (sucesso/erro)
$status = $_GET['status'] ?? '';
$msg = $_GET['msg'] ?? '';

if ($status == 'success_dob') {
    echo '<p class="message success">Data de nascimento alterada com sucesso!</p>';
} elseif ($status == 'error_dob') {
    $errorMessage = 'Erro ao alterar data de nascimento.';
    if ($msg == 'invalid_format') {
        $errorMessage = 'Formato de data inválido. Use AAAA-MM-DD.';
    } elseif ($msg == 'invalid_date') {
        $errorMessage = 'Data inválida.';
    }
    echo '<p class="message error">' . $errorMessage . '</p>';
}

// Carregar a data de nascimento atual
$current_dob = "";
if ($user_id) {
    try {
        $stmt = $pdo->prepare("SELECT data_nascimento FROM usuarios WHERE id = :id"); // Assumindo 'data_nascimento' para a coluna
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user_data && !empty($user_data['data_nascimento'])) {
            $current_dob = htmlspecialchars($user_data['data_nascimento']);
        }
    } catch (PDOException $e) {
        error_log("Erro ao carregar data de nascimento: " . $e->getMessage());
        $current_dob = ""; // Ou mensagem de erro
    }
}
?>
<h2>ALTERAR DATA DE NASCIMENTO</h2>
<form action="process_config.php" method="POST">
    <input type="hidden" name="action" value="update_dob">
    <label for="nova_data_nascimento">Data de Nascimento Atual: <?php echo ($current_dob ?: 'Não informada'); ?></label>
    <input type="date" id="nova_data_nascimento" name="new_dob" required value="<?php echo $current_dob; ?>">
    <button type="submit">Salvar</button>
</form>