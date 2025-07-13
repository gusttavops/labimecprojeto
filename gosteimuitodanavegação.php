<?php
session_start();

if (!isset($_SESSION['usuario']) || !isset($_SESSION['nome'])) {
    header("Location: login.php");
    exit;
}

$nome = $_SESSION['nome'];

require_once __DIR__ . '/includes/db.php';

$foto_perfil = 'https://lh3.googleusercontent.com/a/ACg8ocJxiK2NM9FwrnfbB3aJ7rN5hJ0l6qATGGQVIaqwjakwPzSnS8Yc=s288-c-no';
$user_id = null;

try {
    $stmt = $pdo->prepare("SELECT id, foto_perfil FROM usuarios WHERE usuario = ?");
    $stmt->execute([$_SESSION['usuario']]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_data) {
        $user_id = $user_data['id'];
        if (!empty($user_data['foto_perfil'])) {
            $foto_perfil = $user_data['foto_perfil'];
        }
    } else {
        header("Location: login.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar dados do usuário em comunidades.php: " . $e->getMessage());
    header("Location: login.php");
    exit;
}

// --- Lógica para Criar Nova Comunidade ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_community'])) {
    $community_name = trim($_POST['community_name']);
    $community_description = trim($_POST['community_description']);
    $creator_id = $user_id;

    if (!empty($community_name) && !empty($community_description) && $creator_id) {
        try {
            $stmt = $pdo->prepare("INSERT INTO comunidades (nome, descricao, criador_id, data_criacao) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$community_name, $community_description, $creator_id]);
            $success_message = "Comunidade '{$community_name}' criada com sucesso!";
            header("Location: comunidades.php?success=" . urlencode($success_message));
            exit;
        } catch (PDOException $e) {
            error_log("Erro ao criar comunidade: " . $e->getMessage());
            $error_message = "Erro ao criar comunidade. O nome pode já existir. Tente novamente.";
            header("Location: comunidades.php?error=" . urlencode($error_message));
            exit;
        }
    } else {
        $error_message = "Por favor, preencha todos os campos para criar a comunidade.";
        header("Location: comunidades.php?error=" . urlencode($error_message));
        exit;
    }
}

// --- Lógica para Participar de uma Comunidade ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_community'])) {
    $community_id = $_POST['community_id_to_join'];
    $user_id_to_join = $user_id;

    if ($community_id && $user_id_to_join) {
        try {
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM membros_comunidade WHERE usuario_id = ? AND comunidade_id = ?");
            $check_stmt->execute([$user_id_to_join, $community_id]);
            if ($check_stmt->fetchColumn() == 0) {
                $stmt = $pdo->prepare("INSERT INTO membros_comunidade (usuario_id, comunidade_id, data_entrada) VALUES (?, ?, NOW())");
                $stmt->execute([$user_id_to_join, $community_id]);
                $success_message = "Você entrou na comunidade com sucesso!";
            } else {
                $success_message = "Você já é membro desta comunidade.";
            }
            header("Location: comunidades.php?success=" . urlencode($success_message));
            exit;
        } catch (PDOException $e) {
            error_log("Erro ao entrar na comunidade: " . $e->getMessage());
            $error_message = "Erro ao entrar na comunidade. Tente novamente.";
            header("Location: comunidades.php?error=" . urlencode($error_message));
            exit;
        }
    } else {
        $error_message = "Dados insuficientes para participar da comunidade.";
        header("Location: comunidades.php?error=" . urlencode($error_message));
        exit;
    }
}

// Lógica para upload de foto de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['nova_foto_modal'])) {
    if ($_FILES['nova_foto_modal']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['nova_foto_modal']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $novo_nome = 'perfil_' . $user_id . '_' . time() . '.' . $ext;
            $destino = 'uploads/' . $novo_nome;
            if (move_uploaded_file($_FILES['nova_foto_modal']['tmp_name'], $destino)) {
                try {
                    $stmt = $pdo->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id = ?");
                    $stmt->execute([$destino, $user_id]);
                    $foto_perfil = $destino;
                    $success_message = "Foto de perfil atualizada com sucesso!";
                    header("Location: comunidades.php?success=" . urlencode($success_message));
                    exit;
                } catch (PDOException $e) {
                    error_log("Erro ao atualizar foto de perfil: " . $e->getMessage());
                    $error_message = "Erro ao atualizar foto de perfil no banco de dados.";
                    header("Location: comunidades.php?error=" . urlencode($error_message));
                    exit;
                }
            } else {
                $error_message = "Erro ao mover o arquivo para a pasta de uploads.";
                header("Location: comunidades.php?error=" . urlencode($error_message));
                exit;
            }
        } else {
            $error_message = "Formato de arquivo não permitido. Apenas JPG, JPEG, PNG e GIF.";
            header("Location: comunidades.php?error=" . urlencode($error_message));
            exit;
        }
    } else {
        $error_message = "Erro no upload da foto: " . $_FILES['nova_foto_modal']['error'];
        header("Location: comunidades.php?error=" . urlencode($error_message));
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunidades - Sumidos PB</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Cores base */
        :root {
            --cor-primaria: #004d00; /* Verde Escuro */
            --cor-secundaria: #006400; /* Verde Médio */
            --cor-terciaria: #1a1a1a; /* Preto Quase Escuro */
            --cor-fundo-sidebar: #000;
            --cor-texto-claro: #fff;
            --cor-texto-escuro: #333;
            --cor-fundo-main: #f0f2f5; /* Cor de fundo mais clara, estilo Facebook */
            --cor-borda-clara: #ddd;
            --cor-sombra: rgba(0, 0, 0, 0.1);
            --cor-sucesso-bg: #d4edda;
            --cor-sucesso-text: #155724;
            --cor-erro-bg: #f8d7da;
            --cor-erro-text: #721c24;
            --cor-card-fundo: #fff; /* Fundo dos cards */
            --cor-botoes-fb: #1877f2; /* Azul do Facebook para botões */
            --cor-botoes-hover-fb: #166fe5;
        }

        /* Reset e Fontes */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Poppins', sans-serif;
            display: flex;
            height: 100vh;
            color: var(--cor-texto-escuro);
            background-color: var(--cor-fundo-main);
        }

        /* Sidebar - Estilos Modernos */
        .sidebar {
            background-color: var(--cor-fundo-sidebar);
            color: var(--cor-texto-claro);
            width: 251px;
            height: 100vh;
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 10;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2);
        }
        .user-profile {
            text-align: center;
            margin-bottom: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .user-profile img {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--cor-secundaria);
            box-shadow: 0 0 15px rgba(0, 100, 0, 0.5);
        }
        .edit-photo {
            background-color: var(--cor-secundaria);
            padding: 6px 12px;
            border-radius: 25px;
            font-size: 13px;
            cursor: pointer;
            color: var(--cor-texto-claro);
            font-weight: 500;
            margin-top: 15px;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .edit-photo:hover {
            background-color: var(--cor-primaria);
            transform: translateY(-2px);
        }
        .user-profile h3 {
            margin-top: 15px;
            font-size: 18px;
            font-weight: 600;
            color: var(--cor-texto-claro);
        }
        .sidebar button {
            width: 85%;
            padding: 14px;
            margin: 8px auto;
            background-color: #1a1a1a;
            border: none;
            color: var(--cor-texto-claro);
            cursor: pointer;
            border-radius: 35px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 12px;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        .sidebar button:hover {
            background-color: var(--cor-secundaria);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 100, 0, 0.4);
        }
        .sidebar button i {
            font-size: 18px;
            margin-left: 10px;
        }
        .sidebar .logo {
            text-align: center;
            margin-top: 40px;
            padding-bottom: 20px;
        }
        .sidebar .logo img {
            width: 110px;
            margin-bottom: 10px;
        }
        .sidebar .logo p {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: var(--cor-texto-claro);
            letter-spacing: 1px;
        }
        .sidebar .logo small {
            display: inline-block;
            margin-top: 8px;
            background-color: var(--cor-secundaria);
            padding: 4px 8px;
            border-radius: 8px;
            color: white;
            font-size: 11px;
            font-weight: 500;
        }

        /* Main Content */
        .main {
            margin-left: 251px;
            width: calc(100% - 251px);
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        .header {
            background-color: var(--cor-primaria);
            color: var(--cor-texto-claro);
            padding: 20px 30px;
            font-size: 24px;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        .content {
            flex: 1;
            padding: 20px; /* Padding geral menor para um visual mais denso como o FB */
            overflow-y: auto;
            background-color: var(--cor-fundo-main);
            display: flex;
            flex-direction: column;
            align-items: center; /* Centraliza o conteúdo principal */
        }
        .content-area {
            width: 100%;
            max-width: 800px; /* Largura máxima para o feed, como no Facebook */
            padding: 0 20px; /* Padding horizontal para o conteúdo dentro da área central */
        }

        /* Formulário de Criar Comunidade - Agora como um "post" no feed */
        .create-community-box {
            background-color: var(--cor-card-fundo);
            border-radius: 8px; /* Menos arredondado para estilo FB */
            padding: 20px;
            margin-bottom: 20px; /* Espaçamento entre os "posts" */
            box-shadow: 0 2px 4px var(--cor-sombra); /* Sombra mais sutil */
            width: 100%;
        }
        .create-community-box h2 {
            font-size: 18px; /* Título menor para se parecer com "criar publicação" */
            margin-bottom: 15px;
            color: var(--cor-texto-escuro);
            font-weight: 600;
        }
        .create-community-box label {
            display: none; /* Esconde labels para um visual mais limpo */
        }
        .create-community-box input[type="text"],
        .create-community-box textarea {
            width: 100%;
            padding: 12px; /* Padding menor */
            margin-bottom: 15px;
            border: 1px solid #ccd0d5; /* Borda mais suave, estilo FB */
            border-radius: 6px; /* Arredondamento suave */
            font-size: 15px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .create-community-box input[type="text"]:focus,
        .create-community-box textarea:focus {
            border-color: var(--cor-botoes-fb); /* Azul do FB no foco */
            box-shadow: 0 0 0 1px var(--cor-botoes-fb); /* Sombra de foco mais fina */
            outline: none;
        }
        .create-community-box textarea {
            resize: vertical;
            min-height: 80px; /* Altura mínima menor */
        }
        .create-community-box button {
            background-color: var(--cor-botoes-fb); /* Azul do FB para botões de ação */
            color: var(--cor-texto-claro);
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: background-color 0.2s ease;
            width: 100%; /* Botão ocupa toda a largura */
        }
        .create-community-box button:hover {
            background-color: var(--cor-botoes-hover-fb);
        }

        /* Lista de Comunidades (Cards no Feed) */
        .community-feed {
            width: 100%; /* Ocupa a largura do content-area */
        }
        .community-card {
            background-color: var(--cor-card-fundo);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px; /* Espaçamento entre os cards no feed */
            box-shadow: 0 2px 4px var(--cor-sombra);
            display: flex;
            flex-direction: column;
            position: relative; /* Para posicionar elementos como ícones */
        }
        .community-card:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15); /* Sombra um pouco maior no hover */
        }
        .community-card h3 {
            margin-top: 0;
            font-size: 20px;
            color: var(--cor-texto-escuro); /* Cor de texto mais neutra */
            margin-bottom: 8px;
            font-weight: 700;
        }
        .community-card p {
            font-size: 14px;
            color: #606770; /* Cor de texto mais suave, estilo FB */
            line-height: 1.5;
            margin-bottom: 15px;
            flex-grow: 1;
        }
        .community-card .join-button {
            background-color: var(--cor-botoes-fb);
            color: var(--cor-texto-claro);
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.2s ease;
            text-decoration: none;
            display: block;
            text-align: center;
            font-size: 15px;
            width: auto; /* Deixa o botão se ajustar ao conteúdo */
            align-self: flex-start; /* Alinha o botão à esquerda dentro do card */
        }
        .community-card .join-button:hover {
            background-color: var(--cor-botoes-hover-fb);
        }
        .community-card .join-button:disabled {
            background-color: #e4e6eb; /* Cinza claro do FB para desabilitado */
            color: #606770;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }

        /* Mensagens de feedback (similar ao FB) */
        .message {
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 15px;
            border: 1px solid transparent;
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            max-width: 800px;
        }
        .message i {
            font-size: 18px;
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

        /* Modal para alterar foto */
        .modal-foto-bg {
            display: none;
            position: fixed;
            z-index: 99999;
            left: 0; top: 0; width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.6);
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }
        .modal-foto {
            background: var(--cor-texto-claro);
            padding: 30px 35px;
            border-radius: 18px;
            text-align: center;
            position: relative;
            min-width: 300px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        .modal-foto .close-modal {
            position: absolute;
            right: 15px; top: 10px;
            font-size: 28px;
            color: #777;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        .modal-foto .close-modal:hover {
            color: var(--cor-erro-text);
        }
        .modal-foto label {
            font-weight: 600;
            display: block;
            margin-bottom: 15px;
            font-size: 16px;
        }
        .modal-foto input[type="file"] {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid var(--cor-borda-clara);
            border-radius: 8px;
            background-color: var(--cor-fundo-main);
        }
        .modal-foto button[type="submit"] {
            background-color: var(--cor-secundaria);
            color: var(--cor-texto-claro);
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .modal-foto button[type="submit"]:hover {
            background-color: var(--cor-primaria);
            transform: translateY(-1px);
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
        <button><i class="fas fa-user-friends"></i> Contatos</button>
        <button onclick="window.location.href='lista_desaparecidos.php'">
            <i class="fas fa-list"></i> Lista de Desaparecidos
        </button>
        <button onclick="window.location.href='registrar_desaparecido.php'">
            <i class="fas fa-user-plus"></i> Registrar Desaparecimento
        </button>
        <button onclick="window.location.href='comunidades.php'">
            <i class="fa-solid fa-circle-user"></i> Comunidade
        </button>
        <button><i class="fas fa-database"></i> Dados e Ferramentas</button>
        <button onclick="window.location.href='configuracoes.php'">
            <i class="fas fa-cogs"></i> Configurações
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
    <div class="header">Comunidades</div>

    <div class="content">
        <?php
        if (isset($_GET['success'])) {
            echo '<div class="message success"><i class="fas fa-check-circle"></i> ' . htmlspecialchars($_GET['success']) . '</div>';
        }
        if (isset($_GET['error'])) {
            echo '<div class="message error"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($_GET['error']) . '</div>';
        }
        ?>
        
        <div class="content-area">
            <div class="create-community-box">
                <h2>Crie sua própria comunidade!</h2>
                <form action="comunidades.php" method="POST">
                    <label for="community_name">Nome da Comunidade:</label>
                    <input type="text" id="community_name" name="community_name" placeholder="Dê um nome para sua comunidade (ex: Ajuda Desaparecidos JP)" required>

                    <label for="community_description">Descrição da Comunidade:</label>
                    <textarea id="community_description" name="community_description" rows="3" placeholder="Conte sobre sua comunidade: o que ela busca, quem ela ajuda, etc." required></textarea>

                    <button type="submit" name="create_community">Criar Comunidade</button>
                </form>
            </div>

            <div class="community-feed">
                <h2>Comunidades Populares</h2>
                <?php
                try {
                    $stmt = $pdo->query("SELECT id, nome, descricao FROM comunidades ORDER BY data_criacao DESC");
                    $comunidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (count($comunidades) > 0) {
                        foreach ($comunidades as $comunidade) {
                            $is_member = false;
                            if ($user_id) {
                                $check_member_stmt = $pdo->prepare("SELECT COUNT(*) FROM membros_comunidade WHERE usuario_id = ? AND comunidade_id = ?");
                                $check_member_stmt->execute([$user_id, $comunidade['id']]);
                                if ($check_member_stmt->fetchColumn() > 0) {
                                    $is_member = true;
                                }
                            }
                            ?>
                            <div class="community-card">
                                <h3><?php echo htmlspecialchars($comunidade['nome']); ?></h3>
                                <p><?php echo htmlspecialchars($comunidade['descricao']); ?></p>
                                <?php if ($is_member): ?>
                                    <button class="join-button" disabled>Você já participa</button>
                                <?php else: ?>
                                    <form action="comunidades.php" method="POST">
                                        <input type="hidden" name="community_id_to_join" value="<?php echo $comunidade['id']; ?>">
                                        <button type="submit" name="join_community" class="join-button">Participar da Comunidade</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <?php
                        }
                    } else {
                        echo "<p style='text-align: center; color: #606770;'>Nenhuma comunidade encontrada ainda. Seja o primeiro a criar uma!</p>";
                    }
                } catch (PDOException $e) {
                    error_log("Erro ao listar comunidades: " . $e->getMessage());
                    echo "<p style='text-align: center; color: #606770;'>Não foi possível carregar as comunidades no momento. Tente novamente mais tarde.</p>";
                }
                ?>
            </div>
        </div>
    </div>
</div>

<div class="modal-foto-bg" id="modalAlterarFoto">
    <div class="modal-foto">
        <span class="close-modal" id="closeModal">×</span>
        <form method="post" enctype="multipart/form-data" action="comunidades.php">
            <label for="inputFoto">Escolher nova foto:</label>
            <input type="file" name="nova_foto_modal" id="inputFoto" accept="image/*" required>
            <button type="submit">Salvar Foto</button>
        </form>
    </div>
</div>

<script>
    document.getElementById('btnAlterarFoto').addEventListener('click', function() {
        document.getElementById('modalAlterarFoto').style.display = 'flex';
    });
    document.getElementById('closeModal').addEventListener('click', function() {
        document.getElementById('modalAlterarFoto').style.display = 'none';
    });
    window.addEventListener('click', function(event) {
        if (event.target == document.getElementById('modalAlterarFoto')) {
            document.getElementById('modalAlterarFoto').style.display = 'none';
        }
    });
</script>

</body>
</html>