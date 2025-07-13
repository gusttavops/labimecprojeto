<?php
session_start();
// Supondo que o id do usuário está armazenado na sessão
$usuario_id = $_SESSION['usuario_id'] ?? null;

if (!$usuario_id) {
    header("Location: login.php");
    exit;
}

// Conexão com o banco de dados
$conn = new mysqli('localhost', 'usuario_db', 'senha_db', 'nome_db');
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

$novo_nome = trim($_POST['novo_nome']);

if (!empty($novo_nome)) {
    $stmt = $conn->prepare("UPDATE usuarios SET nome = ? WHERE id = ?");
    $stmt->bind_param("si", $novo_nome, $usuario_id);
    if ($stmt->execute()) {
        // Sucesso: redireciona ou exibe mensagem
        header("Location: configuracoes.php?msg=Nome alterado com sucesso");
    } else {
        echo "Erro ao atualizar nome.";
    }
    $stmt->close();
} else {
    echo "Nome não pode ser vazio.";
}

$conn->close();
?>