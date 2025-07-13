<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$host = "localhost";
$db = "sistema_login";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão com o banco de dados: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao'])) {
    $nome = $_POST['nome'];
    $idade = $_POST['idade'];
    $genero = $_POST['genero'];
    $data_desaparecimento = $_POST['data_desaparecimento'];
    $local_desaparecimento = $_POST['local_desaparecimento'];
    $descricao = $_POST['descricao'];
    $usuario_id = $_SESSION['usuario_id'];

    $imagem = null;
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
        $ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
        $imagem_nome = uniqid() . "." . $ext;
        $caminho_destino = "uploads/" . $imagem_nome;

        if (move_uploaded_file($_FILES['imagem']['tmp_name'], $caminho_destino)) {
            $imagem = $imagem_nome;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO desaparecidos (nome, idade, genero, data_desaparecimento, local_desaparecimento, descricao, imagem, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nome, $idade, $genero, $data_desaparecimento, $local_desaparecimento, $descricao, $imagem, $usuario_id]);

    header("Location: inicio.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registrar Desaparecimento - Sumidos PB</title>
  <link rel="stylesheet" href="style.css"> <!-- Substitua se necessário -->
</head>
<body>
  <h1>Registrar Desaparecimento</h1>

  <form action="registrar_desaparecido.php" method="POST" enctype="multipart/form-data">
    <?php include 'campos_formulario_desaparecido.php'; ?>
    <button type="submit" name="acao">Registrar</button>
  </form>
</body>
</html>
