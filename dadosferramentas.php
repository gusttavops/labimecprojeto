<?php
session_start();

// Redireciona se o usuário não estiver logado
if (!isset($_SESSION['usuario']) || !isset($_SESSION['nome'])) {
    header("Location: login.php");
    exit;
}

$nome = htmlspecialchars($_SESSION['nome']);

// Inclui a conexão com o banco de dados
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
            $foto_perfil = htmlspecialchars($user_data['foto_perfil']);
        }
    } else {
        // Usuário não encontrado, redirecionar para login
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Sua sessão expirou ou usuário não encontrado. Por favor, faça login novamente.'];
        header("Location: login.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar dados do usuário: " . $e->getMessage());
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Ocorreu um erro ao carregar seus dados. Por favor, tente novamente.'];
    header("Location: login.php");
    exit;
}

// Lógica de upload de foto (mantida aqui para consistência com o modal da sidebar)
// Pode ser movida para um arquivo separado para um sistema maior, se desejar.
$message = null; // Reinicia a variável de mensagem para esta página
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['nova_foto_modal'])) {
    if ($user_id === null) {
        $message = ['type' => 'error', 'text' => 'Erro: ID de usuário não disponível para upload.'];
    } elseif ($_FILES['nova_foto_modal']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['nova_foto_modal']['name'];
        $file_tmp_name = $_FILES['nova_foto_modal']['tmp_name'];
        $file_size = $_FILES['nova_foto_modal']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $max_file_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file_ext, $allowed_extensions)) {
            $message = ['type' => 'error', 'text' => 'Tipo de arquivo não permitido. Apenas JPG, JPEG, PNG e GIF são aceitos.'];
        } elseif ($file_size > $max_file_size) {
            $message = ['type' => 'error', 'text' => 'O arquivo é muito grande. O tamanho máximo permitido é 5MB.'];
        } else {
            $novo_nome = 'perfil_' . $user_id . '_' . time() . '.' . $file_ext;
            $destino = 'uploads/' . $novo_nome;

            if (move_uploaded_file($_FILES['nova_foto_modal']['tmp_name'], $destino)) {
                try {
                    $stmt = $pdo->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id = ?");
                    $stmt->execute([$destino, $user_id]);
                    $foto_perfil = htmlspecialchars($destino);
                    $message = ['type' => 'success', 'text' => 'Foto de perfil atualizada com sucesso!'];
                } catch (PDOException $e) {
                    error_log("Erro ao atualizar foto de perfil no DB: " . $e->getMessage());
                    $message = ['type' => 'error', 'text' => 'Erro ao salvar a foto no banco de dados.'];
                }
            } else {
                $message = ['type' => 'error', 'text' => 'Erro ao fazer upload da foto.'];
            }
        }
    } else {
        $message = ['type' => 'error', 'text' => 'Ocorreu um erro durante o upload: Código ' . $_FILES['nova_foto_modal']['error']];
    }
    // Não redirecionar aqui, pois a mensagem será exibida na página atual.
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dados e Ferramentas - Sumidos PB</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        /* CSS Base - Copiado de suas outras páginas para consistência */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Arial", sans-serif; display: flex; height: 100vh; color: #333; }

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

        .main {
            margin-left: 251px; /* Offset para a sidebar */
            width: calc(100% - 251px);
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        .header {
            background-color: #004d00; color: #fff; padding: 15px 25px; font-size: 20px; font-weight: 600;
            position: sticky; top: 0; z-index: 100;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .content-area { /* Nova div para o conteúdo principal específico desta página */
            flex: 1; /* Ocupa o restante do espaço vertical */
            background-color: #f5f5f5;
            padding: 30px;
            overflow-y: auto; /* Permite scroll se o conteúdo for muito grande */
        }
        .content-area h2 {
            font-size: 22px; margin-bottom: 20px; color: #003300;
        }

        /* Estilos para a lista de Ferramentas e Dados (copiados e adaptados do seu CSS anterior) */
        .tools-data-list {
            list-style: none;
            padding: 0;
        }
        .tools-data-list li {
            background-color: #fff;
            margin-bottom: 15px;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .tools-data-list li i {
            font-size: 28px;
            color: #006400;
            flex-shrink: 0;
        }
        .tools-data-list li > div {
            flex-grow: 1;
        }
        .tools-data-list li h3 {
            margin: 0;
            font-size: 18px;
            color: #003300;
        }
        .tools-data-list li p {
            margin: 5px 0 0;
            color: #555;
            font-size: 14px;
        }
        .tools-data-list li .status {
            margin-left: auto;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            color: #fff;
            flex-shrink: 0;
        }
        .tools-data-list li .status.available { background-color: #28a745; } /* Green */
        .tools-data-list li .status.soon { background-color: #ffc107; }     /* Yellow */
        .tools-data-list li .status.beta { background-color: #007bff; }     /* Blue */
        .tools-data-list li .status.restricted { background-color: #dc3545; } /* Red */

        /* Modal para alterar foto (mantido para a sidebar) */
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
            margin: 15px 25px 0 276px; /* Ajusta a margem para ficar ao lado da sidebar e abaixo do cabeçalho */
            border-radius: 4px;
            font-weight: bold;
            z-index: 10;
            position: relative;
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
        <button onclick="window.location.href='inicio.php'">
            <i class="fas fa-home"></i> Início
        </button>
        <button onclick="window.location.href='chat.php'">
            <i class="fas fa-comments"></i> Mensagens
        </button>
<button onclick="window.location.href='contatos.php'">
  <i class="fas fa-user-friends"></i> Contatos</button>
        
        <button onclick="window.location.href='lista_desaparecidos.php'">
            <i class="fas fa-list"></i> Lista de Desaparecidos
        </button>
        <button onclick="window.location.href='registrar_desaparecido.php'">
            <i class="fas fa-user-plus"></i> Registrar Desaparecimento
        </button>
        <button><i class="fa-solid fa-circle-user"></i> Comunidade</button>
        <button onclick="window.location.href='dadosferramentas.php'">
            <i class="fas fa-envelope"></i> Dados e Ferramentas</button>
        <button onclick="window.location.href='configuracoes.php'"> <i class="fas fa-cogs"></i> Configurações
        </button>
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

<div class="main">
    <div class="header">Dados e Ferramentas</div> <?php if ($message): ?>
        <div class="message <?php echo $message['type']; ?>">
            <?php echo htmlspecialchars($message['text']); ?>
        </div>
    <?php endif; ?>

    <div class="content-area"> <h2>Ferramentas e Dados Disponíveis</h2>
        <p>Explore as ferramentas e os conjuntos de dados que o Sumidos PB oferece para auxiliar na busca por pessoas desaparecidas e na análise de informações.</p>
&nbsp; &nbsp; &nbsp; <p></p>
       
        <ul class="tools-data-list">
            <li>
                <i class="fas fa-map-marked-alt"></i>
                <div>
                    <h3>Mapa Interativo de Desaparecimentos</h3>
                    <p>Visualize as ocorrências de desaparecimentos em um mapa interativo, com filtros por região e data.</p>
                </div>
                <span class="status soon">Em Breve</span>
            </li>
            <li>
                <i class="fas fa-chart-line"></i>
                <div>
                    <h3>Análise Estatística</h3>
                    <p>Acesse gráficos e estatísticas sobre padrões de desaparecimentos, faixas etárias e locais comuns.</p>
                </div>
                <span class="status soon">Em Breve</span>
            </li>
            <li>
                <i class="fas fa-search"></i>
                <div>
                    <h3>Busca Avançada de Perfis</h3>
                    <p>Ferramenta de busca com múltiplos critérios para encontrar perfis de desaparecidos com maior precisão.</p>
                </div>
                <span class="status soon">Em Breve</span>
            </li>
            <li>
                <i class="fas fa-users-cog"></i>
                <div>
                    <h3>Colaboração em Casos</h3>
                    <p>Recurso para equipes e voluntários colaborarem diretamente em casos de desaparecimento.</p>
                </div>
                <span class="status soon">Em Breve</span>
            </li>
            <li>
                <i class="fas fa-dna"></i>
                <div>
                    <h3>Banco de Dados de DNA (Restrito)</h3>
                    <p>Acesso a informações genéticas para identificação, disponível apenas para autoridades competentes.</p>
                </div>
                <span class="status soon">Em Breve</span>
            </li>
            <li>
                <i class="fas fa-camera"></i>
                <div>
                    <h3>Reconhecimento Facial (API)</h3>
                    <p>Integração com APIs de reconhecimento facial para auxiliar na identificação a partir de imagens.</p>
                </div>
                <span class="status soon">Em Breve</span>
            </li>
        </ul>
    </div>
</div>

<div class="modal-foto-bg" id="modalAlterarFoto">
    <div class="modal-foto">
        <span class="close-modal" id="closeModal">×</span>
        <form method="post" enctype="multipart/form-data" action="dados_e_ferramentas.php">
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

    // Remova o JavaScript de controle de 'details' para esta página, pois não há menu de detalhes
    // Se desejar adicionar sub-seções aqui, o código pode ser adaptado.
</script>

</body>
</html>