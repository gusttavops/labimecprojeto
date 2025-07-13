<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mensagem = trim($_POST['mensagem'] ?? '');

    if (!empty($mensagem)) {
        $id_usuario = $_SESSION['usuario_id']; // ID numérico correto do usuário

        $stmt = $pdo->prepare("INSERT INTO publicacoes (id_usuario, mensagem, data_publicacao) VALUES (?, ?, NOW())");
        $stmt->execute([$id_usuario, $mensagem]);

        header("Location: inicio.php");
        exit;
    } else {
        // Mensagem vazia — opcional: pode exibir erro
        $_SESSION['erro_publicacao'] = "A mensagem não pode estar vazia.";
        header("Location: inicio.php");
        exit;
    }
} else {
    header("Location: inicio.php");
    exit;
}
