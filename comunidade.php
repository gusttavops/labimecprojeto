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
    error_log("Erro ao buscar dados do usuário em comunidade.php: " . $e->getMessage());
    header("Location: login.php");
    exit;
}

$comunidade_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$comunidade_id) {
    $_SESSION['message'] = ['type' => 'error', 'text' => "Comunidade não especificada."];
    header("Location: comunidades.php");
    exit;
}

$comunidade = null;
$comunidade_posts = [];
$is_member = false;
$message = null;

try {
    // Buscar detalhes da comunidade
    $stmt_comm = $pdo->prepare("SELECT id, nome, descricao, criador_id, data_criacao FROM comunidades WHERE id = ?");
    $stmt_comm->execute([$comunidade_id]);
    $comunidade = $stmt_comm->fetch(PDO::FETCH_ASSOC);

    if (!$comunidade) {
        $_SESSION['message'] = ['type' => 'error', 'text' => "Comunidade não encontrada."];
        header("Location: comunidades.php");
        exit;
    }

    // Verificar se o usuário é membro
    $stmt_is_member = $pdo->prepare("SELECT COUNT(*) FROM membros_comunidade WHERE usuario_id = ? AND comunidade_id = ?");
    $stmt_is_member->execute([$user_id, $comunidade_id]);
    $is_member = ($stmt_is_member->fetchColumn() > 0);

    // Contar membros
    $stmt_member_count = $pdo->prepare("SELECT COUNT(*) FROM membros_comunidade WHERE comunidade_id = ?");
    $stmt_member_count->execute([$comunidade_id]);
    $comunidade['total_membros'] = $stmt_member_count->fetchColumn();


    // Buscar posts da comunidade
    $stmt_posts = $pdo->prepare("
        SELECT
            cp.id,
            cp.titulo,
            cp.conteudo,
            cp.tipo_post,
            cp.link_url,
            cp.imagem_url,
            cp.data_postagem,
            u.nome AS autor_nome,
            u.foto_perfil AS autor_foto_perfil,
            (SELECT COUNT(*) FROM comunidade_comentarios WHERE post_id = cp.id) AS total_comentarios,
            (SELECT SUM(CASE WHEN tipo_voto = 'upvote' THEN 1 ELSE -1 END) FROM comunidade_votos WHERE post_id = cp.id) AS pontuacao_votos,
            (SELECT tipo_voto FROM comunidade_votos WHERE post_id = cp.id AND usuario_id = ?) AS user_voto
        FROM comunidade_posts cp
        JOIN usuarios u ON cp.usuario_id = u.id
        WHERE cp.comunidade_id = ?
        ORDER BY cp.data_postagem DESC
    ");
    $stmt_posts->execute([$user_id, $comunidade_id]);
    $comunidade_posts = $stmt_posts->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro ao carregar comunidade ou posts: " . $e->getMessage());
    $_SESSION['message'] = ['type' => 'error', 'text' => "Erro ao carregar comunidade. Tente novamente."];
    header("Location: comunidades.php");
    exit;
}

// --- Lógica para Participar/Sair da Comunidade ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'join_community' && !$is_member) {
        try {
            $stmt = $pdo->prepare("INSERT INTO membros_comunidade (usuario_id, comunidade_id, data_entrada) VALUES (?, ?, NOW())");
            $stmt->execute([$user_id, $comunidade_id]);
            $_SESSION['message'] = ['type' => 'success', 'text' => "Você entrou na comunidade r/" . htmlspecialchars($comunidade['nome']) . "!"];
        } catch (PDOException $e) {
            error_log("Erro ao entrar na comunidade: " . $e->getMessage());
            $_SESSION['message'] = ['type' => 'error', 'text' => "Erro ao entrar na comunidade. Tente novamente."];
        }
    } elseif ($_POST['action'] === 'leave_community' && $is_member) {
        try {
            $stmt = $pdo->prepare("DELETE FROM membros_comunidade WHERE usuario_id = ? AND comunidade_id = ?");
            $stmt->execute([$user_id, $comunidade_id]);
            $_SESSION['message'] = ['type' => 'success', 'text' => "Você saiu da comunidade r/" . htmlspecialchars($comunidade['nome']) . "."];
        } catch (PDOException $e) {
            error_log("Erro ao sair da comunidade: " . $e->getMessage());
            $_SESSION['message'] = ['type' => 'error', 'text' => "Erro ao sair da comunidade. Tente novamente."];
        }
    }
    header("Location: comunidade.php?id=" . $comunidade_id); // Redireciona para atualizar o estado
    exit;
}

// Captura e limpa a mensagem da sessão (para ações de entrar/sair)
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Lógica de Votação (similar a post.php, acionada por AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_vote']) && ($_POST['action_vote'] === 'upvote' || $_POST['action_vote'] === 'downvote')) {
    header('Content-Type: application/json'); // Responde com JSON
    $post_id_vote = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
    $vote_type = $_POST['action_vote'];

    if (!$post_id_vote || !$user_id) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos para votar.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Verificar voto existente do usuário para este post
        $stmt_check_vote = $pdo->prepare("SELECT id, tipo_voto FROM comunidade_votos WHERE usuario_id = ? AND post_id = ?");
        $stmt_check_vote->execute([$user_id, $post_id_vote]);
        $existing_vote = $stmt_check_vote->fetch(PDO::FETCH_ASSOC);

        // Buscar a pontuação atual do post
        $stmt_current_score = $pdo->prepare("SELECT SUM(CASE WHEN tipo_voto = 'upvote' THEN 1 ELSE -1 END) AS score FROM comunidade_votos WHERE post_id = ?");
        $stmt_current_score->execute([$post_id_vote]);
        $current_score = $stmt_current_score->fetchColumn() ?? 0;
        $new_score = $current_score;

        if ($existing_vote) {
            if ($existing_vote['tipo_voto'] === $vote_type) {
                // Remove o voto
                $stmt_delete = $pdo->prepare("DELETE FROM comunidade_votos WHERE id = ?");
                $stmt_delete->execute([$existing_vote['id']]);
                $new_score += ($vote_type === 'upvote' ? -1 : 1);
            } else {
                // Mudar o voto
                $stmt_update = $pdo->prepare("UPDATE comunidade_votos SET tipo_voto = ? WHERE id = ?");
                $stmt_update->execute([$vote_type, $existing_vote['id']]);
                $new_score += ($vote_type === 'upvote' ? 2 : -2);
            }
        } else {
            // Insere novo voto
            $stmt_insert_vote = $pdo->prepare("INSERT INTO comunidade_votos (usuario_id, post_id, tipo_voto) VALUES (?, ?, ?)");
            $stmt_insert_vote->execute([$user_id, $post_id_vote, $vote_type]);
            $new_score += ($vote_type === 'upvote' ? 1 : -1);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'new_score' => $new_score, 'user_voto' => ($existing_vote && $existing_vote['tipo_voto'] === $vote_type) ? null : $vote_type]);
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Erro ao processar voto em comunidade.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro interno ao votar.']);
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>r/<?php echo htmlspecialchars($comunidade['nome']); ?> - Sumidos PB</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Cores base (mesmas do comunidades.php e post.php) */
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
            --cor-header-reddit: #cee3f8;
        }

        /* Reset e Fontes */
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
            width: 100%;
            max-width: 960px; /* Largura similar ao layout de comunidades */
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }

        .main-content-area {
            flex-grow: 1;
            max-width: 670px; /* Largura do feed principal */
        }

        .sidebar-right-comm {
            width: 290px; /* Largura da sidebar da comunidade */
            flex-shrink: 0;
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

        /* Community Info Card */
        .community-info-card {
            background-color: var(--cor-card-fundo);
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 0 0 1px var(--cor-bordas-reddit), 0 2px 4px var(--cor-sombra);
            overflow: hidden;
        }
        .community-info-card .card-header {
            background-color: var(--cor-header-reddit);
            color: var(--cor-texto-escuro);
            padding: 12px 15px;
            font-size: 18px;
            font-weight: 600;
            border-bottom: 1px solid var(--cor-bordas-reddit);
        }
        .community-info-card .card-content {
            padding: 15px;
        }
        .community-info-card .card-content p {
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 10px;
            color: #555;
        }
        .community-info-card .card-content .members-count {
            font-size: 14px;
            font-weight: 500;
            color: #444;
            margin-bottom: 15px;
        }
        .community-info-card .card-content button {
            background-color: var(--cor-botoes-reddit);
            color: #fff;
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            width: 100%;
            transition: background-color 0.2s ease;
        }
        .community-info-card .card-content button:hover {
            background-color: var(--cor-botoes-hover-reddit);
        }
        .community-info-card .card-content button.leave {
            background-color: #dc3545; /* Cor de sair/vermelho */
        }
        .community-info-card .card-content button.leave:hover {
            background-color: #c82333;
        }


        /* Post Card (central feed) - Mesmos estilos de comunidades.php */
        .post-card {
            background-color: var(--cor-card-fundo);
            border-radius: 8px;
            margin-bottom: 10px;
            box-shadow: 0 0 0 1px var(--cor-bordas-reddit), 0 2px 4px var(--cor-sombra);
            display: flex;
            overflow: hidden;
        }
        .post-card:hover {
            border: 1px solid var(--cor-link-reddit);
            box-shadow: 0 0 0 1px var(--cor-link-reddit), 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .post-card .vote-section {
            background-color: #f8f9fa;
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
        .post-card .vote-section button.upvote:hover { color: var(--cor-upvote); }
        .post-card .vote-section button.downvote:hover { color: var(--cor-downvote); }
        .post-card .vote-section button.active.upvote { color: var(--cor-upvote); }
        .post-card .vote-section button.active.downvote { color: var(--cor-downvote); }

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
            display: block;
        }
        .post-card .post-actions {
            display: flex;
            gap: 10px;
            margin-top: auto;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="main-content-area">
            <a href="comunidades.php" class="back-button"><i class="fas fa-arrow-left"></i> Voltar para Comunidades</a>

            <?php if ($message): ?>
                <div class="message <?php echo htmlspecialchars($message['type']); ?>">
                    <i class="fas fa-<?php echo ($message['type'] === 'success' ? 'check-circle' : ($message['type'] === 'info' ? 'info-circle' : 'exclamation-circle')); ?>"></i>
                    <?php echo htmlspecialchars($message['text']); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($comunidade_posts)): ?>
                <?php foreach ($comunidade_posts as $post): ?>
                    <div class="post-card" data-post-id="<?php echo htmlspecialchars($post['id']); ?>">
                        <div class="vote-section">
                            <button class="upvote-btn <?php echo ($post['user_voto'] === 'upvote' ? 'active' : ''); ?>"><i class="fas fa-arrow-up"></i></button>
                            <span class="score"><?php echo (isset($post['pontuacao_votos']) ? htmlspecialchars($post['pontuacao_votos']) : 0); ?></span>
                            <button class="downvote-btn <?php echo ($post['user_voto'] === 'downvote' ? 'active' : ''); ?>"><i class="fas fa-arrow-down"></i></button>
                        </div>
                        <div class="post-content">
                            <div class="post-meta">
                                <img src="<?php echo htmlspecialchars($post['autor_foto_perfil'] ?? 'https://lh3.googleusercontent.com/a/ACg8ocJxiK2NM9FwrnfbB3aJ7rN5hJ0l6qATGGQVIaqwjakwPzSnS8Yc=s288-c-no'); ?>" alt="Autor">
                                Postado por <a href="#"><?php echo htmlspecialchars($post['autor_nome']); ?></a> em <a href="comunidade.php?id=<?php echo htmlspecialchars($comunidade['id']); ?>">r/<?php echo htmlspecialchars($comunidade['nome']); ?></a>
                                <span>há <?php echo floor((time() - strtotime($post['data_postagem'])) / 3600); ?>h</span>
                            </div>
                            <h3><a href="post.php?id=<?php echo htmlspecialchars($post['id']); ?>"><?php echo htmlspecialchars($post['titulo']); ?></a></h3>
                            <?php if ($post['tipo_post'] === 'texto'): ?>
                                <p class="post-body"><?php echo nl2br(htmlspecialchars($post['conteudo'])); ?></p>
                            <?php elseif ($post['tipo_post'] === 'imagem' && !empty($post['imagem_url'])): ?>
                                <img src="<?php echo htmlspecialchars($post['imagem_url']); ?>" alt="Imagem da Publicação" class="post-image">
                            <?php elseif ($post['tipo_post'] === 'link' && !empty($post['link_url'])): ?>
                                <a href="<?php echo htmlspecialchars($post['link_url']); ?>" target="_blank" class="post-link"><?php echo htmlspecialchars($post['link_url']); ?></a>
                            <?php endif; ?>

                            <div class="post-actions">
                                <button class="comments" onclick="window.location.href='post.php?id=<?php echo htmlspecialchars($post['id']); ?>'"><i class="fas fa-comment-alt"></i> <?php echo (isset($post['total_comentarios']) ? htmlspecialchars($post['total_comentarios']) : 0); ?> Comentários</button>
                                <button><i class="fas fa-share"></i> Compartilhar</button>
                                <button><i class="fas fa-bookmark"></i> Salvar</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #666; margin-top: 50px;">Nenhuma publicação encontrada nesta comunidade ainda. Seja o primeiro a criar uma!</p>
            <?php endif; ?>
        </div>

        <div class="sidebar-right-comm">
            <div class="community-info-card">
                <div class="card-header">Sobre r/<?php echo htmlspecialchars($comunidade['nome']); ?></div>
                <div class="card-content">
                    <p><?php echo nl2br(htmlspecialchars($comunidade['descricao'])); ?></p>
                    <div class="members-count">
                        <i class="fas fa-users"></i> Membros: <?php echo htmlspecialchars($comunidade['total_membros']); ?>
                    </div>
                    <form action="comunidade.php?id=<?php echo htmlspecialchars($comunidade['id']); ?>" method="POST">
                        <?php if ($is_member): ?>
                            <button type="submit" name="action" value="leave_community" class="leave">Sair da Comunidade</button>
                        <?php else: ?>
                            <button type="submit" name="action" value="join_community">Entrar na Comunidade</button>
                        <?php endif; ?>
                    </form>
                    <button onclick="window.location.href='criar_publicacao.php?comunidade_id=<?php echo htmlspecialchars($comunidade['id']); ?>'" style="margin-top: 15px; background-color: var(--cor-secundaria);">Criar Publicação</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Lógica de Votação (AJAX) - Mesma lógica de post.php
        document.querySelectorAll('.vote-section button').forEach(button => {
            button.addEventListener('click', async function() {
                const postId = this.closest('.post-card').dataset.postId;
                const voteType = this.classList.contains('upvote-btn') ? 'upvote' : 'downvote';
                const scoreElement = this.closest('.vote-section').querySelector('.score');
                const upvoteBtn = this.closest('.vote-section').querySelector('.upvote-btn');
                const downvoteBtn = this.closest('.vote-section').querySelector('.downvote-btn');

                try {
                    const response = await fetch('comunidade.php?id=<?php echo htmlspecialchars($comunidade['id']); ?>', { // Envia para a própria página
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action_vote=${voteType}&post_id=${postId}` // Nome do action_vote para não conflitar com join/leave
                    });
                    const data = await response.json();

                    if (data.success) {
                        scoreElement.textContent = data.new_score;
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
    </script>
</body>
</html>