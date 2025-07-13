<?php
session_start();

if (!isset($_SESSION['usuario']) || !isset($_SESSION['nome'])) {
    header("Location: login.php");
    exit;
}

$nome = $_SESSION['nome'];

// Incluir a conexão com o banco de dados
require_once __DIR__ . '/includes/db.php';

// Carregar foto do usuário e ID
$foto_perfil = 'https://lh3.googleusercontent.com/a/ACg8ocJxiK2NM9FwrnfbB3aJ7rN5hJ0l6qATGGQVIaqwjakwPzSnS8Yc=s288-c-no'; // Padrão
$user_id = null;

try {
    // Buscar o ID do usuário e a foto de perfil do banco de dados
    $stmt = $pdo->prepare("SELECT id, foto_perfil FROM usuarios WHERE usuario = ?");
    $stmt->execute([$_SESSION['usuario']]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_data) {
        $user_id = $user_data['id'];
        if (!empty($user_data['foto_perfil'])) {
            $foto_perfil = $user_data['foto_perfil'];
        }
    } else {
        // Usuário não encontrado, redirecionar para login
        header("Location: login.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar dados do usuário: " . $e->getMessage());
    // Em produção, talvez um erro mais amigável ou redirecionamento
    header("Location: login.php");
    exit;
}

// Se recebeu upload de nova foto (da parte de configurações)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['nova_foto_modal'])) { // Nova foto de modal
    if ($_FILES['nova_foto_modal']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['nova_foto_modal']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $novo_nome = 'perfil_' . $user_id . '_' . time() . '.' . $ext;
            $destino = 'uploads/' . $novo_nome; // Pasta para uploads de perfil
            if (move_uploaded_file($_FILES['nova_foto_modal']['tmp_name'], $destino)) {
                try {
                    $stmt = $pdo->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id = ?");
                    $stmt->execute([$destino, $user_id]);
                    $foto_perfil = $destino; // Atualiza a variável para exibir a nova foto
                } catch (PDOException $e) {
                    error_log("Erro ao atualizar foto de perfil: " . $e->getMessage());
                }
            }
        }
    }
}


// Obter a seção a ser exibida (padrão: alterar_nome)
$section = $_GET['section'] ?? 'alterar_nome';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Sumidos PB</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        /* CSS da página de Configurações */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Arial", sans-serif; display: flex; height: 100vh; color: #333; }

        /* Estilos da barra lateral - COPIADOS DO SEU inicio.php para consistência */
        .sidebar { background-color: #000; color: #fff; width: 251px; height: 100vh; padding: 10px 0; display: flex; flex-direction: column; justify-content: space-between; align-items: center; position: fixed; left: 0; top: 0; z-index: 10; }
        .user-profile { text-align: center; margin-bottom: 10px; display: flex; flex-direction: column; align-items: center; }
        .user-profile img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; }
        .edit-photo { background-color: #006400; padding: 5px 10px; border-radius: 20px; font-size: 12px; cursor: pointer; color: #fff; font-weight: bold; margin-top: 10px; }
        .user-profile h3 { margin-top: 10px; font-size: 16px; }
       .sidebar button {
  width: 80%;
  padding: 12px;
  margin: 10px auto;
  background-color: #0e330b;
  border: none;
  color: #fff;
  cursor: pointer;
  border-radius: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  font-size: 16px;
  transition: background-color 0.3s;
}

.sidebar button:hover {
  background-color: #00aa00;
}
         
        .sidebar .logo { text-align: center; margin-bottom: 20px; margin-top: 30px; }
        .sidebar .logo img { width: 100px; margin-bottom: 8px; }
        .sidebar .logo p { margin: 0; }
        .sidebar .logo small { display: inline-block; margin-top: 5px; background-color: #006400; padding: 2px 6px; border-radius: 5px; color: white; }

        /* Estilos do conteúdo principal da página de configurações */
        .main {
            margin-left: 251px; /* Offset para a sidebar */
            width: calc(100% - 251px);
            display: flex;
            flex-direction: column;
            height: 100vh; /* Para garantir que o main ocupe a altura total */
        }
        .header {
            background-color: #004d00; color: #fff; padding: 15px 25px; font-size: 20px; font-weight: 600;
            position: sticky; top: 0; z-index: 100;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); /* Adiciona uma pequena sombra */
        }
        .config-container {
            display: flex;
            flex: 1; /* Ocupa o restante do espaço vertical */
            height: calc(100vh - 60px); /* Subtrai a altura do header */
            overflow: hidden; /* Para que os sub-menus de config-content possam scrollar */
        }
        .config-menu {
            width: 220px; background: #002900; color: #fff; overflow-y: auto;
            display: flex; flex-direction: column; padding-top: 15px;
        }
        .config-menu details {
            border-bottom: 1px solid #000000;
        }
        .config-menu summary {
            padding: 12px 20px; background-color: #0a210a; cursor: pointer; list-style: none;
            font-size: 15px; user-select: none;
        }
        .config-menu summary::-webkit-details-marker { display: none; }
        .config-menu a {
            display: block; padding: 10px 35px; color: #fff; text-decoration: none; background-color: #17310d;
            font-size: 14px; transition: background 0.2s;
        }
        .config-menu a:hover { background-color: #006400; }
        .config-content {
            flex: 1; background-color: #f5f5f5; padding: 30px; overflow-y: auto;
        }
        .config-content h2 {
            font-size: 22px; margin-bottom: 20px; color: #003300;
        }
        .config-content input, .config-content button {
            width: 100%; padding: 12px; margin-bottom: 15px; border-radius: 6px; font-size: 14px;
        }
        .config-content input { border: 1px solid #ccc; }
        .config-content button {
            background-color: #006400; color: #fff; border: none; cursor: pointer; font-weight: bold;
            transition: background 0.2s;
        }
        .config-content button:hover { background-color: #004d00; }

        /* Modal para alterar foto - COPIADOS DO SEU inicio.php para consistência */
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
        /* Classes para mensagens de feedback */
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-weight: bold;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div>
      <div class="user-profile">
        <img src="<?php echo htmlspecialchars($foto_perfil); ?>" alt="Foto de Perfil" id="fotoPerfil">
        <div class="edit-photo" id="btnAlterarFoto">Alterar foto</div>
        <h3><?php echo htmlspecialchars($nome); ?></h3>
      </div>
      <button onclick="window.location.href='Inicio.php'">
  <i class="fas fa-home"></i> Início </button>
            <button onclick="window.location.href='mensagens.php'">
  <i class="fas fa-envelope"></i> Mensagens </button>
        
       <button onclick="window.location.href='contatos.php'">
  <i class="fas fa-user-friends"></i> Contatos</button>
      
      <button onclick="window.location.href='lista_desaparecidos.php'">
            <i class="fas fa-list"></i> Lista de Desaparecidos </button>
        <button onclick="window.location.href='registrar_desaparecido.php'">
            <i class="fas fa-user-plus"></i> Registrar Desaparecimento </button>
        <button><i class="fa-solid fa-circle-user"></i> Comunidade</button>
       
      <button onclick="window.location.href='dadosferramentas.php'">
  <i class="fas fa-home"></i> Dados e Ferramentas</button>

        
         <button onclick="window.location.href='configuracoes.php'">
            <i class="fas fa-cogs"></i> Configurações</button>
      
      <form method="post" action="logout.php">
            <button type="submit"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </div>

<div class="main">
    <div class="header">Configurações da Conta</div>

    <div class="config-container">
        <div class="config-menu">
            <details open>
                <summary>CONTA</summary>
                <a href="?section=alterar_nome">ALTERAR NOME</a>
                <a href="?section=alterar_email">ALTERAR E-MAIL</a>
                <a href="?section=alterar_senha">ALTERAR SENHA</a>
                <a href="?section=alterar_data_nascimento">ALTERAR DATA DE NASCIMENTO</a>
            </details>

            <details>
                <summary>PRIVACIDADE</summary>
                <a href="?section=configuracoes_privacidade">CONFIGURAÇÕES DE PRIVACIDADE</a>
            </details>

            <details>
                <summary>SUMIDOS PB</summary>
                <a href="?section=projeto">PROJETO</a>
            </details>
            
            <details>
                <summary>LABIMEC</summary>
                <a href="?section=sobre_labimec">SOBRE O LABIMEC</a>
            </details>
            
            <details>
                <summary>TERMOS DE USO</summary>
                <a href="?section=termos_uso">TERMOS DE USO</a>
                <a href="?section=dados">DADOS</a>
            </details>
        </div>

        <div class="config-content">
            <?php
            // Inclui o arquivo da seção correspondente
            $section_path = __DIR__ . '/sections/' . $section . '.php';

            // Certifica-se de passar as variáveis necessárias para os arquivos de seção
            if (file_exists($section_path)) {
                require_once $section_path;
            } else {
                echo "<h2>Seção não encontrada.</h2><p>Por favor, selecione uma opção no menu.</p>";
            }
            ?>
        </div>
    </div>
</div>

<div class="modal-foto-bg" id="modalAlterarFoto">
    <div class="modal-foto">
        <span class="close-modal" id="closeModal">×</span>
        <form method="post" enctype="multipart/form-data" action="configuracoes.php?section=<?php echo htmlspecialchars($section); ?>">
            <label for="inputFoto">Escolher nova foto:</label>
            <input type="file" name="nova_foto_modal" id="inputFoto" accept="image/*" required>
            <button type="submit">Salvar Foto</button>
        </form>
    </div>
</div>

<script>
    // JavaScript para abrir/fechar o modal de foto de perfil
    document.getElementById('btnAlterarFoto').addEventListener('click', function() {
        document.getElementById('modalAlterarFoto').style.display = 'flex';
    });
    document.getElementById('closeModal').addEventListener('click', function() {
        document.getElementById('modalAlterarFoto').style.display = 'none';
    });
    // Opcional: fechar modal clicando fora
    window.addEventListener('click', function(event) {
        if (event.target == document.getElementById('modalAlterarFoto')) {
            document.getElementById('modalAlterarFoto').style.display = 'none';
        }
    });

    // Manter a 'details' aberta quando um link dentro dela é clicado
    document.querySelectorAll('.config-menu details a').forEach(link => {
        link.addEventListener('click', function() {
            const parentDetails = this.closest('details');
            if (parentDetails) {
                // Fechar todas as outras 'details' e abrir a clicada
                document.querySelectorAll('.config-menu details').forEach(d => d.removeAttribute('open'));
                parentDetails.setAttribute('open', true);
            }
        });
    });

    // Abrir a 'details' da seção ativa ao carregar a página
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const activeSection = urlParams.get('section');
        if (activeSection) {
            document.querySelectorAll('.config-menu details a').forEach(link => {
                if (link.href.includes(`section=${activeSection}`)) {
                    const parentDetails = link.closest('details');
                    if (parentDetails) {
                        parentDetails.setAttribute('open', true);
                    }
                }
            });
        }
    });
</script>

</body>
</html>