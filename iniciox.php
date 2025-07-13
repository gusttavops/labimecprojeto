<?php
session_start();

if (!isset($_SESSION['usuario']) || !isset($_SESSION['nome'])) {
    header("Location: login.php");
    exit;
}

$nome = $_SESSION['nome'];

// Inicialize o PDO sempre
$pdo = new PDO("mysql:host=localhost;dbname=sistema_login;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Carregar foto do usuário, padrão se não existir
$foto_perfil = isset($_SESSION['foto_perfil']) ? $_SESSION['foto_perfil'] : 'https://lh3.googleusercontent.com/a/ACg8ocJxiK2NM9FwrnfbB3aJ7rN5hJ0l6qATGGQVIaqwjakwPzSnS8Yc=s288-c-no';

// Se recebeu upload de nova foto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['nova_foto'])) {
    if ($_FILES['nova_foto']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['nova_foto']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            if (!is_dir('fotos_perfil')) {
                mkdir('fotos_perfil', 0777, true);
            }
            $novo_nome = 'perfil_' . $_SESSION['usuario'] . '_' . time() . '.' . $ext;
            $destino = 'fotos_perfil/' . $novo_nome;
            if (move_uploaded_file($_FILES['nova_foto']['tmp_name'], $destino)) {
                $_SESSION['foto_perfil'] = $destino;
                $foto_perfil = $destino;
                $stmt = $pdo->prepare("UPDATE usuarios SET foto_perfil = ? WHERE usuario = ?");
                $stmt->execute([$destino, $_SESSION['usuario']]);
            }
        }
    }
} else {
    // Carrega do banco se existir
    $stmt = $pdo->prepare("SELECT foto_perfil FROM usuarios WHERE usuario = ?");
    $stmt->execute([$_SESSION['usuario']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && !empty($row['foto_perfil'])) {
        $foto_perfil = $row['foto_perfil'];
        $_SESSION['foto_perfil'] = $foto_perfil;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sumidos PB - Publicações</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <style>
    body { margin: 0; font-family: Arial, sans-serif; display: flex; }
    .language-selector { position: fixed; top: 5px; left: 6px; z-index: 100; background-color: #ff0000; color: #fff; border-radius: 10px; padding: 3px 8px; font-size: 11px; display: flex; align-items: center; gap: 4px; cursor: pointer; }
    .language-dropdown { position: absolute; top: 100%; left: 0; background-color: #ff0000; border-radius: 5px; margin-top: 5px; padding: 5px 0; display: none; }
    .language-dropdown a { display: block; padding: 5px 15px; color: #fff; text-decoration: none; font-size: 11px; }
    .language-selector:hover .language-dropdown { display: block; }

    .sidebar { background-color: #000; color: #fff; width: 251px; height: 100vh; padding: 10px 0; display: flex; flex-direction: column; justify-content: space-between; align-items: center; position: fixed; left: 0; top: 0; z-index: 10; }
    .user-profile { text-align: center; margin-bottom: 10px; display: flex; flex-direction: column; align-items: center; }
    .user-profile img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; }
    .edit-photo { background-color: #006400; padding: 5px 10px; border-radius: 20px; font-size: 12px; cursor: pointer; color: #fff; font-weight: bold; margin-top: 10px; }
    .user-profile h3 { margin-top: 10px; font-size: 16px; }
    .sidebar button { width: 80%; padding: 12px; margin: 10px auto; background-color: #003300; border: none; color: #fff; cursor: pointer; border-radius: 30px; display: flex; align-items: center; justify-content: center; gap: 10px; font-size: 16px; }
    .sidebar .logo { text-align: center; margin-bottom: 20px; margin-top: 30px; }
    .sidebar .logo img { width: 100px; margin-bottom: 8px; }
    .sidebar .logo p { margin: 0; }
    .sidebar .logo small { display: inline-block; margin-top: 5px; background-color: #006400; padding: 2px 6px; border-radius: 5px; color: white; }

    .main-content { flex: 1; background-color: #f4f4f4; padding: 20px; padding-left: 270px; overflow-y: auto; height: 100vh; box-sizing: border-box; }
    .carousel-container { position: relative; overflow: hidden; width: 100%; max-width: 100%; margin-bottom: 20px; border-radius: 8px; }
    .carousel-slide { display: flex; transition: transform 0.5s ease-in-out; }
    .carousel-item { min-width: 100%; box-sizing: border-box; }
    .carousel-item img { width: 100%; display: block; border-radius: 8px; }
    .carousel-buttons { position: absolute; top: 50%; left: 0; right: 0; display: flex; justify-content: space-between; transform: translateY(-50%); pointer-events: none; }
    .carousel-buttons button { background-color: rgba(0,0,0,0.5); color: #fff; border: none; padding: 10px; cursor: pointer; border-radius: 50%; font-size: 20px; pointer-events: auto; }

    .post-box, .user-post { background: #fff; padding: 15px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .post-box textarea { width: 100%; height: 80px; padding: 10px; resize: none; margin-bottom: 10px; font-size: 14px; }
    .actions { display: flex; gap: 8px; margin-top: 10px; }
    .actions button { background: #006400; color: #fff; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-weight: bold; }
    .comments-section { margin-top: 10px; }
    .comment { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 6px; }
    .user-icon { width: 30px; height: 30px; background-color: #ccc; border-radius: 50%; }
    .comment-text { background: #f0f0f0; padding: 6px 10px; border-radius: 6px; max-width: 500px; }
    .post-info { display: flex; align-items: center; gap: 15px; margin-bottom: 10px; }
    .user-avatar { width: 40px; height: 40px; border-radius: 50%; background-color: #ccc; object-fit: cover; }
    .post-content img.post-image { max-width: 100%; border-radius: 8px; margin-top: 10px; }

    .post-form {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    input[type="file"]::file-selector-button {
      background-color: #006400;
      color: #fff;
      border: none;
      padding: 8px 15px;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
    }

    input[type="file"] {
      color: #333;
      font-size: 14px;
    }

    .post-box button[type="submit"] {
      background-color: #006400;
      color: #fff;
      border: none;
      padding: 8px 15px;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
    }

    .modal-foto-bg {
      display: none;
      position: fixed;
      z-index: 99999;
      left: 0; top: 0; width: 100vw; height: 100vh;
      background: rgba(0,0,0,0.5);
      align-items: center;
      justify-content: center;
    }
    .modal-foto {
      background: #fff;
      padding: 25px 30px 18px 30px;
      border-radius: 15px;
      text-align: center;
      position: relative;
      min-width: 270px;
    }
    .modal-foto .close-modal {
      position: absolute;
      right: 18px; top: 12px;
      font-size: 22px;
      color: #333;
      cursor: pointer;
    }
    .modal-foto label {
      font-weight: bold;
      display: block;
      margin-bottom: 12px;
    }
    .modal-foto input[type="file"] {
      margin-bottom: 16px;
    }
    .modal-foto button[type="submit"] {
      background-color: #006400;
      color: #fff;
      border: none;
      padding: 8px 20px;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
      font-size: 15px;
    }

    /* Notificações */
    .notification-bell {
      position: fixed;
      top: 20px;
      left: 270px;
      color: #fff;
      cursor: pointer;
      font-size: 24px;
      z-index: 3000;
    }
    .notification-bell .notification-count {
      background-color: red;
      color: #fff;
      border-radius: 50%;
      padding: 2px 6px;
      font-size: 11px;
      position: absolute;
      top: -8px;
      right: -10px;
    }
    .modal-bg {
      display: none;
      position: fixed;
      top: 0; left: 0; width: 100%; height: 100%;
      background-color: rgba(0,0,0,0.6);
      justify-content: center;
      align-items: center;
      z-index: 5000;
    }
    .modal {
      background-color: #fff;
      padding: 20px 25px;
      border-radius: 10px;
      max-width: 400px;
      width: 90%;
      text-align: left;
      position: relative;
    }
    .modal h2 {
      margin-bottom: 15px;
      color: #003300;
    }
    .notification-list {
      list-style: none;
      padding: 0;
    }
    .notification-list li {
      margin-bottom: 8px;
      border-bottom: 1px solid #ddd;
      padding-bottom: 5px;
    }
    .notification-list li:last-child {
      border-bottom: none;
    }
    .close-modal {
      position: absolute;
      top: 10px; right: 15px;
      font-size: 22px;
      color: #555;
      cursor: pointer;
    }

    #toTop {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 9999;
      background-color: #333;
      color: #fff;
      padding: 10px 15px;
      border-radius: 5px;
      display: none;
      cursor: pointer;
      font-size: 20px;
    }

    /* Layout flex da área de publicações e detalhes */
    .flex-container-main {
      display: flex;
      gap: 20px;
    }
    .flex-container-main .publicacoes-lista {
      width: 70%;
    }
    .flex-container-main #post-detalhada {
      width: 30%;
    }
  </style>
</head>
<body>

  <div id="toTop">
    <i class="glyphicon glyphicon-arrow-up"></i>
  </div>

  <div class="sidebar">
    <div>
      <div class="user-profile">
        <img src="<?php echo htmlspecialchars($foto_perfil); ?>" alt="Foto de Perfil" id="fotoPerfil">
        <div class="edit-photo" id="btnAlterarFoto">Alterar foto</div>
        <h3><?php echo htmlspecialchars($nome); ?></h3>
      </div>
      <button><i class="fas fa-home"></i> Início</button>
      <a href="chat/chat.php" class="btn-mensagens">
        <button>
          <i class="fas fa-comments"></i> Mensagens
        </button>
      </a>
      <button><i class="fas fa-user-friends"></i> Contatos</button>
      <button><i class="fas fa-user-plus"></i> Registrar Desaparecimento</button>
      <button><i class="fas fa-list"></i> Lista de Desaparecidos</button>
      <button><i class="fa-solid fa-circle-user"></i> Comunidade</button>
      <button><i class="fas fa-database"></i>Dados e Ferramentas</button>
      <button><i class="fas fa-cogs"></i> Configurações</button>
      <form method="post" action="logout.php">
        <button type="submit"><i class="fas fa-sign-out-alt"></i> Sair</button>
      </form>
    </div>
    <div class="logo">
      <img src="https://i.postimg.cc/HnfTFnXB/GGGGXXXXX.png" alt="Logo Sumidos PB">
      <p><strong>SUMIDOS <span style="color: red;">PB</span></strong></p>
      <small>Versão Beta - LABIMEC</small>
    </div>
  </div>

  <!-- Sino de Notificação e Modal de Notificações (apenas UM componente visual) -->
  <div class="notification-bell" onclick="abrirNotificacoes()">
    <i class="fas fa-bell"></i>
    <span class="notification-count" id="notificationCount">3</span>
  </div>

  <div class="modal-bg" id="modalNotificacoes">
    <div class="modal">
      <span class="close-modal" onclick="fecharNotificacoes()">&times;</span>
      <h2>Notificações</h2>
      <ul class="notification-list">
        <li><strong>Maria</strong> enviou um pedido de contato.</li>
        <li>Nova publicação em "Desaparecidos".</li>
        <li>Atualização disponível na plataforma.</li>
      </ul>
    </div>
  </div>

  <!-- Modal de alteração de foto -->
  <div class="modal-foto-bg" id="modalFotoBg">
    <div class="modal-foto">
      <span class="close-modal" id="closeModalFoto">&times;</span>
      <form method="post" enctype="multipart/form-data">
        <label for="nova_foto">Selecione uma nova foto:</label>
        <input type="file" name="nova_foto" id="nova_foto" accept="image/*" required>
        <br>
        <button type="submit">Enviar</button>
        <button type="reset" style="background-color: #006400; color: #fff; border: none; padding: 8px 15px; border-radius: 5px; font-weight: bold;">Limpar</button>
        <button type="button" onclick="window.location.href='inicio.php';" style="background-color: #006400; color: #fff; border: none; padding: 8px 15px; border-radius: 5px; font-weight: bold;">Cancelar</button>
      </form>
    </div>
  </div>

  <div class="main-content">
    <div class="carousel-container" id="carousel">
      <div class="carousel-slide" id="carousel-slide">
        <div class="carousel-item"><img src="uploads/slide1.jpg" alt="Slide 1"></div>
        <div class="carousel-item"><img src="uploads/slide2.jpg" alt="Slide 2"></div>
        <div class="carousel-item"><img src="uploads/slide3.jpg" alt="Slide 3"></div>
      </div>
      <div class="carousel-buttons">
        <button onclick="prevSlide()">‹</button>
        <button onclick="nextSlide()">›</button>
      </div>
    </div>

    <div class="post-box">
      <h3>Postar Publicação</h3>
      <form method="POST" action="publicar.php" enctype="multipart/form-data" class="post-form">
        <textarea name="mensagem" placeholder="Escreva algo..." style="flex:1 1 100%;"></textarea>
        <div style="display: flex; gap: 10px; align-items: center; width: 100%; justify-content: space-between;">
          <input type="file" name="imagem">
          <button type="submit">Publicar</button>
        </div>
      </form>
    </div>

    <!-- Flex container para lista de publicações e detalhes -->
    <div class="flex-container-main">
      <div class="publicacoes-lista">
        <?php
        // Buscar publicações + foto_perfil
        $sql = "SELECT publicacoes.*, usuarios.usuario, usuarios.foto_perfil 
                FROM publicacoes
                JOIN usuarios ON publicacoes.id_usuario = usuarios.id
                ORDER BY publicacoes.data_publicacao DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $publicacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <?php foreach ($publicacoes as $pub): ?>
          <div class="user-post">
            <div class="post">
              <a href="javascript:void(0);" class="ver-detalhes-btn" data-id="<?php echo $pub['id']; ?>" style="text-decoration: none; color: inherit;">
                <div class="post-info">
                  <img class="user-avatar" src="<?php echo !empty($pub["foto_perfil"]) ? htmlspecialchars($pub["foto_perfil"]) : 'https://exemplo.com/default.png'; ?>" alt="Foto de perfil">
                  <div class="user-details">
                    <strong><?php echo htmlspecialchars($pub["usuario"]); ?></strong>
                    <p style="font-size: 12px; color: #666;"><?php echo date('d/m/Y H:i', strtotime($pub["data_publicacao"])); ?></p>
                  </div>
                </div>
                <div class="post-content">
                  <p><?php echo nl2br(htmlspecialchars($pub["mensagem"])); ?></p>
                  <?php if (!empty($pub["imagem"])): ?>
                    <img src="uploads/<?php echo htmlspecialchars($pub["imagem"]); ?>" class="post-image">
                  <?php endif; ?>
                </div>
              </a>
              <div class="actions">
                <button><i class="fas fa-heart"></i> Curtir</button>
                <button class="btn-comentar" data-id="<?php echo $pub['id']; ?>">
                  <i class="fas fa-comment"></i> Comentar
                </button>
                <button><i class="fa-solid fa-share"></i> Compartilhar</button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <!-- Área de detalhe ao lado -->
      <div id="post-detalhada">
        <!-- Conteúdo da publicação detalhada será carregado aqui -->
      </div>
    </div>

    <!-- Área onde os detalhes da publicação serão exibidos em cima (caso queira usar em vez do lado) -->
    <div id="detalhePublicacao" style="display:none; background-color:#fff; padding:20px; border-radius:10px; margin-bottom:20px;">
      <!-- Conteúdo dinâmico será carregado aqui -->
    </div>
  </div>

  <script>
    // Modal alterar foto
    document.getElementById('btnAlterarFoto').onclick = function() {
      document.getElementById('modalFotoBg').style.display = 'flex';
    };
    document.getElementById('closeModalFoto').onclick = function() {
      document.getElementById('modalFotoBg').style.display = 'none';
    };
    window.onclick = function(event) {
      if (event.target === document.getElementById('modalFotoBg')) {
        document.getElementById('modalFotoBg').style.display = 'none';
      }
    };

    // Carrossel
    const slide = document.getElementById('carousel-slide');
    const totalSlides = document.querySelectorAll('.carousel-item').length;
    let index = 0;

    function updateSlide() {
      slide.style.transform = `translateX(-${index * 100}%)`;
    }

    function nextSlide() {
      index = (index + 1) % totalSlides;
      updateSlide();
    }

    function prevSlide() {
      index = (index - 1 + totalSlides) % totalSlides;
      updateSlide();
    }

    setInterval(nextSlide, 30000);

    const mainContent = document.querySelector('.main-content');
    const toTop = document.getElementById('toTop');

    mainContent.addEventListener('scroll', function () {
      if (mainContent.scrollTop > 100) {
        toTop.style.display = "block";
      } else {
        toTop.style.display = "none";
      }
    });

    toTop.onclick = function () {
      mainContent.scrollTo({ top: 0, behavior: 'smooth' });
    };

    // Modal de Notificações
    function abrirNotificacoes() {
      document.getElementById("modalNotificacoes").style.display = "flex";
    }
    function fecharNotificacoes() {
      document.getElementById("modalNotificacoes").style.display = "none";
    }
    window.addEventListener('click', function(event) {
      const modal = document.getElementById("modalNotificacoes");
      if (event.target === modal) {
        modal.style.display = "none";
      }
    });

    // Delegação para detalhes da publicação (mostra em cima ou ao lado)
    document.addEventListener('click', function (event) {
      const btn = event.target.closest('.ver-detalhes-btn');
      if (btn) {
        const postId = btn.getAttribute('data-id');
        fetch(`carregar_publicacao.php?id=${postId}`)
          .then(response => response.text())
          .then(html => {
            // Para mostrar em cima:
            // const detalhe = document.getElementById('detalhePublicacao');
            // detalhe.innerHTML = html;
            // detalhe.style.display = 'block';
            // window.scrollTo({ top: detalhe.offsetTop, behavior: 'smooth' });

            // Para mostrar ao lado:
            const detalheLado = document.getElementById('post-detalhada');
            detalheLado.innerHTML = html;
          })
          .catch(error => {
            console.error('Erro ao carregar publicação:', error);
          });
      }
    });

    // Comentar (detalhe em coluna lateral)
    document.addEventListener('DOMContentLoaded', function () {
      const botoesComentar = document.querySelectorAll('.btn-comentar');
      botoesComentar.forEach(btn => {
        btn.addEventListener('click', function () {
          const postId = this.getAttribute('data-id');
          fetch('post_detalhe.php?id=' + postId)
            .then(response => response.text())
            .then(html => {
              document.getElementById('post-detalhada').innerHTML = html;
            })
            .catch(error => {
              console.error('Erro ao carregar post:', error);
            });
        });
      });
    });
  </script>
</body>
</html>