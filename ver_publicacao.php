<?php
require 'conexao.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Postagem inválida.";
    exit;
}

$id = (int) $_GET['id'];

$sql = "SELECT publicacoes.*, usuarios.usuario, usuarios.foto_perfil
        FROM publicacoes
        JOIN usuarios ON publicacoes.id_usuario = usuarios.id
        WHERE publicacoes.id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    echo "Postagem não encontrada.";
    exit;
}
?>

<div class="post-detalhada">
  <h3><?php echo htmlspecialchars($post['usuario']); ?></h3>
  <p><small><?php echo date('d/m/Y H:i', strtotime($post['data_publicacao'])); ?></small></p>
  <p><?php echo nl2br(htmlspecialchars($post['mensagem'])); ?></p>

  <?php if (!empty($post['imagem'])): ?>
    <img src="uploads/<?php echo htmlspecialchars($post['imagem']); ?>" style="max-width:100%; margin-top:10px;">
  <?php endif; ?>
</div>
