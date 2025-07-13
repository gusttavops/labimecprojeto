<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$id_usuario = $_SESSION['usuario_id'];
$id_publicacao = $_POST['id_publicacao'] ?? null;

// Evita curtida duplicada
$stmt = $pdo->prepare("SELECT COUNT(*) FROM curtidas WHERE id_publicacao = ? AND id_usuario = ?");
$stmt->execute([$id_publicacao, $id_usuario]);
if ($stmt->fetchColumn() == 0) {
    $inserir = $pdo->prepare("INSERT INTO curtidas (id_publicacao, id_usuario) VALUES (?, ?)");
    $inserir->execute([$id_publicacao, $id_usuario]);
}

header("Location: inicio.php");
exit;
