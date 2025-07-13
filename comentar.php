<?php
include 'conexao.php'; // ajuste conforme sua estrutura

session_start();
$id_usuario = $_SESSION['usuario_id'] ?? 1; // Simulado (pode trocar quando tiver login)

if (!isset($_POST['id_publicacao']) || !isset($_POST['comentario'])) {
    die("Comentário inválido.");
}

$id_publicacao = (int) $_POST['id_publicacao'];
$comentario = trim($_POST['comentario']);

try {
    $stmt = $pdo->prepare("INSERT INTO comentarios (id_publicacao, id_usuario, comentario) VALUES (?, ?, ?)");
    $stmt->execute([$id_publicacao, $id_usuario, $comentario]);

    header("Location: ver_publicacao.php?id=$id_publicacao");
    exit;
} catch (PDOException $e) {
    echo "Erro ao salvar comentário: " . $e->getMessage();
}
