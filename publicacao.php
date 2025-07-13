<?php
include 'conexao.php'; // use sua conexão

$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM publicacoes WHERE id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch();

$stmtComentarios = $pdo->prepare("SELECT * FROM comentarios WHERE id_publicacao = ?");
$stmtComentarios->execute([$id]);
$comentarios = $stmtComentarios->fetchAll(PDO::FETCH_ASSOC);
?>

<h1><?= htmlspecialchars($post["mensagem"]) ?></h1>

<h2>Comentários:</h2>
<?php foreach ($comentarios as $comentario): ?>
  <div>
    <strong><?= htmlspecialchars($comentario["autor"]) ?>:</strong>
    <?= htmlspecialchars($comentario["comentario"]) ?>
  </div>
<?php endforeach; ?>
