<?php
// ATENÇÃO: As 3 linhas abaixo são para DEBUG APENAS! Remova ou comente-as em ambiente de produção.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Verifica se o usuário está logado, caso contrário, redireciona para a página de login.
if (!isset($_SESSION['usuario']) || !isset($_SESSION['nome'])) {
    header("Location: login.php");
    exit;
}

$nome_usuario_logado = $_SESSION['nome'];

// Inclui o arquivo de conexão com o banco de dados.
require_once __DIR__ . '/includes/db.php';

$user_id = null;
$foto_perfil = 'https://lh3.googleusercontent.com/a/ACg8ocJxiK2NM9FwrnfbB3aJ7rN5hJ0l6qATGGQVIaqwjakwPzSnS8Yc=s288-c-no'; // URL padrão para foto de perfil

try {
    // Busca o ID do usuário e a foto de perfil no banco de dados.
    $stmt = $pdo->prepare("SELECT id, foto_perfil FROM usuarios WHERE usuario = ?");
    $stmt->execute([$_SESSION['usuario']]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_data) {
        $user_id = $user_data['id'];
        if (!empty($user_data['foto_perfil'])) {
            $foto_perfil = $user_data['foto_perfil'];
        }
    } else {
        // Se o usuário não for encontrado no banco de dados (erro na sessão ou usuário removido), redireciona para login.
        header("Location: login.php");
        exit;
    }
} catch (PDOException $e) {
    // Registra o erro no log do servidor.
    error_log("Erro ao buscar dados do usuário em criar_publicacao.php: " . $e->getMessage());
    // Em caso de erro grave ao buscar dados do usuário, redireciona para login.
    header("Location: login.php");
    exit;
}

// --- Lógica para criar a publicação ---
$message = null; // Variável para armazenar mensagens de sucesso ou erro para o usuário.

// Processa o formulário apenas se for uma requisição POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Valida e obtém o ID da comunidade, garantindo que seja um inteiro.
    $comunidade_id = filter_input(INPUT_POST, 'comunidade_id', FILTER_VALIDATE_INT);

    // Obtém o título, conteúdo e tipo de post. Usamos trim() para remover espaços extras.
    // FILTER_SANITIZE_STRING foi removido pois está deprecated. A sanitização para exibição deve ser feita com htmlspecialchars().
    $titulo = trim($_POST['titulo'] ?? '');
    $conteudo = trim($_POST['conteudo'] ?? '');
    $tipo_post = trim($_POST['tipo_post'] ?? '');

    // Valida e obtém a URL do link, se o tipo de post for 'link'.
    $link_url = filter_input(INPUT_POST, 'link_url', FILTER_VALIDATE_URL);
    $imagem_url = null; // Inicializa a URL da imagem como nula.

    // Validação básica dos campos obrigatórios.
    if (!$comunidade_id || empty($titulo) || (empty($conteudo) && $tipo_post == 'texto')) {
        $message = ['type' => 'error', 'text' => "Por favor, preencha todos os campos obrigatórios."];
    } else {
        try {
            // Verifica se a comunidade selecionada realmente existe no banco de dados.
            $stmt_comm = $pdo->prepare("SELECT COUNT(*) FROM comunidades WHERE id = ?");
            $stmt_comm->execute([$comunidade_id]);
            if ($stmt_comm->fetchColumn() == 0) {
                $message = ['type' => 'error', 'text' => "A comunidade selecionada não existe."];
            } else {
                // Lógica para lidar com o upload de imagem.
                if ($tipo_post === 'imagem' && isset($_FILES['imagem_upload']) && $_FILES['imagem_upload']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['imagem_upload']['name'], PATHINFO_EXTENSION));
                    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif']; // Extensões de imagem permitidas.
                    if (in_array($ext, $allowed_ext)) {
                        // Gera um nome único para a imagem para evitar conflitos.
                        $novo_nome_imagem = 'post_img_' . $user_id . '_' . time() . '.' . $ext;
                        $destino_imagem = 'uploads/' . $novo_nome_imagem; // Caminho completo onde a imagem será salva.
                        // Tenta mover o arquivo temporário para o destino final.
                        if (move_uploaded_file($_FILES['imagem_upload']['tmp_name'], $destino_imagem)) {
                            $imagem_url = $destino_imagem; // Define a URL da imagem para salvar no BD.
                        } else {
                            $message = ['type' => 'error', 'text' => "Erro ao mover a imagem para o destino. Verifique as permissões da pasta 'uploads/'."];
                        }
                    } else {
                        $message = ['type' => 'error', 'text' => "Formato de imagem não permitido. Use JPG, JPEG, PNG ou GIF."];
                    }
                } elseif ($tipo_post === 'link' && !filter_var($link_url, FILTER_VALIDATE_URL)) {
                    // Valida a URL se o tipo de post for 'link'.
                    $message = ['type' => 'error', 'text' => "Por favor, insira uma URL válida para o link."];
                    $link_url = null; // Garante que uma URL inválida não seja inserida.
                }

                // Se não houve mensagens de erro (upload/link ok e campos básicos ok), prossegue com a inserção no banco.
                if (!$message) {
                    // Prepara a query SQL para inserir a nova publicação.
                    $stmt = $pdo->prepare("INSERT INTO comunidade_posts (comunidade_id, usuario_id, titulo, conteudo, tipo_post, link_url, imagem_url, data_postagem) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                    // Executa a query com os valores fornecidos, evitando SQL Injection.
                    $stmt->execute([$comunidade_id, $user_id, $titulo, $conteudo, $tipo_post, $link_url, $imagem_url]);

                    // Define uma mensagem de sucesso na sessão para ser exibida na próxima página.
                    $_SESSION['message'] = ['type' => 'success', 'text' => "Publicação criada com sucesso!"];
                    // Redireciona para a página de comunidades após o sucesso.
                    header("Location: comunidades.php");
                    exit;
                }
            }
        } catch (PDOException $e) {
            // --- BLOCO DE DEPURACÃO DO BANCO DE DADOS (APENAS PARA DESENVOLVIMENTO) ---
            // Este bloco irá mostrar o erro exato do banco de dados na tela.
            // COMENTE OU REMOVA ESTAS LINHAS EM PRODUÇÃO!
            echo "<h1>Erro Crítico ao Criar Publicação (DEBUG):</h1>";
            echo "<p><strong>Mensagem do Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>Código do Erro:</strong> " . htmlspecialchars($e->getCode()) . "</p>";
            echo "<p><strong>Arquivo:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
            echo "<p><strong>Linha:</strong> " . htmlspecialchars($e->getLine()) . "</p>";
            if (isset($e->errorInfo)) {
                echo "<p><strong>Detalhes do SQLSTATE:</strong> ";
                var_dump($e->errorInfo);
                echo "</p>";
            }
            die("Execução interrompida para depuração. Verifique os detalhes acima para corrigir o erro no banco de dados.");
            // --- FIM DO BLOCO DE DEPURACÃO ---

            // A linha abaixo é a mensagem genérica para o usuário final em produção.
            // error_log("Erro ao criar publicação: " . $e->getMessage()); // Para logar o erro em produção
            // $message = ['type' => 'error', 'text' => "Erro ao criar publicação. Tente novamente."];
        }
    }
}

// Buscar lista de comunidades para preencher o campo 'select' no formulário.
$comunidades = [];
try {
    $stmt = $pdo->query("SELECT id, nome FROM comunidades ORDER BY nome ASC");
    $comunidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar comunidades: " . $e->getMessage());
    // Em caso de erro, a lista de comunidades ficará vazia.
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Publicação - Sumidos PB</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Variáveis CSS para cores e estilos */
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
            --cor-sucesso-bg: #d4edda;
            --cor-sucesso-text: #155724;
            --cor-erro-bg: #f8d7da;
            --cor-erro-text: #721c24;
        }

        /* Reset de estilos básicos */
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
            align-items: flex-start; /* Alinha no topo */
            min-height: 100vh;
            padding: 40px 20px;
        }

        /* Estilo do container principal */
        .container {
            background-color: var(--cor-card-fundo);
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 0 0 1px var(--cor-bordas-reddit), 0 4px 12px var(--cor-sombra);
            width: 100%;
            max-width: 700px;
        }

        h1 {
            font-size: 24px;
            color: var(--cor-texto-escuro);
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
        }

        /* Estilo dos grupos de formulário */
        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
            font-size: 15px;
        }

        /* Estilo dos campos de input e textarea */
        input[type="text"],
        textarea,
        select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--cor-borda-clara);
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            background-color: #fcfcfc;
        }

        /* Estilo de foco para campos de input e textarea */
        input[type="text"]:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--cor-link-reddit);
            box-shadow: 0 0 0 1px var(--cor-link-reddit);
        }

        textarea {
            min-height: 120px;
            resize: vertical; /* Permite redimensionar apenas verticalmente */
        }

        /* Estilo para o seletor de tipo de post (botões) */
        .post-type-selector {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--cor-bordas-reddit);
            padding-bottom: 15px;
        }

        .post-type-selector button {
            flex-grow: 1; /* Faz os botões ocuparem espaço igual */
            padding: 12px 15px;
            border: 1px solid var(--cor-borda-clara);
            border-radius: 6px;
            background-color: var(--cor-card-fundo);
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            color: #555;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        /* Estilo para o botão de tipo de post ativo */
        .post-type-selector button.active {
            border-color: var(--cor-link-reddit);
            color: var(--cor-link-reddit);
            background-color: #e6f0fa;
            box-shadow: 0 0 0 1px var(--cor-link-reddit);
        }

        /* Estilo de hover para os botões de tipo de post */
        .post-type-selector button:hover:not(.active) {
            border-color: #b0b8bc;
            background-color: #f0f2f4;
        }

        /* Estilo para o conteúdo de cada tipo de post (escondido/mostrado com JS) */
        .post-type-content {
            margin-top: 15px;
        }
        .post-type-content div {
            display: none; /* Esconde todos por padrão */
        }
        .post-type-content div.active {
            display: block; /* Mostra o ativo */
        }

        /* Estilo para o input de arquivo (upload de imagem) */
        input[type="file"] {
            padding: 10px;
            background-color: #f0f2f4;
            border-radius: 6px;
        }
        input[type="file"]::file-selector-button {
            background-color: var(--cor-botoes-secundarios-reddit); /* Esta variável pode não existir, talvez defina uma cor direto */
            color: #555;
            border: 1px solid var(--cor-borda-clara);
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        input[type="file"]::file-selector-button:hover {
            background-color: #d0d4d9; /* Cor de hover para o botão de seleção de arquivo */
        }

        /* Estilo do botão de submit */
        button[type="submit"] {
            background-color: var(--cor-botoes-reddit);
            color: #fff;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 17px;
            font-weight: 600;
            transition: background-color 0.2s ease, transform 0.2s ease;
            width: 100%;
            margin-top: 20px;
        }

        /* Estilo de hover para o botão de submit */
        button[type="submit"]:hover {
            background-color: var(--cor-botoes-hover-reddit);
            transform: translateY(-1px);
        }

        /* Estilos das mensagens (sucesso, erro, info) */
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
            box-sizing: border-box; /* Garante que padding não estoure a largura */
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

        /* Estilo do botão "Voltar para Comunidades" */
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
    </style>
</head>
<body>
    <div class="container">
        <a href="comunidades.php" class="back-button"><i class="fas fa-arrow-left"></i> Voltar para Comunidades</a>
        <h1>Criar Nova Publicação</h1>

        <?php if ($message): // Exibe mensagens de sucesso ou erro ?>
            <div class="message <?php echo htmlspecialchars($message['type']); ?>">
                <i class="fas fa-<?php echo ($message['type'] === 'success' ? 'check-circle' : ($message['type'] === 'info' ? 'info-circle' : 'exclamation-circle')); ?>"></i>
                <?php echo htmlspecialchars($message['text']); ?>
            </div>
        <?php endif; ?>

        <form action="criar_publicacao.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="comunidade_id">Publicar em:</label>
                <select id="comunidade_id" name="comunidade_id" required>
                    <option value="">Selecione uma comunidade</option>
                    <?php foreach ($comunidades as $comm): ?>
                        <option value="<?php echo htmlspecialchars($comm['id']); ?>"><?php echo htmlspecialchars($comm['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="titulo">Título:</label>
                <input type="text" id="titulo" name="titulo" placeholder="Digite o título da sua publicação" required maxlength="255">
            </div>

            <div class="post-type-selector">
                <button type="button" class="active" data-type="texto"><i class="fas fa-paragraph"></i> Post de Texto</button>
                <button type="button" data-type="imagem"><i class="fas fa-image"></i> Imagem</button>
                <button type="button" data-type="link"><i class="fas fa-link"></i> Link</button>
            </div>

            <input type="hidden" name="tipo_post" id="tipo_post" value="texto">

            <div class="post-type-content">
                <div id="content-texto" class="active form-group">
                    <label for="conteudo">Conteúdo:</label>
                    <textarea id="conteudo" name="conteudo" placeholder="Escreva o conteúdo da sua publicação aqui..."></textarea>
                </div>

                <div id="content-imagem" class="form-group">
                    <label for="imagem_upload">Upload de Imagem:</label>
                    <input type="file" id="imagem_upload" name="imagem_upload" accept="image/*">
                    <small style="color: #666; display: block; margin-top: 5px;">Formatos aceitos: JPG, JPEG, PNG, GIF</small>
                </div>

                <div id="content-link" class="form-group">
                    <label for="link_url">URL do Link:</label>
                    <input type="text" id="link_url" name="link_url" placeholder="https://exemplo.com/seu-link">
                </div>
            </div>

            <button type="submit">Criar Publicação</button>
        </form>
    </div>

    <script>
        // Lógica JavaScript para alternar entre os tipos de post (Texto, Imagem, Link)
        document.querySelectorAll('.post-type-selector button').forEach(button => {
            button.addEventListener('click', function() {
                // Remove 'active' de todos os botões e conteúdos
                document.querySelectorAll('.post-type-selector button').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.post-type-content > div').forEach(div => div.classList.remove('active'));

                // Adiciona 'active' ao botão clicado
                this.classList.add('active');

                // Pega o tipo de post do atributo data-type do botão
                const type = this.dataset.type;

                // Define o valor do input hidden 'tipo_post' (usado pelo PHP)
                document.getElementById('tipo_post').value = type;

                // Mostra o conteúdo correspondente ao tipo de post selecionado
                document.getElementById(`content-${type}`).classList.add('active');

                // Ajusta o atributo 'required' (obrigatório) para os campos de entrada de cada tipo
                document.getElementById('conteudo').required = (type === 'texto');
                document.getElementById('link_url').required = (type === 'link');
                document.getElementById('imagem_upload').required = (type === 'imagem');

                // Limpa os campos quando muda o tipo de post para evitar envio de dados de outros tipos
                if (type !== 'texto') document.getElementById('conteudo').value = '';
                if (type !== 'link') document.getElementById('link_url').value = '';
                if (type !== 'imagem') document.getElementById('imagem_upload').value = '';
            });
        });

        // Inicializa o estado 'required' no carregamento da página (conteúdo de texto é obrigatório por padrão)
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('conteudo').required = true;
            document.getElementById('link_url').required = false;
            document.getElementById('imagem_upload').required = false;
        });
    </script>
</body>
</html>