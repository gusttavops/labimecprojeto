<?php
session_start();

// Diagnóstico para entender se a sessão está sendo mantida corretamente
if (!isset($_SESSION['id'])) {
    echo "<h3>⚠️ Sessão não ativa. Usuário não logado.</h3>";
    var_dump($_SESSION);
    exit;
}

$id_usuario = $_SESSION['id'];


require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mensagem = trim($_POST['mensagem'] ?? '');

    if (!empty($mensagem)) {
        $id_usuario = $_SESSION['id']; // ID correto do usuário logado

        try {
            $stmt = $pdo->prepare("INSERT INTO publicacoes (id_usuario, mensagem, data_publicacao) VALUES (?, ?, NOW())");
            $stmt->execute([$id_usuario, $mensagem]);

            // Sucesso: redireciona para a página inicial
            header("Location: inicio.php");
            exit;
        } catch (PDOException $e) {
            echo "<p>Erro ao salvar publicação: " . $e->getMessage() . "</p>";
            exit;
        }

    } else {
        // Mensagem vazia — redireciona com erro na sessão
        $_SESSION['erro_publicacao'] = "A mensagem não pode estar vazia.";
        header("Location: inicio.php");
        exit;
    }
} else {
    // Acesso direto: redireciona para início
    header("Location: inicio.php");
    exit;
}
