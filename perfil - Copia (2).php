<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['nome'])) {
    header("Location: login.php");
    exit;
}

$usuario_logado_id = $_SESSION['usuario_id'];
$perfil_id = isset($_GET['id']) ? intval($_GET['id']) : $usuario_logado_id;
$eh_dono_do_perfil = ($usuario_logado_id === $perfil_id);

// Atualizar foto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_fotos'])) {
    if (!empty($_FILES['nova_foto_perfil']['tmp_name'])) {
        $ext = pathinfo($_FILES['nova_foto_perfil']['name'], PATHINFO_EXTENSION);
        $nome_arquivo = 'perfil_' . $perfil_id . '_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['nova_foto_perfil']['tmp_name'], 'fotos_perfil/' . $nome_arquivo);
        $stmt = $pdo->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id = ?");
        $stmt->execute(['fotos_perfil/' . $nome_arquivo, $perfil_id]);
    }

    if (!empty($_FILES['nova_foto_capa']['tmp_name'])) {
        $ext = pathinfo($_FILES['nova_foto_capa']['name'], PATHINFO_EXTENSION);
        $nome_arquivo = 'capa_' . $perfil_id . '_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['nova_foto_capa']['tmp_name'], 'fotos_capa/' . $nome_arquivo);
        $stmt = $pdo->prepare("UPDATE usuarios SET foto_capa = ? WHERE id = ?");
        $stmt->execute(['fotos_capa/' . $nome_arquivo, $perfil_id]);
    }

    header("Location: perfil.php?id=$perfil_id");
    exit;
}

$stmt = $pdo->prepare("SELECT nome, usuario, foto_perfil, foto_capa FROM usuarios WHERE id = ?");
$stmt->execute([$perfil_id]);
$perfil = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$perfil) {
    echo "Perfil não encontrado.";
    exit;
}

$nome = $perfil['nome'];
$usuario = $perfil['usuario'];
$foto_perfil = !empty($perfil['foto_perfil']) ? $perfil['foto_perfil'] : 'https://via.placeholder.com/100';
$foto_capa = !empty($perfil['foto_capa']) ? $perfil['foto_capa'] : 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d';

$stmt = $pdo->prepare("SELECT COUNT(*) FROM publicacoes WHERE id_usuario = ?");
$stmt->execute([$perfil_id]);
$total_pubs = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM publicacoes WHERE id_usuario = ? ORDER BY data_publicacao DESC");
$stmt->execute([$perfil_id]);
$publicacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Perfil do Usuário</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
      display: flex;
    }

    .sidebar {
      background-color: #000;
      color: #fff;
      width: 250px;
      height: 100vh;
      padding: 10px 0;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      align-items: center;
      position: fixed;
      left: 0;
      top: 0;
    }

    .user-profile {
      text-align: center;
    }

    .user-profile img {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      object-fit: cover;
    }

    .edit-photo {
      background-color: #006400;
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 12px;
      cursor: pointer;
      color: #fff;
      font-weight: bold;
      margin-top: 10px;
    }

    .sidebar button {
      width: 80%;
      padding: 12px;
      margin: 10px auto;
      background-color: #003300;
      border: none;
      color: #fff;
      cursor: pointer;
      border-radius: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      font-size: 15px;
    }

    .sidebar .logo {
      text-align: center;
      margin-bottom: 20px;
    }

    .sidebar .logo img {
      width: 100px;
      margin-bottom: 8px;
    }

    .main-content {
      margin-left: 270px;
      width: calc(100% - 270px);
    }

    .profile-header {
      position: relative;
      height: 300px;
      background-image: url('<?= htmlspecialchars($foto_capa) ?>');
      background-size: cover;
      background-position: center;
    }

    .profile-pic {
      position: absolute;
      bottom: -45px;
      left: 30px;
      width: 100px;
      height: 100px;
      border-radius: 50%;
      overflow: hidden;
      border: 4px solid white;
      background: white;
      z-index: 2;
    }

    .profile-pic img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .profile-info {
      background: #fff;
      padding: 60px 30px 20px 30px;
      border-radius: 0 0 8px 8px;
      text-align: left;
      position: relative;
    }

    .botoes-perfil {
      position: absolute;
      top: 20px;
      right: 30px;
      display: flex;
      gap: 10px;
    }

    .botoes-perfil a {
      background-color: #006400;
      color: white;
      padding: 8px 16px;
      border-radius: 20px;
      text-decoration: none;
      font-weight: bold;
    }

    .titulo-publicacoes {
      background-color: #006400;
      color: white;
      font-weight: bold;
      font-size: 18px;
      padding: 8px 720px;
      border-radius: 30px;
      display: inline-block;
      margin-left: 30px;
      margin-top: 30px;
      margin-bottom: 15px;
    }

    .postagens-wrapper {
      background-color: #fff;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.1);
      margin: 20px;
    }

    .user-post {
      background: #f9f9f9;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 15px;
    }

    .post-info {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 8px;
    }

    .user-avatar {
      width: 35px;
      height: 35px;
      border-radius: 50%;
      object-fit: cover;
    }

    .actions button {
      background-color: #006400;
      color: #fff;
      border: none;
      padding: 6px 12px;
      border-radius: 5px;
      font-size: 13px;
      margin-right: 8px;
      cursor: pointer;
    }
  </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <div>
    <div class="user-profile">
      <img src="<?= htmlspecialchars($foto_perfil) ?>" alt="Foto de perfil">
      <div class="edit-photo" onclick="document.getElementById('editarFotos').style.display='flex';">Alterar foto</div>
      <h3><?= htmlspecialchars($nome) ?></h3>
    </div>
    <button><i class="fas fa-home"></i> Início</button>
    <button><i class="fas fa-comments"></i> Mensagens</button>
    <button><i class="fas fa-user-friends"></i> Contatos</button>
    <button><i class="fas fa-user-plus"></i> Registrar Desaparecimento</button>
    <button><i class="fas fa-list"></i> Lista de Desaparecidos</button>
    <button><i class="fas fa-circle-user"></i> Comunidade</button>
    <button><i class="fas fa-database"></i> Dados e Ferramentas</button>
    <button><i class="fas fa-cogs"></i> Configurações</button>
    <button><i class="fas fa-sign-out-alt"></i> Sair</button>
  </div>
  <div class="logo">
    <img src="https://i.postimg.cc/HnfTFnXB/GGGGXXXXX.png" alt="Logo">
    <p><strong>SUMIDOS <span style="color: red;">PB</span></strong></p>
    <small>Versão Beta - LABIMEC</small>
  </div>
</div>

<!-- Conteúdo principal -->
<div class="main-content">
  <div class="profile-header">
    <div class="profile-pic">
      <img src="<?= htmlspecialchars($foto_perfil) ?>" alt="Foto do Usuário">
    </div>
  </div>

  <div class="profile-info">
    <h2><?= htmlspecialchars($nome) ?></h2>
    <p>@<?= htmlspecialchars($usuario) ?></p>
    <p>Localizações ajudadas: 0 | Publicações: <?= $total_pubs ?></p>

    <?php if ($eh_dono_do_perfil): ?>
      <div class="botoes-perfil">
        <a href="editar_perfil.php">Editar Perfil</a>
        <a href="configuracoes.php">Configurações</a>
      </div>
    <?php endif; ?>
  </div>

  <div class="titulo-publicacoes">Publicações</div>

  <div class="postagens-wrapper">
    <?php foreach ($publicacoes as $pub): ?>
      <div class="user-post">
        <div class="post-info">
          <img class="user-avatar" src="<?= htmlspecialchars($foto_perfil) ?>">
          <strong><?= htmlspecialchars($nome) ?></strong>
          <span style="font-size: 12px; color: #666;"><?= date('d/m/Y H:i', strtotime($pub['data_publicacao'])) ?></span>
        </div>
        <p><?= nl2br(htmlspecialchars($pub['mensagem'])) ?></p>
        <div class="actions">
          <button>Curtir</button>
          <button>Comentar</button>
          <button>Compartilhar</button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Modal Upload -->
<div id="editarFotos" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.6); justify-content:center; align-items:center;">
  <form method="post" enctype="multipart/form-data" style="background:white; padding:20px; border-radius:10px; min-width:300px;">
    <h3>Editar Fotos</h3>
    <p>Foto de Perfil: <input type="file" name="nova_foto_perfil"></p>
    <p>Foto de Capa: <input type="file" name="nova_foto_capa"></p>
    <button type="submit" name="salvar_fotos">Salvar</button>
    <button type="button" onclick="document.getElementById('editarFotos').style.display='none';">Cancelar</button>
  </form>
</div>

</body>
</html>
