<?php
// Este arquivo é incluído em configuracoes.php
// As variáveis globais ($pdo, $user_id, $nome) de configuracoes.php estarão disponíveis aqui.

// Mensagens de feedback (sucesso/erro)
$status = $_GET['status'] ?? '';
if ($status == 'success_name') {
    echo '<p class="message success">Nome alterado com sucesso!</p>';
} elseif ($status == 'error_name') {
    echo '<p class="message error">Erro ao alterar nome. Tente novamente.</p>';
}

// Carregar o nome atual do usuário do banco de dados
$current_name = "Carregando..."; // Valor padrão
if ($user_id) { // Certifique-se de que $user_id está disponível
    try {
        $stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = :id");
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user_data) {
            $current_name = htmlspecialchars($user_data['nome']);
        }
    } catch (PDOException $e) {
        error_log("Erro ao carregar nome: " . $e->getMessage());
        $current_name = "Erro ao carregar nome.";
    }
}
?>

<h2>ALTERAR NOME</h2>
<form action="process_config.php" method="POST">
    <input type="hidden" name="action" value="update_name">
    <label for="novo_nome">Nome Atual: <?php echo $current_name; ?></label>
    <input type="text" id="novo_nome" name="new_name" placeholder="Novo Nome" required value="<?php echo $current_name; ?>">
    <button type="submit">Salvar</button>
</form>