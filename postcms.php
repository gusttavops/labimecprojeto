<?php
session_start();

if (!isset($_SESSION['usuario']) || !isset($_SESSION['nome'])) {
    header("Location: login.php");
    exit;
}

$nome_usuario_logado = $_SESSION['nome'];

require_once __DIR__ . '/includes/db.php'; // Inclui sua conexão com o banco de dados

$user_id = null;
$foto_perfil_usuario_logado = 'https://lh3.googleusercontent.com/a/ACg8ocJxiK2NM9FwrnfbB3aJ7rN5hJ0l6qATGGQVIaqwjakwPzSnS8Yc=s288-c-no'; // Default

try {
    $stmt = $pdo->prepare("SELECT id, foto_perfil FROM usuarios WHERE usuario = ?");
    $stmt->execute([$_SESSION['usuario']]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_data) {
        $user_id = $user_data['id'];
        if (!empty($user_data['foto_perfil'])) {
            $foto_perfil_usuario_logado = $user_data['foto_perfil'];
        }
    } else {
        header("Location: login.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar dados do usuário em post.php: " . $e->getMessage());
    header("Location: login.php");
    exit;
}

$post_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$post_id) {
    $_SESSION['message'] = ['type' => 'error', 'text' => "Publicação não especificada."];
    header("Location: comunidades.php");
    exit;
}

$post = null;
$comments = [];
$message = null;

try {
    // Buscar detalhes da publicação
    $stmt_post = $pdo->prepare("
        SELECT
            cp.id,
            cp.titulo,
            cp.conteudo,
            cp.tipo_post,
            cp.link_url,
            cp.imagem_url,
            cp.data_postagem,
            c.id AS comunidade_id,
            c.nome AS comunidade_nome,
            u.id AS autor_id,
            u.nome AS autor_nome,
            u.foto_perfil AS autor_foto_perfil,
            (SELECT SUM(CASE WHEN tipo_voto = 'upvote' THEN 1 ELSE -1 END) FROM comunidade_votos WHERE post_id = cp.id) AS pontuacao_votos,
            (SELECT tipo_voto FROM comunidade_votos WHERE post_id = cp.id AND usuario_id = ?) AS user_voto
        FROM comunidade_posts cp
        JOIN comunidades c ON cp.comunidade_id = c.id
        JOIN usuarios u ON cp.usuario_id = u.id
        WHERE cp.id = ?
    ");
    $stmt_post->execute([$user_id, $post_id]);
    $post = $stmt_post->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        $_SESSION['message'] = ['type' => 'error', 'text' => "Publicação não encontrada."];
        header("Location: comunidades.php");
        exit;
    }

    // Buscar comentários (ordem por mais recente, ou por votos, ou por aninhamento)
    // Para aninhamento complexo, seria necessário uma função recursiva ou lógica JS
    $stmt_comments = $pdo->prepare("
        SELECT
            cc.id,
            cc.conteudo,
            cc.data_comentario,
            cc.parent_comentario_id,
            u.id AS usuario_comentario_id,
            u.nome AS usuario_comentario_nome,
            u.foto_perfil AS usuario_comentario_foto_perfil
        FROM comunidade_comentarios cc
        JOIN usuarios u ON cc.usuario_id = u.id
        WHERE cc.post_id = ?
        ORDER BY cc.data_comentario ASC
    ");
    $stmt_comments->execute([$post_id]);
    $comments_raw = $stmt_comments->fetchAll(PDO::FETCH_ASSOC);

    // Organizar comentários em uma estrutura aninhada (simples)
    $comments_tree = [];
    $indexed_comments = [];

    foreach ($comments_raw as $comment) {
        $indexed_comments[$comment['id']] = $comment;
        $indexed_comments[$comment['id']]['children'] = [];
    }

    foreach ($indexed_comments as $id => $comment) {
        if ($comment['parent_comentario_id'] !== null && isset($indexed_comments[$comment['parent_comentario_id']])) {
            $indexed_comments[$comment['parent_comentario_id']]['children'][] = &$indexed_comments[$id];
        } else {
            $comments_tree[] = &$indexed_comments[$id];
        }
    }
    $comments = $comments_tree;

} catch (PDOException $e) {
    error_log("Erro ao carregar publicação ou comentários: " . $e->getMessage());
    $_SESSION['message'] = ['type' => 'error', 'text' => "Erro ao carregar conteúdo. Tente novamente."];
    header("Location: comunidades.php");
    exit;
}

// --- Lógica para adicionar comentário ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $conteudo_comentario = trim(filter_input(INPUT_POST, 'conteudo_comentario', FILTER_SANITIZE_STRING));
    $parent_comentario_id = filter_input(INPUT_POST, 'parent_comentario_id', FILTER_VALIDATE_INT);

    if (empty($conteudo_comentario)) {
        $message = ['type' => 'error', 'text' => "O conteúdo do comentário não pode ser vazio."];
    } else {
        try {
            $stmt_insert_comment = $pdo->prepare("INSERT INTO comunidade_comentarios (post_id, usuario_id, conteudo, data_comentario, parent_comentario_id) VALUES (?, ?, ?, NOW(), ?)");
            $stmt_insert_comment->execute([$post_id, $user_id, $conteudo_comentario, $parent_comentario_id]);

            $_SESSION['message'] = ['type' => 'success', 'text' => "Comentário adicionado com sucesso!"];
            header("Location: post.php?id=" . $post_id); // Redireciona para recarregar com o novo comentário
            exit;
        } catch (PDOException $e) {
            error_log("Erro ao adicionar comentário: " . $e->getMessage());
            $message = ['type' => 'error', 'text' => "Erro ao adicionar comentário. Tente novamente."];
        }
    }
}

// Lógica para votação (chamada por AJAX)
// Este PHP será acionado por um fetch() em JavaScript, não um post de formulário normal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && ($_POST['action'] === 'upvote' || $_POST['action'] === 'downvote')) {
    header('Content-Type: application/json'); // Responde com JSON
    $post_id_vote = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
    $vote_type = $_POST['action'];

    if (!$post_id_vote || $post_id_vote != $post_id || !$user_id) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos para votar.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Verificar voto existente do usuário para este post
        $stmt_check_vote = $pdo->prepare("SELECT id, tipo_voto FROM comunidade_votos WHERE usuario_id = ? AND post_id = ?");
        $stmt_check_vote->execute([$user_id, $post_id_vote]);
        $existing_vote = $stmt_check_vote->fetch(PDO::FETCH_ASSOC);

        $current_score = $post['pontuacao_votos'] ?? 0;
        $new_score = $current_score;

        if ($existing_vote) {
            // Já existe um voto
            if ($existing_vote['tipo_voto'] === $vote_type) {
                // Se votou no mesmo tipo novamente, remove o voto
                $stmt_delete = $pdo->prepare("DELETE FROM comunidade_votos WHERE id = ?");
                $stmt_delete->execute([$existing_vote['id']]);
                $new_score += ($vote_type === 'upvote' ? -1 : 1); // Desfaz o voto
            } else {
                // Se votou no tipo oposto, muda o voto
                $stmt_update = $pdo->prepare("UPDATE comunidade_votos SET tipo_voto = ? WHERE id = ?");
                $stmt_update->execute([$vote_type, $existing_vote['id']]);
                $new_score += ($vote_type === 'upvote' ? 2 : -2); // Desfaz o antigo e adiciona o novo (ex: -1 para 1 é +2)
            }
        } else {
            // Não existe voto, insere um novo
            $stmt_insert_vote = $pdo->prepare("INSERT INTO comunidade_votos (usuario_id, post_id, tipo_voto) VALUES (?, ?, ?)");
            $stmt_insert_vote->execute([$user_id, $post_id_vote, $vote_type]);
            $new_score += ($vote_type === 'upvote' ? 1 : -1);
        }

        // 2. Atualizar a pontuação total do post (opcional, pode ser calculada on-the-fly)
        // Se você decidiu armazenar a pontuação total na tabela 'comunidade_posts', atualize-a aqui:
        // $stmt_update_post_score = $pdo->prepare("UPDATE comunidade_posts SET pontuacao_votos = ? WHERE id = ?");
        // $stmt_update_post_score->execute([$new_score, $post_id_vote]);

        $pdo->commit();
        echo json_encode(['success' => true, 'new_score' => $new_score, 'user_voto' => ($existing_vote && $existing_vote['tipo_voto'] === $vote_type) ? null : $vote_type]);
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Erro ao processar voto: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro interno ao votar.']);
        exit;
    }
}


// Função para renderizar comentários (para lidar com aninhamento)
function renderComments($comments, $foto_perfil_logado, $user_id_logado, $post_id, $depth = 0) {
    if (empty($comments)) return;

    echo '<ul class="comments-list ' . ($depth > 0 ? 'nested-comments' : '') . '">';
    foreach ($comments as $comment) {
        $is_author = ($comment['usuario_comentario_id'] == $user_id_logado);
        ?>
        <li class="comment-item">
            <div class="comment-header">
                <img src="<?php echo htmlspecialchars($comment['usuario_comentario_foto_perfil'] ?? 'https://lh3.googleusercontent.com/a/ACg8ocJxiK2NM9FwrnfbB3aJ7rN5hJ0l6qATGGQVIaqwjakwPzSnS8Yc=s288-c-no'); ?>" alt="Avatar" class="comment-avatar">
                <span class="comment-author"><?php echo htmlspecialchars($comment['usuario_comentario_nome']); ?></span>
                <span class="comment-time">há <?php echo floor((time() - strtotime($comment['data_comentario'])) / 3600); ?>h</span>
            </div>
            <div class="comment-body">
                <p><?php echo nl2br(htmlspecialchars($comment['conteudo'])); ?></p>
            </div>
            <div class="comment-actions">
                <button class="reply-button" data-comment-id="<?php echo htmlspecialchars($comment['id']); ?>"><i class="fas fa-reply"></i> Responder</button>
                <?php if ($is_author): ?>
                    <?php endif; ?>
            </div>
            <div class="reply-form-container" id="reply-form-<?php echo htmlspecialchars($comment['id']); ?>" style="display: none;">
                <form action="post.php?id=<?php echo htmlspecialchars($post_id); ?>" method="POST" class="add-comment-form">
                    <input type="hidden" name="add_comment" value="1">
                    <input type="hidden" name="parent_comentario_id" value="<?php echo htmlspecialchars($comment['id']); ?>">
                    <div class="comment-input-area">
                        <img src="<?php echo htmlspecialchars($foto_perfil_logado); ?>" alt="Seu Avatar">
                        <textarea name="conteudo_comentario" placeholder="Escreva sua resposta..." required></textarea>
                    </div>
                    <div class="comment-form-actions">
                        <button type="button" class="cancel-reply-button">Cancelar</button>
                        <button type="submit">Responder</button>
                    </div>
                </form>
            </div>
        </li>
        <?php
        // Renderiza filhos recursivamente
        if (!empty($comment['children'])) {
            renderComments($comment['children'], $foto_perfil_logado, $user_id_logado, $post_id, $depth + 1);
        }
    }
    echo '</ul>';
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['titulo']); ?> - Sumidos PB</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --cor-primaria: #004d00;
            --cor-secundaria: #006400;
            --cor-fundo-main: #dae0e6;
            --cor-card-fundo: #fff;
            --cor-texto-escuro: #333;
            --cor-link-reddit: #0079d3;
            --cor-botoes-reddit: #0079d3;
            --cor-botoes-hover-reddit: #0060a9;
            --cor-borda-clara: #ccd0d5;
            --cor-bordas-reddit: #edeff1;
            --cor-sombra: rgba(0, 0, 0, 0.05);
            --cor-upvote: #ff4500;
            --cor-downvote: #7193ff;
            --cor-botoes-secundarios-reddit: #f6f7f8;
            --cor-botoes-secundarios-hover-reddit: #e8eaed;
            --cor-sucesso-bg: #d4edda;
            --cor-sucesso-text: #155724;
            --cor-erro-bg: #f8d7da;
            --cor-erro-text: #721c24;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--cor-fundo-main);
            color: var(--cor-texto-escuro);
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            background-color: var(--cor-card-fundo);
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 0 0 1px var(--cor-bordas-reddit), 0 4px 12px var(--cor-sombra);
            width: 100%;
            max-width: 800px;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            color: var(--cor-link-reddit);
            margin-bottom: 25px;
            font-weight: 500;
            transition: color 0.2s ease;
            font-size: 15px;
        }
        .back-button:hover {
            color: var(--cor-botoes-hover-reddit);
            text-decoration: underline;
        }

        /* Post Card Styling (similar to communities.php but full width) */
        .post-detail-card {
            background-color: var(--cor-card-fundo);
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 0 0 1px var(--cor-bordas-reddit), 0 2px 4px var(--cor-sombra);
            display: flex;
            overflow: hidden;
        }
        .post-detail-card .vote-section {
            background-color: #f8f9fa;
            width: 50px; /* Slightly wider */
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 10px 0;
            flex-shrink: 0;
            border-right: 1px solid var(--cor-bordas-reddit);
        }
        .post-detail-card .vote-section button {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 24px; /* Larger icons */
            color: #878a8c;
            padding: 5px;
            transition: color 0.2s ease;
        }
        .post-detail-card .vote-section button.upvote:hover { color: var(--cor-upvote); }
        .post-detail-card .vote-section button.downvote:hover { color: var(--cor-downvote); }
        .post-detail-card .vote-section button.active.upvote { color: var(--cor-upvote); }
        .post-detail-card .vote-section button.active.downvote { color: var(--cor-downvote); }

        .post-detail-card .vote-section .score {
            font-weight: 700; /* Bolder score */
            margin: 8px 0;
            font-size: 16px;
            color: var(--cor-texto-escuro);
        }
        .post-detail-card .post-content {
            flex-grow: 1;
            padding: 15px 20px;
            display: flex;
            flex-direction: column;
        }
        .post-detail-card .post-meta {
            font-size: 13px;
            color: #777;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .post-detail-card .post-meta img {
            width: 24px; /* Larger avatar */
            height: 24px;
            border-radius: 50%;
            object-fit: cover;
        }
        .post-detail-card .post-meta a {
            color: var(--cor-link-reddit);
            text-decoration: none;
            font-weight: 500;
        }
        .post-detail-card .post-meta a:hover {
            text-decoration: underline;
        }
        .post-detail-card h1 {
            font-size: 26px; /* Larger title */
            margin-bottom: 12px;
            color: var(--cor-texto-escuro);
            line-height: 1.3;
            text-align: left; /* Alinhamento do título principal */
        }
        .post-detail-card .post-body {
            font-size: 16px; /* Larger body text */
            color: #444;
            margin-bottom: 15px;
            line-height: 1.7;
        }
        .post-detail-card .post-image,
        .post-detail-card .post-link {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            margin-top: 15px;
            display: block;
            border: 1px solid var(--cor-bordas-reddit);
        }
        .post-detail-card .post-actions {
            display: flex;
            gap: 10px;
            margin-top: auto;
            border-top: 1px solid var(--cor-bordas-reddit);
            padding-top: 10px;
        }
        .post-detail-card .post-actions button {
            background-color: var(--cor-botoes-secundarios-reddit);
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #555;
            transition: background-color 0.2s ease;
        }
        .post-detail-card .post-actions button i {
            margin-right: 5px;
        }
        .post-detail-card .post-actions button:hover {
            background-color: var(--cor-botoes-secundarios-hover-reddit);
        }

        /* Comment Section */
        .comments-section {
            margin-top: 30px;
        }

        h2 {
            font-size: 22px;
            color: var(--cor-texto-escuro);
            margin-bottom: 20px;
            font-weight: 600;
            border-bottom: 1px solid var(--cor-bordas-reddit);
            padding-bottom: 10px;
        }

        .add-comment-form {
            background-color: #f6f7f8; /* Light gray background for comment form */
            border: 1px solid var(--cor-bordas-reddit);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
        }
        .add-comment-form .comment-input-area {
            display: flex;
            gap: 10px;
            align-items: flex-start; /* Alinha a imagem com o topo do textarea */
            margin-bottom: 15px;
        }
        .add-comment-form .comment-input-area img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }
        .add-comment-form textarea {
            flex-grow: 1;
            padding: 10px 12px;
            border: 1px solid var(--cor-borda-clara);
            border-radius: 6px;
            font-size: 15px;
            min-height: 80px;
            resize: vertical;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .add-comment-form textarea:focus {
            outline: none;
            border-color: var(--cor-link-reddit);
            box-shadow: 0 0 0 1px var(--cor-link-reddit);
        }
        .comment-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .comment-form-actions button {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            transition: background-color 0.2s ease;
        }
        .comment-form-actions button[type="submit"] {
            background-color: var(--cor-botoes-reddit);
            color: #fff;
        }
        .comment-form-actions button[type="submit"]:hover {
            background-color: var(--cor-botoes-hover-reddit);
        }
        .comment-form-actions .cancel-reply-button {
            background-color: var(--cor-botoes-secundarios-reddit);
            color: #555;
        }
        .comment-form-actions .cancel-reply-button:hover {
            background-color: var(--cor-botoes-secundarios-hover-reddit);
        }


        .comments-list {
            list-style: none;
            padding-left: 0;
        }
        .comment-item {
            background-color: var(--cor-card-fundo);
            border: 1px solid var(--cor-bordas-reddit);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            position: relative;
        }
        .comments-list.nested-comments {
            margin-left: 20px; /* Indentação para comentários aninhados */
            padding-left: 10px;
            border-left: 2px solid var(--cor-borda-clara); /* Linha vertical */
            margin-top: 10px;
            padding-top: 5px;
        }
        .comment-item + .comments-list.nested-comments {
             /* Adjust margin if next element is a nested list */
            margin-top: -5px; /* Overlap slightly for cleaner visual */
        }
        .comment-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            font-size: 13px;
            color: #777;
        }
        .comment-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }
        .comment-author {
            font-weight: 600;
            color: var(--cor-texto-escuro);
        }
        .comment-body p {
            font-size: 15px;
            line-height: 1.6;
            color: #444;
            margin-bottom: 10px;
        }
        .comment-actions button {
            background: none;
            border: none;
            color: #777;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            padding: 5px 8px;
            border-radius: 4px;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        .comment-actions button i {
            margin-right: 4px;
        }
        .comment-actions button:hover {
            background-color: var(--cor-botoes-secundarios-hover-reddit);
            color: var(--cor-link-reddit);
        }

        .reply-form-container {
            margin-top: 10px;
            background-color: #fcfcfc;
            border: 1px solid var(--cor-bordas-reddit);
            border-radius: 8px;
            padding: 10px;
        }

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
            box-sizing: border-box;
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
    </style>
</head>
<body>
    <div class="container">
        <a href="comunidades.php" class="back-button"><i class="fas fa-arrow-left"></i> Voltar para Comunidades</a>

        <?php if ($message): ?>
            <div class="message <?php echo htmlspecialchars($message['type']); ?>">
                <i class="fas fa-<?php echo ($message['type'] === 'success' ? 'check-circle' : ($message['type'] === 'info' ? 'info-circle' : 'exclamation-circle')); ?>"></i>
                <?php echo htmlspecialchars($message['text']); ?>
            </div>
        <?php endif; ?>

        <div class="post-detail-card" data-post-id="<?php echo htmlspecialchars($post['id']); ?>">
            <div class="vote-section">
                <button class="upvote-btn <?php echo ($post['user_voto'] === 'upvote' ? 'active' : ''); ?>"><i class="fas fa-arrow-up"></i></button>
                <span class="score"><?php echo (isset($post['pontuacao_votos']) ? htmlspecialchars($post['pontuacao_votos']) : 0); ?></span>
                <button class="downvote-btn <?php echo ($post['user_voto'] === 'downvote' ? 'active' : ''); ?>"><i class="fas fa-arrow-down"></i></button>
            </div>
            <div class="post-content">
                <div class="post-meta">
                    <img src="<?php echo htmlspecialchars($post['autor_foto_perfil'] ?? 'https://lh3.googleusercontent.com/a/ACg8ocJxiK2NM9FwrnfbB3aJ7rN5hJ0l6qATGGQVIaqwjakwPzSnS8Yc=s288-c-no'); ?>" alt="Autor">
                    Postado por <a href="#"><?php echo htmlspecialchars($post['autor_nome']); ?></a> em <a href="comunidade.php?id=<?php echo htmlspecialchars($post['comunidade_id']); ?>">r/<?php echo htmlspecialchars($post['comunidade_nome']); ?></a>
                    <span>há <?php echo floor((time() - strtotime($post['data_postagem'])) / 3600); ?>h</span>
                </div>
                <h1><?php echo htmlspecialchars($post['titulo']); ?></h1>
                <?php if ($post['tipo_post'] === 'texto'): ?>
                    <p class="post-body"><?php echo nl2br(htmlspecialchars($post['conteudo'])); ?></p>
                <?php elseif ($post['tipo_post'] === 'imagem' && !empty($post['imagem_url'])): ?>
                    <img src="<?php echo htmlspecialchars($post['imagem_url']); ?>" alt="Imagem da Publicação" class="post-image">
                <?php elseif ($post['tipo_post'] === 'link' && !empty($post['link_url'])): ?>
                    <a href="<?php echo htmlspecialchars($post['link_url']); ?>" target="_blank" class="post-link"><?php echo htmlspecialchars($post['link_url']); ?></a>
                    <p class="post-body"><?php echo nl2br(htmlspecialchars($post['conteudo'])); ?></p>
                <?php endif; ?>

                <div class="post-actions">
                    <button><i class="fas fa-comment-alt"></i> Comentar</button>
                    <button><i class="fas fa-share"></i> Compartilhar</button>
                    <button><i class="fas fa-bookmark"></i> Salvar</button>
                </div>
            </div>
        </div>

        <div class="comments-section">
            <h2>Comentários</h2>

            <form action="post.php?id=<?php echo htmlspecialchars($post['id']); ?>" method="POST" class="add-comment-form">
                <input type="hidden" name="add_comment" value="1">
                <div class="comment-input-area">
                    <img src="<?php echo htmlspecialchars($foto_perfil_usuario_logado); ?>" alt="Seu Avatar">
                    <textarea name="conteudo_comentario" placeholder="Escreva seu comentário..." required></textarea>
                </div>
                <div class="comment-form-actions">
                    <button type="submit">Comentar</button>
                </div>
            </form>

            <?php if (!empty($comments)): ?>
                <?php renderComments($comments, $foto_perfil_usuario_logado, $user_id, $post_id); ?>
            <?php else: ?>
                <p style="text-align: center; color: #666; margin-top: 30px;">Nenhum comentário ainda. Seja o primeiro a comentar!</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Função para formatar o tempo (ajuste se preferir uma biblioteca como Moment.js)
        function formatTimeAgo(dateString) {
            const now = new Date();
            const date = new Date(dateString);
            const seconds = Math.floor((now - date) / 1000);

            let interval = seconds / 31536000;
            if (interval > 1) return Math.floor(interval) + " anos";
            interval = seconds / 2592000;
            if (interval > 1) return Math.floor(interval) + " meses";
            interval = seconds / 86400;
            if (interval > 1) return Math.floor(interval) + " dias";
            interval = seconds / 3600;
            if (interval > 1) return Math.floor(interval) + "h";
            interval = seconds / 60;
            if (interval > 1) return Math.floor(interval) + "m";
            return Math.floor(seconds) + "s";
        }

        // Atualizar todos os campos de tempo no carregamento da página
        document.querySelectorAll('.comment-time, .post-meta span').forEach(el => {
            const rawTime = el.textContent.match(/há (\d+)(h|dias|meses|anos)/);
            if (rawTime) {
                // Isso é um placeholder, o ideal seria ter o timestamp real no HTML
                // Para este exemplo, manteremos a exibição PHP simplificada.
            }
        });


        // Lógica de Votação (AJAX)
        document.querySelectorAll('.vote-section button').forEach(button => {
            button.addEventListener('click', async function() {
                const postId = this.closest('.post-detail-card').dataset.postId;
                const voteType = this.classList.contains('upvote-btn') ? 'upvote' : 'downvote';
                const scoreElement = this.closest('.vote-section').querySelector('.score');
                const upvoteBtn = this.closest('.vote-section').querySelector('.upvote-btn');
                const downvoteBtn = this.closest('.vote-section').querySelector('.downvote-btn');

                try {
                    const response = await fetch('post.php?id=' + postId, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=${voteType}&post_id=${postId}`
                    });
                    const data = await response.json();

                    if (data.success) {
                        scoreElement.textContent = data.new_score;
                        // Atualiza as classes 'active' dos botões de voto
                        upvoteBtn.classList.remove('active');
                        downvoteBtn.classList.remove('active');
                        if (data.user_voto === 'upvote') {
                            upvoteBtn.classList.add('active');
                        } else if (data.user_voto === 'downvote') {
                            downvoteBtn.classList.add('active');
                        }
                    } else {
                        alert(data.message || 'Erro ao votar.');
                    }
                } catch (error) {
                    console.error('Erro de rede ou servidor:', error);
                    alert('Erro de conexão ao votar. Tente novamente.');
                }
            });
        });

        // Lógica para mostrar/esconder formulário de resposta
        document.querySelectorAll('.reply-button').forEach(button => {
            button.addEventListener('click', function() {
                const commentId = this.dataset.commentId;
                const replyFormContainer = document.getElementById(`reply-form-${commentId}`);

                // Esconde todos os outros formulários de resposta
                document.querySelectorAll('.reply-form-container').forEach(form => {
                    if (form.id !== `reply-form-${commentId}`) {
                        form.style.display = 'none';
                    }
                });

                // Alterna a visibilidade do formulário atual
                if (replyFormContainer) {
                    replyFormContainer.style.display = replyFormContainer.style.display === 'none' ? 'block' : 'none';
                    if (replyFormContainer.style.display === 'block') {
                        replyFormContainer.querySelector('textarea').focus();
                    }
                }
            });
        });

        document.querySelectorAll('.cancel-reply-button').forEach(button => {
            button.addEventListener('click', function() {
                this.closest('.reply-form-container').style.display = 'none';
            });
        });

    </script>
</body>
</html>