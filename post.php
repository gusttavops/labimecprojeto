<!-- post.php -->
<?php
include 'conexao.php'; // conexão com o banco

$id = $_GET['id'] ?? 0;

$sql = "SELECT publicacoes.*, usuarios.usuario, usuarios.foto_perfil
        FROM publicacoes
        JOIN usuarios ON publicacoes.id_usuario = usuarios.id
        WHERE publicacoes.id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $id]);
$post = $stmt->fetch();

if (!$post) {
    echo "Postagem não encontrada!";
    exit;
}
?> <!-- ✅ FECHOU o PHP aqui -->

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Postagem</title>
  <style>
    .post-image {
      max-width: 100%;
      border-radius: 10px;
      margin-top: 10px;
    }
  </style>
</head>
<body>

  <p><strong><?php echo htmlspecialchars($post['usuario']); ?></strong></p>
  <p><small><?php echo date('d/m/Y H:i', strtotime($post['data_publicacao'])); ?></small></p>
  <p><?php echo nl2br(htmlspecialchars($post['mensagem'])); ?></p>

  <?php if (!empty($post['imagem'])): ?>
    <img src="uploads/<?php echo htmlspecialchars($post['imagem']); ?>" class="post-image">
  <?php endif; ?>

  <p><a href="inicio.php">← Voltar</a></p>

</body>
</html>
