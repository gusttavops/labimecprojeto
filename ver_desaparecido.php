<?php
require 'conexao.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo "<p>ID inválido.</p>";
    exit;
}

$sql = "SELECT p.*, u.nome AS nome_usuario 
        FROM pessoas_desaparecidas p
        LEFT JOIN usuarios u ON p.usuario_id = u.id
        WHERE p.id = :id";

$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $id]);
$pessoa = $stmt->fetch();

$nomeUsuario = $pessoa['nome_usuario'] ?? 'Desconhecido';

if (!$pessoa) {
    echo "<p>Registro não encontrado.</p>";
    exit;
}

$foto = !empty($pessoa['foto']) ? 'fotos/' . htmlspecialchars($pessoa['foto']) : 'https://via.placeholder.com/150x180?text=Sem+Foto';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>

<meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta charset="UTF-8">
  <title>Registro de Desaparecimento</title>
  <style>
    body { margin: 0; font-family: Arial, sans-serif; background-color: #f5f5f5; }

    /* Topo */
    header {
      background-color: black;
      color: white;
      text-align: center;
      padding: 20px 0;
    }

    header img { width: 150px; }
    header h1 { margin: 10px 0 0; font-size: 36px; }
    header h1 span { color: red; }

    .subtitle {
      background-color: #003300;
      color: white;
      display: inline-block;
      padding: 5px 15px;
      border-radius: 10px;
      font-size: 14px;
      margin-top: 10px;
    }

    nav {
      background-color: #119C2F;
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      padding: 10px 0;
    }

    nav a {
      color: white;
      text-decoration: none;
      margin: 0 15px;
      font-weight: bold;
    }

    /* Conteúdo */
    .container {
      max-width: 1000px;
      margin: 30px auto;
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    h2 {
      text-align: center;
      color: #119C2F;
      margin-bottom: 20px;
    }

    .foto {
      width: 180px;
      height: 220px;
      object-fit: cover;
      border-radius: 8px;
      float: right;
      margin-left: 20px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    td {
      padding: 10px;
      border-bottom: 1px solid #ddd;
    }

    td.label {
      font-weight: bold;
      width: 30%;
      color: #333;
    }

    .voltar {
      text-align: center;
      margin-top: 30px;
    }

    .voltar a {
      background-color: #119C2F;
      color: white;
      padding: 10px 20px;
      text-decoration: none;
      border-radius: 5px;
      font-weight: bold;
    }

    footer {
      background-color: #000;
      color: #ccc;
      text-align: center;
      padding: 10px;
      font-size: 13px;
      margin-top: 50px;
    }

@media (max-width: 768px) {
  .container {
    padding: 15px;
    box-sizing: border-box;
  }

  .foto {
    float: none;
    display: block;
    margin: 0 auto 20px;
    width: 100%;
    max-width: 220px;
    height: auto;
  }

  table {
    font-size: 14px;
  }

  td.label {
    width: 100%;
    display: block;
    font-weight: bold;
    background-color: #f0f0f0;
    margin-top: 10px;
    padding: 8px;
  }

  td:not(.label) {
    display: block;
    padding: 8px;
  }

  nav {
    flex-direction: column;
    align-items: center;
  }

  nav a {
    margin: 5px 0;
  }

  h2 {
    font-size: 22px;
  }
}

  </style>
</head>
<body>

<header>
  <img src="https://i.postimg.cc/brqVVSjZ/GGGGXXXXX.png" alt="Logo SUMIDOS PB" />
  <h1>SUMIDOS <span>PB</span></h1>
  <div class="subtitle">INTERMEDIANDO A LOCALIZAÇÃO</div>
</header>

<nav>
  <a href="inicio.php">Início</a>
  <a href="#">Quem Somos</a>
  <a href="lista_desaparecidos.php">Lista de Desaparecidos</a>
  <a href="registrar_desaparecido.php">Registrar Desaparecimento</a>
  <a href="#">Dados</a>
</nav>

<div class="container">
  <h2><?= htmlspecialchars($pessoa['nome']) ?></h2>
  <img src="<?= $foto ?>" class="foto" alt="Foto">

  <table>
    <tr><td class="label">Nome:</td><td><?= htmlspecialchars($pessoa['nome']) ?></td></tr>
    <tr><td class="label">Nome da Mãe:</td><td><?= htmlspecialchars($pessoa['nome_mae']) ?></td></tr>
    <tr><td class="label">Nome do Pai:</td><td><?= htmlspecialchars($pessoa['nome_pai']) ?></td></tr>
    <tr><td class="label">Sexo:</td><td><?= htmlspecialchars($pessoa['sexo']) ?></td></tr>
    <tr><td class="label">Idade:</td><td><?= htmlspecialchars($pessoa['idade']) ?> anos</td></tr>
    <tr><td class="label">Data de Nascimento:</td><td><?= !empty($pessoa['data_nascimento']) ? date('d/m/Y', strtotime($pessoa['data_nascimento'])) : '' ?></td></tr>
    <tr><td class="label">Cidade onde ocorreu:</td><td><?= htmlspecialchars($pessoa['cidade_ocorrido']) ?></td></tr>
    <tr><td class="label">Bairro do ocorrido:</td><td><?= htmlspecialchars($pessoa['bairro_ocorrido']) ?></td></tr>
    <tr><td class="label">Data do fato:</td><td><?= !empty($pessoa['data_fato_ocorrido']) ? date('d/m/Y', strtotime($pessoa['data_fato_ocorrido'])) : '' ?></td></tr>
    <tr><td class="label">Descrição:</td><td><?= nl2br(htmlspecialchars($pessoa['descricao_ocorrido'])) ?></td></tr>
    <tr><td class="label">Telefone de contato:</td><td><?= htmlspecialchars($pessoa['telefone']) ?></td></tr>
  </table>

  <!-- Publicado por -->
  <p style="margin-top: 20px; font-size: 14px;">
  <strong>Publicado por:</strong> <?= htmlspecialchars($nomeUsuario) ?>
</p>

  <!-- Caixa de mensagem estilo redes sociais -->
  <hr style="margin: 30px 0;">
  <p>Ajude a trazer esperança para uma família que está sofrendo. Compartilhe essa pessoa desaparecida e contribua com a busca. Cada compartilhamento pode ser a chave para encontrá-lo(a). Sua ajuda faz toda a diferença!</p>
  <div style="display: flex; gap: 10px; margin-bottom: 15px;">
    <a href="#" style="background: #3b5998; color: white; padding: 8px 12px; border-radius: 5px; text-decoration: none;">Facebook</a>
    <a href="#" style="background:rgb(0, 0, 0); color: white; padding: 8px 12px; border-radius: 5px; text-decoration: none;">X</a>
    <a href="#" style="background: #25d366; color: white; padding: 8px 12px; border-radius: 5px; text-decoration: none;">WhatsApp</a>
  </div>

  <form method="post" style="display: flex; flex-direction: column; gap: 10px;">
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
      <input type="text" name="nome" placeholder="Seu nome" style="flex: 1; padding: 10px;" required>
      <input type="text" name="telefone" placeholder="Seu telefone" style="flex: 1; padding: 10px;">
    </div>
    <textarea name="mensagem" rows="4" placeholder="Mensagem" style="padding: 10px;" required></textarea>
    <button type="submit" style="align-self: flex-start; background: #119C2F; color: #FFFFFF; padding: 10px 20px; border: none; border-radius: 5px; font-weight: bold;">ENVIAR</button>
  </form>

  <!-- Botão de voltar -->
  <div class="voltar">
    <a href="lista_desaparecidos.php">← Voltar para a Lista</a>
  </div>
</div>

<footer>
  <p>© 2025 SUMIDOS PB - Todos os direitos reservados</p>
  <p>Desenvolvido por Gustavo Pereira - LABIMEC</p>
</footer>

</body>
</html>
