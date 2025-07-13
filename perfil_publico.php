<?php
require_once 'conexao.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "ID de usuário inválido.";
    exit;
}

$usuario_id = $_GET['id'];

$stmt = $pdo->prepare("SELECT nome, usuario, foto_perfil, foto_capa FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    echo "Usuário não encontrado.";
    exit;
}

$foto_perfil = $usuario['foto_perfil'] ?? 'https://via.placeholder.com/100';
$foto_capa = $usuario['foto_capa'] ?? 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d';

// Buscar publicações públicas
$stmt = $pdo->prepare("SELECT * FROM publicacoes WHERE id_usuario = ? ORDER BY data_publicacao DESC");
$stmt->execute([$usuario_id]);
$publicacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Perfil Público</title>
</head>
<body>
  <h2>Perfil de <?= htmlspecialchars($usuario['nome']) ?></h2>
  <div style="background:url('<?= $foto_capa ?>');height:200px;background-size:cover;"></div>
  <img src="<?= $foto_perfil ?>" width="100" height="100" style="border-radius:50%">
  <p>@<?= htmlspecialchars($usuario['usuario']) ?></p>

  <h3>Publicações</h3>
  <?php foreach ($publicacoes as $pub): ?>
    <div style="background:#f0f0f0;padding:10px;margin:10px 0;">
      <p><?= nl2br(htmlspecialchars($pub['mensagem'])) ?></p>
      <small><?= date('d/m/Y H:i', strtotime($pub['data_publicacao'])) ?></small>
    </div>
  <?php endforeach; ?>
</body>
</html>
