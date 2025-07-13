<?php
session_start();

if (!isset($_SESSION['usuario']) || !isset($_SESSION['nome'])) {
    header("Location: login.php");
    exit;
}

$nome = $_SESSION['nome'];

// Carregar foto do usuário, padrão se não existir
$foto_perfil = isset($_SESSION['foto_perfil']) ? $_SESSION['foto_perfil'] : 'https://lh3.googleusercontent.com/a/ACg8ocJxiK2NM9FwrnfbB3aJ7rN5hJ0l6qATGGQVIaqwjakwPzSnS8Yc=s288-c-no';

// Se recebeu upload de nova foto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['nova_foto'])) {
    if ($_FILES['nova_foto']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['nova_foto']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $novo_nome = 'perfil_' . $_SESSION['usuario'] . '_' . time() . '.' . $ext;
            $destino = 'fotos_perfil/' . $novo_nome;
            if (move_uploaded_file($_FILES['nova_foto']['tmp_name'], $destino)) {
                $_SESSION['foto_perfil'] = $destino;
                $foto_perfil = $destino;

                // Atualizar no banco de dados
                $pdo = new PDO("mysql:host=localhost;dbname=sistema_login;charset=utf8mb4", "root", "");
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $stmt = $pdo->prepare("UPDATE usuarios SET foto_perfil = ? WHERE usuario = ?");
                $stmt->execute([$destino, $_SESSION['usuario']]);
            }
        }
    }
} else {
    // Carrega do banco se existir
    $pdo = new PDO("mysql:host=localhost;dbname=sistema_login;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
    .sidebar { background-color: #000; color: #fff; width: 251px; height: 100vh; padding: 10px 0; display: flex; flex-direction: column; justify-content: space-between; align-items: center; position: fixed; left: 0; top: 0; z-index: 10; }
    .main-content { flex: 1; background-color: #f4f4f4; padding: 20px; padding-left: 270px; overflow-y: auto; height: 100vh; box-sizing: border-box; }
  </style>
</head>
<body>

  <div class="sidebar">
    <div class="user-profile">
      <img src="<?php echo htmlspecialchars($foto_perfil); ?>" alt="Foto de Perfil" id="fotoPerfil">
      <div class="edit-photo" id="btnAlterarFoto">Alterar foto</div>
      <h3><?php echo htmlspecialchars($nome); ?></h3>
    </div>
  </div>

  <!-- Sino de Notificações -->
  <div id="notificationIcon" style="position: fixed; top: 15px; right: 20px; z-index: 9999; cursor: pointer; color: white; font-size: 22px;">
    <i class="fas fa-bell"></i>
    <span id="notificationCount" style="background-color: red; color: white; font-size: 12px; padding: 2px 6px; border-radius: 50%; position: absolute; top: -8px; right: -8px;">3</span>
  </div>

  <div id="notificationDropdown" style="display:none; position: fixed; top: 45px; right: 20px; background: #fff; color: #000; border-radius: 5px; box-shadow: 0 2px 8px rgba(0,0,0,0.2); width: 250px; z-index: 9999;">
    <div style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Notificações</div>
    <div style="padding: 10px; border-bottom: 1px solid #eee;">Nova publicação disponível</div>
    <div style="padding: 10px; border-bottom: 1px solid #eee;">Novo comentário em sua postagem</div>
    <div style="padding: 10px;">Atualização disponível</div>
  </div>

  <div class="main-content">
    <h1>Conteúdo Principal Aqui</h1>
  </div>

  <script>
    const notificationIcon = document.getElementById('notificationIcon');
    const notificationDropdown = document.getElementById('notificationDropdown');

    notificationIcon.onclick = function () {
      if (notificationDropdown.style.display === 'none') {
        notificationDropdown.style.display = 'block';
      } else {
        notificationDropdown.style.display = 'none';
      }
    };

    window.onclick = function(event) {
      if (!notificationIcon.contains(event.target) && !notificationDropdown.contains(event.target)) {
        notificationDropdown.style.display = 'none';
      }
    };
  </script>

</body>
</html>
