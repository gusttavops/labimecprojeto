<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
    $extensao = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    $permitidas = array("jpg", "jpeg", "png", "gif");

    if (in_array($extensao, $permitidas)) {
        $novo_nome = uniqid() . "." . $extensao;
        $diretorio = "fotos_perfil/";
        move_uploaded_file($_FILES['foto']['tmp_name'], $diretorio . $novo_nome);

        require 'conexao.php';
        $id_usuario = $_SESSION['usuario'];
        $sql = "UPDATE usuarios SET foto_perfil = :foto WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['foto' => $novo_nome, 'id' => $id_usuario]);

        $_SESSION['foto_perfil'] = $novo_nome;

        header("Location: inicio.php");
        exit;
    } else {
        echo "Formato inválido. Envie JPG, PNG ou GIF.";
    }
} else {
    echo "Erro no upload.";
}
?>
