<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario']) || !isset($_SESSION['nome'])) {
    header("Location: login.php");
    exit;
}

$nome = $_SESSION['nome'];

// Incluir a conexão com o banco de dados
require_once __DIR__ . '/includes/db.php'; // Certifique-se de que este caminho está correto

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
        $_SESSION['user_id'] = $user_id; // Armazenar user_id na sessão para uso posterior
        if (!empty($user_data['foto_perfil'])) {
            $foto_perfil = $user_data['foto_perfil'];
        }
    } else {
        header("Location: login.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar dados do usuário em mensagens.php: " . $e->getMessage());
    header("Location: login.php");
    exit;
}

// Determinar qual caixa exibir inicialmente (padrão: entrada)
$caixa = isset($_GET['caixa']) && $_GET['caixa'] === 'enviadas' ? 'enviadas' : 'entrada';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Mensagens - Sumidos PB</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        /* Shared CSS from your other pages */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Arial", sans-serif; display: flex; height: 100vh; color: #333; }

        /* Sidebar styles (copied directly from your configuracoes.php) */
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

        /* Main content styles for messages */
        .main-messages {
            margin-left: 251px; /* Offset for the sidebar */
            width: calc(100% - 251px);
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        .messages-header {
            background-color: #004d00; color: #fff; padding: 15px 25px; font-size: 20px; font-weight: 600;
            position: sticky; top: 0; z-index: 100;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .messages-container {
            display: flex;
            flex: 1; /* Occupies remaining vertical space */
            height: calc(100vh - 60px); /* Subtract header height */
            background-color: #f5f5f5;
        }

        /* Left Panel: Mailbox Navigation */
        .mailbox-nav {
            width: 200px; /* Adjust as needed */
            background: #fff;
            border-right: 1px solid #ddd;
            overflow-y: auto;
            padding: 10px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
        }
        .mailbox-nav button {
            width: 90%;
            padding: 10px 15px;
            margin: 5px auto;
            background-color: #006400;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: background-color 0.2s;
        }
        .mailbox-nav button:hover {
            background-color: #004d00;
        }
        .mailbox-nav .nav-link {
            display: block;
            padding: 10px 15px;
            margin: 5px 0;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .mailbox-nav .nav-link:hover {
            background-color: #e0ffe0;
        }
        .mailbox-nav .nav-link.active {
            background-color: #c0f4c0;
            font-weight: bold;
            color: #003300;
        }
        .mailbox-nav .unread-count-badge {
            margin-left: auto;
            background-color: red;
            color: white;
            padding: 2px 7px;
            border-radius: 10px;
            font-size: 12px;
            min-width: 25px;
            text-align: center;
        }

        /* Right Panel: Message List / Message View / Compose Form */
        .message-content-area {
            flex: 1; /* Takes up remaining space */
            display: flex;
            flex-direction: column;
        }

        /* Message List View */
        .message-list {
            flex: 1; /* Messages take up most of the space */
            overflow-y: auto;
            background-color: #fff;
            border-left: 1px solid #eee;
        }
        .message-list-item {
            display: flex;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
            align-items: center;
        }
        .message-list-item:hover {
            background-color: #f0f0f0;
        }
        .message-list-item.unread {
            background-color: #f8fff8; /* Lightest green for unread */
            font-weight: bold;
        }
        .message-list-item .sender-recipient {
            flex-shrink: 0;
            width: 150px; /* Fixed width for sender/recipient name */
            font-size: 15px;
            color: #003300;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .message-list-item .subject-snippet {
            flex-grow: 1;
            margin: 0 10px;
            font-size: 15px;
            color: #333;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .message-list-item.unread .subject-snippet {
            color: #000;
        }
        .message-list-item .message-date {
            flex-shrink: 0;
            width: 80px; /* Fixed width for date */
            text-align: right;
            font-size: 13px;
            color: #999;
        }

        /* Message Detail View */
        .message-detail {
            flex: 1;
            overflow-y: auto;
            background-color: #fff;
            padding: 20px;
            border-left: 1px solid #eee;
            display: none; /* Hidden by default */
            flex-direction: column;
        }
        .message-detail .detail-header {
            margin-bottom: 20px;
        }
        .message-detail .detail-header h3 {
            font-size: 22px;
            color: #003300;
            margin-bottom: 10px;
        }
        .message-detail .detail-meta {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }
        .message-detail .detail-body {
            flex-grow: 1;
            line-height: 1.6;
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #eee;
            background-color: #f9f9f9;
            border-radius: 8px;
            white-space: pre-wrap; /* Preserve whitespace and breaks */
            word-wrap: break-word;
        }
        .message-detail .detail-actions {
            display: flex;
            gap: 10px;
        }
        .message-detail .detail-actions button {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .message-detail .detail-actions .btn-reply {
            background-color: #006400;
            color: #fff;
        }
        .message-detail .detail-actions .btn-reply:hover {
            background-color: #004d00;
        }
        .message-detail .detail-actions .btn-delete {
            background-color: #e0e0e0;
            color: #333;
        }
        .message-detail .detail-actions .btn-delete:hover {
            background-color: #ccc;
        }

        /* Compose Message Form */
        .compose-message {
            flex: 1;
            background-color: #fff;
            padding: 20px;
            border-left: 1px solid #eee;
            display: none; /* Hidden by default */
            flex-direction: column;
        }
        .compose-message h3 {
            font-size: 22px;
            color: #003300;
            margin-bottom: 20px;
        }
        .compose-message label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        .compose-message input[type="text"],
        .compose-message textarea,
        .compose-message select {
            width: 100%;
            padding: 10px 12px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 15px;
            box-sizing: border-box; /* Include padding in width */
        }
        .compose-message textarea {
            min-height: 150px;
            resize: vertical;
        }
        .compose-message .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .compose-message .form-actions button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        .compose-message .form-actions .btn-send {
            background-color: #006400;
            color: #fff;
        }
        .compose-message .form-actions .btn-send:hover {
            background-color: #004d00;
        }
        .compose-message .form-actions .btn-cancel {
            background-color: #e0e0e0;
            color: #333;
        }
        .compose-message .form-actions .btn-cancel:hover {
            background-color: #ccc;
        }

        /* Modal for photo (keep for consistency) */
        .modal-foto-bg {
            display: none; position: fixed; z-index: 99999; left: 0; top: 0; width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.5); align-items: center; justify-content: center;
        }
        .modal-foto {
            background: #fff; padding: 25px 30px 18px 30px; border-radius: 15px; text-align: center;
            position: relative; min-width: 270px;
        }
        .modal-foto .close-modal {
            position: absolute; right: 18px; top: 12px; font-size: 22px; color: #333; cursor: pointer;
        }
        .modal-foto label { font-weight: bold; display: block; margin-bottom: 12px; }
        .modal-foto input[type="file"] { margin-bottom: 16px; }
        .modal-foto button[type="submit"] {
            background-color: #006400; color: #fff; border: none; padding: 8px 20px;
            border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 15px;
        }
    
    
    /* Estilo para o contador na sidebar */
.sidebar-badge {
    margin-left: 10px; /* Espaçamento entre o texto e o badge */
    background-color: red;
    color: white;
    padding: 2px 7px;
    border-radius: 10px;
    font-size: 12px;
    min-width: 25px; /* Garante que o badge tenha largura mínima */
    text-align: center;
    font-weight: bold;
    display: inline-block; /* Para garantir que o padding e a largura funcionem */
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
       
       <button onclick="window.location.href='mensagens.php'" id="sidebarMensagensButton">
            <i class="fas fa-envelope"></i> Mensagens
            <span class="sidebar-badge" id="sidebarUnreadCount" style="display:none;"></span>
        </button>
        
        <button><i class="fas fa-user-friends"></i> Contatos</button>
        <button onclick="window.location.href='lista_desaparecidos.php'">
            <i class="fas fa-list"></i> Lista de Desaparecidos
        </button>
        <button onclick="window.location.href='registrar_desaparecido.php'">
            <i class="fas fa-user-plus"></i> Registrar Desaparecimento
        </button>
        <button><i class="fa-solid fa-circle-user"></i> Comunidade</button>

        <button onclick="window.location.href='dadosferramentas.php'">
            <i class="fas fa-database"></i> Dados e Ferramentas</button>

        <button onclick="window.location.href='configuracoes.php'">
            <i class="fas fa-cogs"></i> Configurações</button>

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

<div class="main-messages">
    <div class="messages-header">Minhas Mensagens</div>

    <div class="messages-container">
        <div class="mailbox-nav">
            <button id="btnCompose"><i class="fas fa-plus"></i> Nova Mensagem</button>
            <a href="#" class="nav-link" id="navInbox" data-box="entrada">
                <i class="fas fa-inbox"></i> Caixa de Entrada
                <span class="unread-count-badge" id="inboxUnreadCount" style="display:none;"></span>
            </a>
            <a href="#" class="nav-link" id="navSent" data-box="enviadas">
                <i class="fas fa-paper-plane"></i> Mensagens Enviadas
            </a>
            </div>

        <div class="message-content-area">
            <div class="message-list" id="messageList">
                <p style="padding: 20px; text-align: center; color: #666;">Carregando mensagens...</p>
            </div>

            <div class="message-detail" id="messageDetail">
                 <button class="btn-secondary btn-sm mb-3" id="btnVoltarListaDetalhes" style="padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; display: flex; align-items: center; gap: 5px; background-color: #e0e0e0; color: #333; margin-bottom: 20px;">
        <i class="fas fa-arrow-left"></i> Voltar
    </button>
                <div class="detail-header">
                    <h3 id="detailSubject">Assunto da Mensagem</h3>
                    <div class="detail-meta">
                        De: <span id="detailSender"></span><br>
                        Para: Você<br>
                        Em: <span id="detailDate"></span>
                    </div>
                </div>
                <div class="detail-body" id="detailBody">
                    Conteúdo da mensagem aqui.
                </div>
                <div class="detail-actions">
                    <button class="btn-reply" id="btnReply"><i class="fas fa-reply"></i> Responder</button>
                    <button class="btn-delete" id="btnDeleteMessage"><i class="fas fa-trash-alt"></i> Apagar</button>
                </div>
            </div>

            <div class="compose-message" id="composeMessage">
                <h3>Nova Mensagem</h3>
                <form id="composeForm">
                    <label for="composeRecipient">Para:</label>
                    <select id="composeRecipient" required>
                        <option value="">Selecione um destinatário</option>
                        </select>

                    <label for="composeSubject">Assunto:</label>
                    <input type="text" id="composeSubject" required>

                    <label for="composeBody">Mensagem:</label>
                    <textarea id="composeBody" rows="10" required></textarea>

                    <div class="form-actions">
                        <button type="submit" class="btn-send"><i class="fas fa-paper-plane"></i> Enviar</button>
                        <button type="button" class="btn-cancel" id="btnCancelCompose"><i class="fas fa-times"></i> Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal-foto-bg" id="modalAlterarFoto">
    <div class="modal-foto">
        <span class="close-modal" id="closeModal">×</span>
        <form method="post" enctype="multipart/form-data" action="configuracoes.php">
            <label for="inputFoto">Escolher nova foto:</label>
            <input type="file" name="nova_foto_modal" id="inputFoto" accept="image/*" required>
            <button type="submit">Salvar Foto</button>
        </form>
    </div>
</div>

<script>
    
    // ... suas declarações de variáveis existentes ...

// NOVO: Adicione as referências aos novos botões "Voltar"
const btnVoltarListaDetalhes = document.getElementById('btnVoltarListaDetalhes');
const btnVoltarListaComposicao = document.getElementById('btnVoltarListaComposicao'); // Já existia, mas aqui se refere ao novo elemento

// ... suas funções existentes como showPanel(), loadMessages(), loadMessageDetail(), etc. ...

// NOVO: Ouvinte de evento para o botão "Voltar" na visualização de detalhes da mensagem
if (btnVoltarListaDetalhes) { // Verificação para garantir que o elemento existe
    btnVoltarListaDetalhes.addEventListener('click', function() {
        loadMessages(currentBox); // Volta para a lista atual (caixa de entrada ou enviadas)
    });
}

// NOVO: Ouvinte de evento para o botão "Voltar" na tela de composição de mensagem
// (O seu botão "Cancelar" (btnCancelCompose) já faz isso, então podemos usar o mesmo comportamento para o novo botão "Voltar" se desejar, ou apenas adicionar um novo listener.)
// Se btnCancelCompose já funciona como "Voltar", você pode simplesmente mapear o novo botão para a mesma função.
if (btnVoltarListaComposicao) { // Verificação para garantir que o elemento existe
    btnVoltarListaComposicao.addEventListener('click', function() {
        loadMessages(currentBox); // Volta para a lista atual (caixa de entrada ou enviadas)
    });
}

// O seu btnCancelCompose já possui este ouvinte e funciona como "Voltar" para a lista, então ele continua como está:
// btnCancelCompose.addEventListener('click', function() {
//     loadMessages(currentBox); // Volta para a caixa de mensagens visualizada por último
// });

// ... o restante do seu JavaScript
    
    // JavaScript for modal (from your configuracoes.php)
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

    // --- New JavaScript for Webmail Functionality ---
    const messageListDiv = document.getElementById('messageList');
    const messageDetailDiv = document.getElementById('messageDetail');
    const composeMessageDiv = document.getElementById('composeMessage');
    const btnCompose = document.getElementById('btnCompose');
    const navInbox = document.getElementById('navInbox');
    const navSent = document.getElementById('navSent');
    const inboxUnreadCount = document.getElementById('inboxUnreadCount');

    const detailSubject = document.getElementById('detailSubject');
    const detailSender = document.getElementById('detailSender');
    const detailDate = document.getElementById('detailDate');
    const detailBody = document.getElementById('detailBody');
    const btnReply = document.getElementById('btnReply');
    const btnDeleteMessage = document.getElementById('btnDeleteMessage');

    const composeForm = document.getElementById('composeForm');
    const composeRecipient = document.getElementById('composeRecipient');
    const composeSubject = document.getElementById('composeSubject');
    const composeBody = document.getElementById('composeBody');
    const btnCancelCompose = document.getElementById('btnCancelCompose');

    let currentBox = 'entrada'; // Default to inbox

    // Function to show a specific panel and hide others
    function showPanel(panelId) {
        messageListDiv.style.display = 'none';
        messageDetailDiv.style.display = 'none';
        composeMessageDiv.style.display = 'none';

        if (panelId === 'messageList') {
            messageListDiv.style.display = 'block';
        } else if (panelId === 'messageDetail') {
            messageDetailDiv.style.display = 'flex'; // Use flex for column layout
        } else if (panelId === 'composeMessage') {
            composeMessageDiv.style.display = 'flex'; // Use flex for column layout
        }
    }

    // Function to update active navigation link
    function updateNavLinkActive(box) {
        document.querySelectorAll('.mailbox-nav .nav-link').forEach(link => {
            link.classList.remove('active');
        });
        if (box === 'entrada') {
            navInbox.classList.add('active');
        } else if (box === 'enviadas') {
            navSent.classList.add('active');
        }
    }

    // Load messages for a given box (inbox/sent)
    function loadMessages(box = 'entrada') {
        currentBox = box;
        updateNavLinkActive(box);
        showPanel('messageList');
        messageListDiv.innerHTML = '<p style="padding: 20px; text-align: center; color: #666;">Carregando mensagens...</p>';

        fetch(`api/obter_mensagens.php?caixa=${box}`) // Crie este endpoint
            .then(response => {
                if (!response.ok) throw new Error('Erro na rede ou servidor.');
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    messageListDiv.innerHTML = `<p style="padding: 20px; text-align: center; color: red;">Erro: ${data.error}</p>`;
                    return;
                }
                messageListDiv.innerHTML = ''; // Clear previous messages

                if (data.messages.length === 0) {
                    messageListDiv.innerHTML = `<p style="padding: 20px; text-align: center; color: #666;">Nenhuma mensagem na ${box === 'entrada' ? 'caixa de entrada' : 'caixa de saída'}.</p>`;
                    return;
                }

                data.messages.forEach(msg => {
                    const item = document.createElement('div');
                    item.className = 'message-list-item';
                    if (box === 'entrada' && !msg.lida) {
                        item.classList.add('unread');
                    }
                    item.dataset.messageId = msg.id;
                    item.innerHTML = `
                        <div class="sender-recipient">
                            ${box === 'entrada' ? htmlspecialchars(msg.remetente_nome) : htmlspecialchars(msg.destinatario_nome)}
                        </div>
                        <div class="subject-snippet">
                            ${msg.assunto ? htmlspecialchars(msg.assunto) : '(Sem Assunto)'} -
                            <span>${htmlspecialchars(msg.corpo_mensagem.substring(0, 70))}...</span>
                        </div>
                        <div class="message-date">${msg.data_envio_formatado}</div>
                    `;
                    item.addEventListener('click', () => loadMessageDetail(msg.id, box));
                    messageListDiv.appendChild(item);
                });
                updateUnreadCount(); // Update unread count after loading inbox
            })
            .catch(error => {
                console.error('Erro ao carregar mensagens:', error);
                messageListDiv.innerHTML = '<p style="padding: 20px; text-align: center; color: red;">Erro ao carregar mensagens. Tente novamente.</p>';
            });
    }

    // Load details of a specific message
    function loadMessageDetail(messageId, box) {
        showPanel('messageDetail');
        detailSubject.textContent = 'Carregando...';
        detailSender.textContent = '';
        detailDate.textContent = '';
        detailBody.textContent = 'Carregando conteúdo...';

        fetch(`api/obter_mensagem_detalhes.php?id=${messageId}&caixa=${box}`) // Crie este endpoint
            .then(response => {
                if (!response.ok) throw new Error('Erro na rede ou servidor.');
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    alert('Erro ao carregar detalhes da mensagem: ' + data.error);
                    console.error('Erro:', data.error);
                    loadMessages(currentBox); // Go back to list
                    return;
                }
                const msg = data.message;
                detailSubject.textContent = htmlspecialchars(msg.assunto || '(Sem Assunto)');
                detailSender.textContent = htmlspecialchars(msg.remetente_nome);
                detailDate.textContent = htmlspecialchars(msg.data_envio_formatado);
                detailBody.textContent = htmlspecialchars(msg.corpo_mensagem);

                // Set up reply button
                btnReply.onclick = () => composeReply(msg.remetente_id, msg.remetente_nome, msg.assunto);
                // Set up delete button
                btnDeleteMessage.onclick = () => deleteMessage(msg.id, box);

                // If it was an unread message in inbox, mark it as read and update count
                if (box === 'entrada' && !msg.lida) {
                    // Visually remove unread style
                    const listItem = document.querySelector(`.message-list-item[data-message-id="${messageId}"]`);
                    if (listItem) {
                        listItem.classList.remove('unread');
                    }
                    updateUnreadCount(); // This will re-fetch the count
                }
            })
            .catch(error => {
                console.error('Erro ao carregar detalhes:', error);
                alert('Não foi possível carregar os detalhes da mensagem.');
                loadMessages(currentBox); // Go back to list
            });
    }

    // Compose new message form setup
    function composeNewMessage() {
        showPanel('composeMessage');
        composeForm.reset();
        composeRecipient.innerHTML = '<option value="">Carregando usuários...</option>';
        composeSubject.value = '';
        composeBody.value = '';
        loadUsersForCompose(); // Load users for recipient dropdown
    }

    // Compose reply message form setup
    function composeReply(recipientId, recipientName, originalSubject) {
        showPanel('composeMessage');
        composeForm.reset(); // Clear form

        // Populate recipient
        composeRecipient.innerHTML = `<option value="${recipientId}">${htmlspecialchars(recipientName)}</option>`;
        composeRecipient.value = recipientId;
        composeRecipient.disabled = true; // Disable changing recipient for reply

        // Populate subject
        if (!originalSubject.startsWith('Re: ')) {
            composeSubject.value = `Re: ${originalSubject}`;
        } else {
            composeSubject.value = originalSubject;
        }

        // Add original message quote (optional, but good for webmail)
        const quote = `\n\n--- Mensagem Original ---\nDe: ${recipientName}\nAssunto: ${originalSubject}\n\n${detailBody.textContent}`;
        composeBody.value = quote;
        composeBody.focus(); // Focus on message body for typing
        composeBody.setSelectionRange(0, 0); // Put cursor at beginning

        // Adjust textarea height for the pre-filled content
        composeBody.style.height = 'auto';
        composeBody.style.height = (composeBody.scrollHeight) + 'px';
    }

    // Load users for the "To:" dropdown when composing
    function loadUsersForCompose() {
        fetch('api/obter_usuarios.php') // Crie este endpoint
            .then(response => {
                if (!response.ok) throw new Error('Erro na rede ou servidor.');
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    composeRecipient.innerHTML = `<option value="">Erro ao carregar usuários</option>`;
                    console.error('Erro ao carregar usuários:', data.error);
                    return;
                }
                composeRecipient.innerHTML = '<option value="">Selecione um destinatário</option>';
                data.users.forEach(user => {
                    // Exclude current user from recipient list
                    if (user.id != <?php echo json_encode($user_id); ?>) {
                        const option = document.createElement('option');
                        option.value = user.id;
                        option.textContent = htmlspecialchars(user.nome);
                        composeRecipient.appendChild(option);
                    }
                });
            })
            .catch(error => {
                console.error('Erro ao carregar usuários para compor:', error);
                composeRecipient.innerHTML = `<option value="">Erro ao carregar usuários</option>`;
            });
    }

    // Handle sending a new message
    composeForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const recipientId = composeRecipient.value;
        const subject = composeSubject.value.trim();
        const body = composeBody.value.trim();

        if (!recipientId || !subject || !body) {
            alert('Por favor, preencha todos os campos.');
            return;
        }

        const sendBtn = composeForm.querySelector('.btn-send');
        sendBtn.disabled = true;
        sendBtn.textContent = 'Enviando...';

        fetch('api/enviar_mensagem.php', { // Crie este endpoint
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                destinatario_id: recipientId,
                assunto: subject,
                corpo_mensagem: body
            }),
        })
        .then(response => {
            if (!response.ok) throw new Error('Erro na rede ou servidor.');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('Mensagem enviada com sucesso!');
                loadMessages('enviadas'); // Go to sent box after sending
            } else {
                alert('Erro ao enviar mensagem: ' + (data.error || 'Erro desconhecido.'));
            }
        })
        .catch(error => {
            console.error('Erro ao enviar mensagem:', error);
            alert('Não foi possível enviar a mensagem. Tente novamente.');
        })
        .finally(() => {
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar';
        });
    });

    // Handle canceling compose
    btnCancelCompose.addEventListener('click', function() {
        loadMessages(currentBox); // Go back to the last viewed box
    });

    // Handle deleting a message
    function deleteMessage(messageId, box) {
        if (!confirm('Tem certeza que deseja apagar esta mensagem?')) {
            return;
        }

        fetch('api/apagar_mensagem.php', { // Crie este endpoint
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                message_id: messageId,
                box: box // To distinguish if sender or recipient is deleting
            }),
        })
        .then(response => {
            if (!response.ok) throw new Error('Erro na rede ou servidor.');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('Mensagem apagada.');
                loadMessages(currentBox); // Reload current box
            } else {
                alert('Erro ao apagar mensagem: ' + (data.error || 'Erro desconhecido.'));
            }
        })
        .catch(error => {
            console.error('Erro ao apagar mensagem:', error);
            alert('Não foi possível apagar a mensagem. Tente novamente.');
        });
    }

    // Function to update unread count for the inbox
    function updateUnreadCount() {
        fetch('api/obter_contagem_nao_lidas.php') // Crie este endpoint
            .then(response => {
                if (!response.ok) throw new Error('Erro na rede ou servidor.');
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    console.error('Erro ao obter contagem de não lidas:', data.error);
                    return;
                }
                if (data.unread_count > 0) {
                    inboxUnreadCount.textContent = data.unread_count;
                    inboxUnreadCount.style.display = 'inline-block';
                } else {
                    inboxUnreadCount.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Erro ao obter contagem de não lidas:', error);
            });
    }


    // Event listeners for navigation
    btnCompose.addEventListener('click', composeNewMessage);
    navInbox.addEventListener('click', (e) => {
        e.preventDefault();
        loadMessages('entrada');
    });
    navSent.addEventListener('click', (e) => {
        e.preventDefault();
        loadMessages('enviadas');
    });

    // Auxiliary function for HTML escaping
    function htmlspecialchars(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // Initial load
    document.addEventListener('DOMContentLoaded', function() {
        loadMessages('<?php echo $caixa; ?>'); // Load initial box based on URL or default
        updateUnreadCount(); // Load initial unread count
        // Consider refreshing unread count periodically for new message notifications
        setInterval(updateUnreadCount, 60000); // Check for new messages every 60 seconds
    });

    
    // ... (seu código JavaScript existente) ...

// NOVO: Referência para o badge da sidebar
const sidebarUnreadCount = document.getElementById('sidebarUnreadCount');

// NOVO: Função para atualizar a contagem de não lidas na sidebar
function updateSidebarUnreadCount() {
    fetch('api/obter_contagem_nao_lidas.php')
        .then(response => {
            if (!response.ok) throw new Error('Erro na rede ou servidor ao obter contagem da sidebar.');
            return response.json();
        })
        .then(data => {
            if (data.error) {
                console.error('Erro ao carregar contagem de não lidas para sidebar:', data.error);
                sidebarUnreadCount.style.display = 'none'; // Esconde se houver erro
                return;
            }
            const count = data.unread_count;
            if (count > 0) {
                sidebarUnreadCount.textContent = count;
                sidebarUnreadCount.style.display = 'inline-block'; // Mostra o badge
            } else {
                sidebarUnreadCount.style.display = 'none'; // Esconde se não houver mensagens não lidas
            }
        })
        .catch(error => {
            console.error('Erro ao buscar contagem de não lidas para sidebar:', error);
            sidebarUnreadCount.style.display = 'none';
        });
}

// ... (sua função updateUnreadCount existente para a caixa de entrada) ...
// Certifique-se de que sua função `updateUnreadCount()` (que já existe para a Caixa de Entrada)
// também chame `updateSidebarUnreadCount()` ao final, para manter ambos os contadores sincronizados.
function updateUnreadCount() {
    fetch('api/obter_contagem_nao_lidas.php') // Reutilize o mesmo endpoint
        .then(response => {
            if (!response.ok) throw new Error('Erro na rede ou servidor.');
            return response.json();
        })
        .then(data => {
            if (data.error) {
                console.error('Erro ao carregar contagem de não lidas:', data.error);
                inboxUnreadCount.style.display = 'none';
                return;
            }
            const count = data.unread_count;
            if (count > 0) {
                inboxUnreadCount.textContent = count;
                inboxUnreadCount.style.display = 'inline-block';
            } else {
                inboxUnreadCount.style.display = 'none';
            }
            // NOVO: Atualiza também o contador da sidebar
            updateSidebarUnreadCount();
        })
        .catch(error => {
            console.error('Erro ao buscar contagem de não lidas:', error);
            inboxUnreadCount.style.display = 'none';
        });
}

// NOVO: Chamar a função de atualização da sidebar quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    // Sua chamada loadMessages('<?php echo $caixa; ?>'); deve continuar aqui
    loadMessages('<?php echo $caixa; ?>');
    // E também o novo:
    updateSidebarUnreadCount();
});

// Certifique-se de que `loadMessages()` e `loadMessageDetail()` chamam `updateUnreadCount()` (que por sua vez chama `updateSidebarUnreadCount()`)
// para garantir que a contagem seja atualizada após as operações de listagem e leitura de mensagens.

    </script>

</body>
</html>