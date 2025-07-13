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
            $_SESSION['message'] = ['type' => 'success', 'text' => "Comunidade '{$community_name}' criada com sucesso!"];
        } catch (PDOException $e) {
            error_log("Erro ao criar comunidade: " . $e->getMessage());
            $_SESSION['message'] = ['type' => 'error', 'text' => "Erro ao criar comunidade. O nome pode já existir. Tente novamente."];
        }
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => "Por favor, preencha todos os campos para criar a comunidade."];
    }
    header("Location: comunidades.php");
    exit;
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
                $_SESSION['message'] = ['type' => 'success', 'text' => "Você entrou na comunidade com sucesso!"];
            } else {
                $_SESSION['message'] = ['type' => 'info', 'text' => "Você já é membro desta comunidade."];
            }
        } catch (PDOException $e) {
            error_log("Erro ao entrar na comunidade: " . $e->getMessage());
            $_SESSION['message'] = ['type' => 'error', 'text' => "Erro ao entrar na comunidade. Tente novamente."];
        }
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => "Dados insuficientes para participar da comunidade."];
    }
    header("Location: comunidades.php");
    exit;
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
                    $_SESSION['message'] = ['type' => 'success', 'text' => "Foto de perfil atualizada com sucesso!"];
                } catch (PDOException $e) {
                    error_log("Erro ao atualizar foto de perfil: " . $e->getMessage());
                    $_SESSION['message'] = ['type' => 'error', 'text' => "Erro ao atualizar foto de perfil no banco de dados."];
                }
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => "Erro ao mover o arquivo para a pasta de uploads."];
            }
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => "Formato de arquivo não permitido. Apenas JPG, JPEG, PNG e GIF."];
        }
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => "Erro no upload da foto: " . $_FILES['nova_foto_modal']['error']];
    }
    header("Location: comunidades.php");
    exit;
}

// Captura e limpa a mensagem da sessão
$message = null;
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// --- Funções para buscar dados para os painéis ---

// Função mock para "Top Comunidades" (você precisaria de lógica real de popularidade/membresia)
function getTopComunidades($pdo, $limit = 5) {
    try {
        // Exemplo: comunidades com mais membros, ou simplesmente as mais recentes se não houver tabela de membros populada
        $stmt = $pdo->query("SELECT c.id, c.nome, COUNT(mc.usuario_id) AS total_membros
                             FROM comunidades c
                             LEFT JOIN membros_comunidade mc ON c.id = mc.comunidade_id
                             GROUP BY c.id, c.nome
                             ORDER BY total_membros DESC, c.data_criacao DESC
                             LIMIT $limit");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar top comunidades: " . $e->getMessage());
        return [];
    }
}

// Função mock para "Publicações Recentes" (você precisaria da tabela comunidade_posts)
function getRecentPosts($pdo, $limit = 5) {
    try {
        $stmt = $pdo->query("SELECT cp.id, cp.titulo, cp.conteudo, c.nome AS comunidade_nome
                             FROM comunidade_posts cp
                             JOIN comunidades c ON cp.comunidade_id = c.id
                             ORDER BY cp.data_postagem DESC
                             LIMIT $limit");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar publicações recentes: " . $e->getMessage());
        return [];
    }
}

// Função mock para "Publicações Mais Comentadas" (você precisaria da tabela comunidade_comentarios)
function getMostCommentedPosts($pdo, $limit = 5) {
    try {
        $stmt = $pdo->query("SELECT cp.id, cp.titulo, c.nome AS comunidade_nome, COUNT(cc.id) AS total_comentarios
                             FROM comunidade_posts cp
                             JOIN comunidades c ON cp.comunidade_id = c.id
                             LEFT JOIN comunidade_comentarios cc ON cp.id = cc.post_id
                             GROUP BY cp.id, cp.titulo, c.nome
                             ORDER BY total_comentarios DESC
                             LIMIT $limit");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar publicações mais comentadas: " . $e->getMessage());
        return [];
    }
}

// Carrega os dados para os painéis
$top_comunidades = getTopComunidades($pdo);
$recent_posts = getRecentPosts($pdo);
$most_commented_posts = getMostCommentedPosts($pdo);

// Lógica para buscar todas as publicações (para o feed central)
function getAllPosts($pdo, $user_id) {
    try {
        // Isso é um placeholder. Você precisaria de um JOIN com a tabela de usuários
        // para pegar o nome do autor do post e, talvez, a foto de perfil.
        // Além disso, para votos, você teria que fazer um LEFT JOIN com comunidade_votos
        // para o usuário atual e somar os votos.
        $stmt = $pdo->prepare("
            SELECT
                cp.id,
                cp.titulo,
                cp.conteudo,
                cp.tipo_post,
                cp.link_url,
                cp.imagem_url,
                cp.data_postagem,
                c.nome AS comunidade_nome,
                u.nome AS autor_nome,
                u.foto_perfil AS autor_foto_perfil,
                (SELECT COUNT(*) FROM comunidade_comentarios WHERE post_id = cp.id) AS total_comentarios,
                (SELECT SUM(CASE WHEN tipo_voto = 'upvote' THEN 1 ELSE -1 END) FROM comunidade_votos WHERE post_id = cp.id) AS pontuacao_votos
            FROM comunidade_posts cp
            JOIN comunidades c ON cp.comunidade_id = c.id
            JOIN usuarios u ON cp.usuario_id = u.id
            ORDER BY cp.data_postagem DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar todas as publicações: " . $e->getMessage());
        return [];
    }
}
$all_posts = getAllPosts($pdo, $user_id);


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
        /* Cores base (ajustadas para um tom mais neutro/Reddit-like) */
        :root {
            --cor-primaria: #004d00; /* Verde Escuro */
            --cor-secundaria: #006400; /* Verde Médio */
            --cor-terciaria: #1a1a1a; /* Preto Quase Escuro */
            --cor-fundo-sidebar: #000;
            --cor-texto-claro: #fff;
            --cor-texto-escuro: #333;
            --cor-fundo-main: #dae0e6; /* Cor de fundo principal, cinza claro do Reddit */
            --cor-borda-clara: #ccc;
            --cor-sombra: rgba(0, 0, 0, 0.05); /* Sombra mais leve */
            --cor-card-fundo: #fff; /* Fundo dos cards */
            --cor-link-reddit: #0079d3; /* Azul de links do Reddit */
            --cor-link-hover-reddit: #0060a9;
            --cor-botoes-reddit: #0079d3; /* Azul para botões principais */
            --cor-botoes-hover-reddit: #0060a9;
            --cor-botoes-secundarios-reddit: #f6f7f8; /* Cinza claro para botões secundários */
            --cor-botoes-secundarios-hover-reddit: #e8eaed;
            --cor-upvote: #ff4500; /* Laranja do Reddit */
            --cor-downvote: #7193ff; /* Azul do Reddit */
            --cor-bordas-reddit: #edeff1; /* Borda padrão do Reddit */
            --cor-header-reddit: #cee3f8; /* Azul claro do header de blocos Reddit */
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

        /* Sidebar - Mantido o estilo original do seu projeto */
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

        /* Main Content para Layout Reddit */
        .main {
            margin-left: 251px; /* Offset da sidebar */
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
        .content-wrapper {
            display: flex;
            flex: 1; /* Ocupa o restante do espaço vertical */
            padding: 20px;
            gap: 20px; /* Espaçamento entre as colunas */
            overflow-y: auto; /* Permite scrollar o conteúdo */
            background-color: var(--cor-fundo-main);
        }

        /* Colunas de Conteúdo */
        .left-column {
            width: 280px; /* Largura da coluna de "Top Comunidades" */
            flex-shrink: 0; /* Não encolhe */
        }
        .central-feed {
            flex-grow: 1; /* Ocupa o espaço restante no centro */
            max-width: 700px; /* Largura máxima para o feed principal */
        }
        .right-column {
            width: 300px; /* Largura da coluna direita */
            flex-shrink: 0; /* Não encolhe */
        }

        /* Estilo para os cards de blocos (Top Comunidades, Publicações Recentes, Mais Comentadas) */
        .reddit-block {
            background-color: var(--cor-card-fundo);
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 0 0 1px var(--cor-bordas-reddit), 0 2px 4px var(--cor-sombra); /* Borda e sombra sutis */
            overflow: hidden; /* Para garantir que o border-radius funcione com o header */
        }
        .reddit-block-header {
            background-color: var(--cor-header-reddit); /* Azul claro do Reddit */
            color: var(--cor-texto-escuro);
            padding: 12px 15px;
            font-size: 16px;
            font-weight: 600;
            border-bottom: 1px solid var(--cor-bordas-reddit);
        }
        .reddit-block-content {
            padding: 15px;
        }
        .reddit-block-content ul {
            list-style: none;
        }
        .reddit-block-content li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .reddit-block-content li:last-child {
            border-bottom: none;
        }
        .reddit-block-content a {
            color: var(--cor-texto-escuro);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.2s ease;
            display: block;
        }
        .reddit-block-content a:hover {
            color: var(--cor-link-reddit);
            text-decoration: underline;
        }
        .reddit-block-content .community-name-small {
            font-size: 13px;
            color: #777;
            margin-top: 3px;
        }

        /* Create Post/Community Box (similar ao Reddit) */
        .create-post-box, .create-community-box {
            background-color: var(--cor-card-fundo);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 0 0 1px var(--cor-bordas-reddit), 0 2px 4px var(--cor-sombra);
            display: flex;
            align-items: center; /* Alinha itens verticalmente */
            gap: 10px; /* Espaço entre a foto e o input */
        }
        .create-post-box img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .create-post-box input[type="text"] {
            flex-grow: 1; /* O input ocupa o espaço restante */
            padding: 10px 15px;
            border: 1px solid var(--cor-borda-clara);
            border-radius: 20px; /* Borda bem arredondada */
            font-size: 15px;
            background-color: var(--cor-fundo-main); /* Fundo sutil */
            transition: border-color 0.2s ease, background-color 0.2s ease;
        }
        .create-post-box input[type="text"]:hover {
            background-color: #ebedef;
            border-color: #b0b8bc;
        }
        .create-post-box input[type="text"]:focus {
            outline: none;
            border-color: var(--cor-link-reddit);
            background-color: var(--cor-card-fundo);
        }
        .create-post-box .post-buttons button {
            background-color: transparent;
            border: none;
            color: #878a8c;
            padding: 8px 10px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        .create-post-box .post-buttons button:hover {
            background-color: var(--cor-botoes-secundarios-hover-reddit);
            color: var(--cor-link-reddit);
        }
        .create-post-box .post-buttons button i {
            margin-right: 5px;
        }

        /* Formulário de criar nova comunidade (dentro do create-post-box para consistência) */
        .create-community-box form {
            display: flex;
            flex-direction: column;
            width: 100%;
        }
        .create-community-box h2 {
            font-size: 18px;
            margin-bottom: 15px;
            color: var(--cor-texto-escuro);
            font-weight: 600;
        }
        .create-community-box label {
            display: none;
        }
        .create-community-box input[type="text"],
        .create-community-box textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ccd0d5;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .create-community-box input[type="text"]:focus,
        .create-community-box textarea:focus {
            border-color: var(--cor-link-reddit);
            box-shadow: 0 0 0 1px var(--cor-link-reddit);
            outline: none;
        }
        .create-community-box textarea {
            resize: vertical;
            min-height: 80px;
        }
        .create-community-box button[type="submit"] {
            background-color: var(--cor-botoes-reddit);
            color: var(--cor-texto-claro);
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: background-color 0.2s ease;
            width: 100%;
        }
        .create-community-box button[type="submit"]:hover {
            background-color: var(--cor-botoes-hover-reddit);
        }


        /* Post Card (central feed) */
        .post-card {
            background-color: var(--cor-card-fundo);
            border-radius: 8px;
            margin-bottom: 10px; /* Espaço entre posts */
            box-shadow: 0 0 0 1px var(--cor-bordas-reddit), 0 2px 4px var(--cor-sombra);
            display: flex;
            overflow: hidden; /* Para cantos arredondados */
        }
        .post-card:hover {
            border: 1px solid var(--cor-link-reddit); /* Borda azul no hover */
            box-shadow: 0 0 0 1px var(--cor-link-reddit), 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .post-card .vote-section {
            background-color: #f8f9fa; /* Fundo cinza claro para seção de votos */
            width: 45px;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 8px 0;
            flex-shrink: 0;
            border-right: 1px solid var(--cor-bordas-reddit);
        }
        .post-card .vote-section button {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 20px;
            color: #878a8c;
            padding: 3px;
            transition: color 0.2s ease;
        }
        .post-card .vote-section button.upvote:hover {
            color: var(--cor-upvote);
        }
        .post-card .vote-section button.downvote:hover {
            color: var(--cor-downvote);
        }
        .post-card .vote-section .score {
            font-weight: 600;
            margin: 5px 0;
            font-size: 14px;
            color: var(--cor-texto-escuro);
        }
        .post-card .post-content {
            flex-grow: 1;
            padding: 10px 15px;
            display: flex;
            flex-direction: column;
        }
        .post-card .post-meta {
            font-size: 12px;
            color: #777;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .post-card .post-meta img {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            object-fit: cover;
        }
        .post-card .post-meta a {
            color: var(--cor-link-reddit);
            text-decoration: none;
            font-weight: 500;
        }
        .post-card .post-meta a:hover {
            text-decoration: underline;
        }
        .post-card h3 {
            font-size: 18px;
            margin-bottom: 8px;
            color: var(--cor-texto-escuro);
            line-height: 1.3;
        }
        .post-card .post-body {
            font-size: 14px;
            color: #444;
            margin-bottom: 10px;
            line-height: 1.6;
        }
        .post-card .post-image,
        .post-card .post-link {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            margin-top: 10px;
            display: block; /* Garante que a imagem se ajuste */
        }
        .post-card .post-actions {
            display: flex;
            gap: 10px;
            margin-top: auto; /* Empurra as ações para baixo */
        }
        .post-card .post-actions button {
            background-color: var(--cor-botoes-secundarios-reddit);
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            color: #555;
            transition: background-color 0.2s ease;
        }
        .post-card .post-actions button i {
            margin-right: 5px;
        }
        .post-card .post-actions button:hover {
            background-color: var(--cor-botoes-secundarios-hover-reddit);
        }
        .post-card .post-actions button.comments {
            color: var(--cor-link-reddit);
        }

        /* Mensagens de feedback */
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
            max-width: 800px; /* Adapta ao layout central */
            margin: 0 auto 20px auto; /* Centraliza */
        }
        .message i {
            font-size: 18px;
        }
        .message.success {
            background-color: var(--cor-sucesso-bg);
            color: var(--cor-sucesso-text);
            border-color: #c3e6cb;
        }
        .message.error {
            background-color: var(--cor-erro-bg);
            color: var(--cor-erro-text);
            border-color: #f5c6cb;
        }
        .message.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border-color: #bee5eb;
        }

        /* Modal para alterar foto - Mantido o estilo original */
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

    <div class="content-wrapper">
        <div class="left-column">
            <div class="reddit-block">
                <div class="reddit-block-header">Top Comunidades</div>
                <div class="reddit-block-content">
                    <?php if (!empty($top_comunidades)): ?>
                        <ul>
                            <?php foreach ($top_comunidades as $comm): ?>
                                <li>
                                    <a href="comunidade.php?id=<?php echo htmlspecialchars($comm['id']); ?>">
                                        r/<?php echo htmlspecialchars($comm['nome']); ?>
                                        <span class="community-name-small">(<?php echo htmlspecialchars($comm['total_membros']); ?> membros)</span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>Nenhuma comunidade popular encontrada.</p>
                    <?php endif; ?>
                </div>
            </div>
            </div>

        <div class="central-feed">
            <?php
            if ($message) {
                echo '<div class="message ' . htmlspecialchars($message['type']) . '">';
                echo '<i class="fas fa-' . ($message['type'] === 'success' ? 'check-circle' : ($message['type'] === 'info' ? 'info-circle' : 'exclamation-circle')) . '"></i> ';
                echo htmlspecialchars($message['text']);
                echo '</div>';
            }
            ?>

            <div class="create-post-box">
                <img src="<?php echo htmlspecialchars($foto_perfil); ?>" alt="Seu Avatar">
                <input type="text" placeholder="Criar nova publicação" onclick="window.location.href='criar_publicacao.php'">
                <div class="post-buttons">
                    <button type="button" title="Publicar Imagem"><i class="fas fa-image"></i></button>
                    <button type="button" title="Publicar Link"><i class="fas fa-link"></i></button>
                </div>
            </div>

            <div class="create-community-box reddit-block">
                <div class="reddit-block-header">Crie sua própria Comunidade</div>
                <div class="reddit-block-content">
                    <form action="comunidades.php" method="POST">
                        <input type="text" id="community_name" name="community_name" placeholder="Nome da comunidade (ex: ParaibaAjuda)" required>
                        <textarea id="community_description" name="community_description" rows="3" placeholder="Uma breve descrição sobre a comunidade..." required></textarea>
                        <button type="submit" name="create_community">Criar Comunidade</button>
                    </form>
                </div>
            </div>

            <?php if (!empty($all_posts)): ?>
                <?php foreach ($all_posts as $post): ?>
                    <div class="post-card">
                        <div class="vote-section">
                            <button class="upvote"><i class="fas fa-arrow-up"></i></button>
                            <span class="score"><?php echo (isset($post['pontuacao_votos']) ? htmlspecialchars($post['pontuacao_votos']) : 0); ?></span>
                            <button class="downvote"><i class="fas fa-arrow-down"></i></button>
                        </div>
                        <div class="post-content">
                            <div class="post-meta">
                                <img src="<?php echo htmlspecialchars($post['autor_foto_perfil'] ?? 'https://lh3.googleusercontent.com/a/ACg8ocJxiK2NM9FwrnfbB3aJ7rN5hJ0l6qATGGQVIaqwjakwPzSnS8Yc=s288-c-no'); ?>" alt="Autor">
                                Postado por <a href="#"><?php echo htmlspecialchars($post['autor_nome']); ?></a> em <a href="comunidade.php?id=<?php echo htmlspecialchars($post['comunidade_id']); ?>">r/<?php echo htmlspecialchars($post['comunidade_nome']); ?></a>
                                <span>há <?php echo floor((time() - strtotime($post['data_postagem'])) / 3600); ?>h</span> </div>
                            <h3><?php echo htmlspecialchars($post['titulo']); ?></h3>
                            <?php if ($post['tipo_post'] === 'texto'): ?>
                                <p class="post-body"><?php echo nl2br(htmlspecialchars($post['conteudo'])); ?></p>
                            <?php elseif ($post['tipo_post'] === 'imagem' && !empty($post['imagem_url'])): ?>
                                <img src="<?php echo htmlspecialchars($post['imagem_url']); ?>" alt="Imagem da Publicação" class="post-image">
                            <?php elseif ($post['tipo_post'] === 'link' && !empty($post['link_url'])): ?>
                                <a href="<?php echo htmlspecialchars($post['link_url']); ?>" target="_blank" class="post-link"><?php echo htmlspecialchars($post['link_url']); ?></a>
                            <?php endif; ?>

                            <div class="post-actions">
                                <button class="comments">
                                    <i class="fas fa-comment-alt"></i> <?php echo (isset($post['total_comentarios']) ? htmlspecialchars($post['total_comentarios']) : 0); ?> Comentários
                                </button>
                                <button><i class="fas fa-share"></i> Compartilhar</button>
                                <button><i class="fas fa-bookmark"></i> Salvar</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #666; margin-top: 50px;">Nenhuma publicação encontrada ainda. Seja o primeiro a criar uma!</p>
            <?php endif; ?>
        </div>

        <div class="right-column">
            <div class="reddit-block">
                <div class="reddit-block-header">Publicações Recentes</div>
                <div class="reddit-block-content">
                    <?php if (!empty($recent_posts)): ?>
                        <ul>
                            <?php foreach ($recent_posts as $post): ?>
                                <li>
                                    <a href="post.php?id=<?php echo htmlspecialchars($post['id']); ?>">
                                        <?php echo htmlspecialchars($post['titulo']); ?>
                                        <span class="community-name-small">em r/<?php echo htmlspecialchars($post['comunidade_nome']); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>Nenhuma publicação recente.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="reddit-block">
                <div class="reddit-block-header">Publicações Mais Comentadas</div>
                <div class="reddit-block-content">
                    <?php if (!empty($most_commented_posts)): ?>
                        <ul>
                            <?php foreach ($most_commented_posts as $post): ?>
                                <li>
                                    <a href="post.php?id=<?php echo htmlspecialchars($post['id']); ?>">
                                        <?php echo htmlspecialchars($post['titulo']); ?>
                                        <span class="community-name-small">em r/<?php echo htmlspecialchars($post['comunidade_nome']); ?> (<?php echo htmlspecialchars($post['total_comentarios']); ?> comments)</span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>Nenhuma publicação comentada.</p>
                    <?php endif; ?>
                </div>
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