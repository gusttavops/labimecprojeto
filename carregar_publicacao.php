<?php
if (!isset($_GET['id'])) {
  echo "<p>ID da publicação não fornecido.</p>";
  exit;
}

$id = (int) $_GET['id'];

try {
  $pdo = new PDO("mysql:host=localhost;dbname=sistema_login;charset=utf8", "root", "");
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $stmt = $pdo->prepare("SELECT p.*, u.usuario, u.foto_perfil FROM publicacoes p JOIN usuarios u ON p.id_usuario = u.id WHERE p.id = ?");
  $stmt->execute([$id]);
  $post = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($post) {
    echo "<div style='display:flex; align-items:center; gap:10px; margin-bottom:10px;'>";
    echo "<img src='" . (!empty($post['foto_perfil']) ? htmlspecialchars($post['foto_perfil']) : 'https://exemplo.com/default.png') . "' style='width:50px; height:50px; border-radius:50%;'>";
    echo "<div>";
    echo "<strong>" . htmlspecialchars($post['usuario']) . "</strong><br>";
    echo "<span style='font-size:12px; color:#888;'>" . date('d/m/Y H:i', strtotime($post['data_publicacao'])) . "</span>";
    echo "</div></div>";

    echo "<p>" . nl2br(htmlspecialchars($post['mensagem'])) . "</p>";

    if (!empty($post['imagem'])) {
      echo "<img src='uploads/" . htmlspecialchars($post['imagem']) . "' style='max-width:100%; margin-top:10px; border-radius:10px;'>";
    }
  } else {
    echo "<p>Publicação não encontrada.</p>";
  }
} catch (PDOException $e) {
  echo "<p>Erro: " . $e->getMessage() . "</p>";
}
?>
