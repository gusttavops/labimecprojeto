<?php
// chat.php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario']) || !isset($_SESSION['nome'])) {
    header("Location: login.php");
    exit;
}

$nome_usuario_logado = $_SESSION['nome']; // Nome do usuário logado para exibição

// Incluir a conexão com o banco de dados
// CERTIFIQUE-SE DE QUE ESTE CAMINHO ESTÁ CORRETO PARA O SEU SETUP!
require_once __DIR__ . '/includes/db.php';

// Carregar foto do usuário e ID
$foto_perfil_usuario_logado = 'https://lh3.googleusercontent.com/a/ACg8ocJxiK2NM9FwrnfbB3aJ7rN5hJ0l6qATGGQVIaqwjakwPzSnS8Yc=s288-c-no'; // URL padrão para foto de perfil
$id_usuario_logado = null; // ID do usuário logado

try {
    // Buscar o ID do usuário e a foto de perfil do banco de dados
    $stmt_user_data = $pdo->prepare("SELECT id, foto_perfil FROM usuarios WHERE usuario = ?");
    $stmt_user_data->execute([$_SESSION['usuario']]);
    $dados_usuario_logado = $stmt_user_data->fetch(PDO::FETCH_ASSOC);

    if ($dados_usuario_logado) {
        $id_usuario_logado = $dados_usuario_logado['id'];
        $_SESSION['user_id'] = $id_usuario_logado; // Armazenar user_id na sessão para uso posterior em APIs
        if (!empty($dados_usuario_logado['foto_perfil'])) {
            $foto_perfil_usuario_logado = $dados_usuario_logado['foto_perfil'];
        }
    } else {
        // Se o usuário não for encontrado no DB (mas está na sessão), redireciona para login
        header("Location: login.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar dados do usuário em chat.php: " . $e->getMessage());
    // Em caso de erro no DB, redireciona para login
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - Sumidos PB</title>
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

        /* Main content styles for chat */
        .main-chat {
            margin-left: 251px; /* Offset for the sidebar */
            width: calc(100% - 251px);
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        .chat-header {
            background-color: #004d00; color: #fff; padding: 15px 25px; font-size: 20px; font-weight: 600;
            position: sticky; top: 0; z-index: 100;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .chat-container {
            display: flex;
            flex: 1; /* Occupies remaining vertical space */
            height: calc(100vh - 60px); /* Subtract header height */
            background-color: #f5f5f5;
        }

        /* Left Panel: Contact List */
        .contact-list-panel {
            width: 280px; /* Wider for contact names */
            background: #fff;
            border-right: 1px solid #ddd;
            padding: 10px 0;
            flex-shrink: 0;
            display: flex; /* Adicionado para flexbox vertical */
            flex-direction: column; /* Adicionado para flexbox vertical */
            height: 100%; /* Ocupa toda a altura para o scroll interno */
        }
        .contact-list-panel h4 {
            padding: 10px 15px;
            color: #003300;
            font-size: 1.1em;
            border-bottom: 1px solid #eee;
            margin-bottom: 5px;
        }

        /* Busca de Contatos */
        .contact-search {
            padding: 0 15px 10px; /* Adiciona padding para o input */
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .contact-search input {
            flex-grow: 1;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 20px;
            outline: none;
            font-size: 0.9em;
        }
        .contact-search i {
            color: #888;
        }

        /* Container para os itens de contato, para que o overflow-y funcione abaixo da busca */
        .contact-list-items {
            overflow-y: auto;
            flex-grow: 1; /* Permite que ocupe o espaço restante para o scroll */
        }

        .contact-item {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s;
        }
        .contact-item:hover {
            background-color: #f0f0f0;
        }
        .contact-item.active {
            background-color: #e0ffe0;
            font-weight: bold;
            color: #003300;
        }
        .contact-item img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }
        .contact-item .contact-name {
            flex-grow: 1;
        }
        .contact-item .unread-badge {
            background-color: red;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8em;
            margin-left: auto;
            display: none; /* Hide by default, show if unread */
        }
        /* Botão de exclusão */
        .delete-contact-btn {
            color: #aaa; /* Cor padrão, pode ser ajustada */
            cursor: pointer;
            margin-left: 10px; /* Espaçamento da direita do nome */
            transition: color 0.2s;
            font-size: 0.9em;
            display: none; /* Ocultar por padrão, mostrar no hover */
        }
        .contact-item:hover .delete-contact-btn {
            display: inline-block; /* Mostrar no hover do item de contato */
            color: #ff6666; /* Cor mais chamativa no hover */
        }


        /* Right Panel: Chat Area */
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: #fff;
            position: relative; /* For loading overlay */
        }

        /* Novo estilo para o cabeçalho do parceiro de chat */
        .chat-partner-header {
            background-color: #004d00;
            color: #fff;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: sticky; /* Mantém no topo */
            top: 0;
            z-index: 99; /* Acima das mensagens, mas abaixo do chat-header principal se houver */
            display: none; /* Oculto por padrão, visível quando um chat é selecionado */
        }
        .chat-partner-header img {
            width: 45px; /* Tamanho da foto no cabeçalho */
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 2px solid #fff; /* Borda branca na foto */
        }
        .chat-partner-header h3 {
            margin: 0;
            font-size: 1.2em;
            font-weight: 600;
            flex-grow: 1; /* Permite que o nome ocupe o espaço restante */
        }

        /* Estilos para os novos botões de ação no cabeçalho do chat */
        .chat-partner-header .action-buttons {
            display: flex;
            gap: 15px; /* Espaçamento entre os botões */
            margin-left: 20px; /* Espaço à esquerda dos botões */
        }

        .chat-partner-header .action-buttons button {
            background: none;
            border: none;
            color: #fff;
            font-size: 1.3em; /* Tamanho maior para os ícones */
            cursor: pointer;
            padding: 5px;
            border-radius: 5px;
            transition: background-color 0.2s, color 0.2s;
        }
        .chat-partner-header .action-buttons button:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        .chat-partner-header .action-buttons button.blocked {
            color: #ff9999; /* Cor para indicar que está bloqueado */
        }


        .chat-messages {
            flex-grow: 1;
            overflow-y: auto;
            padding: 20px;
            background-color: #f9f9f9; /* Light background for chat bubbles */
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .chat-message {
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 15px;
            word-wrap: break-word;
            font-size: 0.95em;
        }
        .chat-message.sent {
            background-color: #dcf8c6; /* Light green for sent messages */
            align-self: flex-end; /* Align to right */
            margin-left: auto;
        }
        .chat-message.received {
            background-color: #e6e6e6; /* Light gray for received messages */
            align-self: flex-start; /* Align to left */
            margin-right: auto;
        }
        .message-time {
            font-size: 0.75em;
            color: #888;
            margin-top: 5px;
            text-align: right; /* For sent messages */
        }
        .chat-message.received .message-time {
            text-align: left; /* For received messages */
        }

        .chat-input-area {
            display: flex;
            padding: 15px;
            border-top: 1px solid #eee;
            background-color: #fff;
            align-items: center;
        }
        .chat-input-area input[type="text"] {
            flex-grow: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
            font-size: 1em;
            margin-right: 10px;
            outline: none;
        }
        .chat-input-area button {
            background-color: #006400;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            font-size: 1.2em;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s;
        }
        .chat-input-area button:hover {
            background-color: #004d00;
        }

        /* Placeholder for no chat selected */
        .no-chat-selected {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            height: 100%;
            color: #888;
            font-size: 1.1em;
            text-align: center;
        }
        .no-chat-selected i {
            font-size: 3em;
            margin-bottom: 20px;
            color: #bbb;
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
    </style>
</head>
<body>
    <audio id="notificationSound" src="audio/notificacaomsg.wav" preload="auto"></audio>

    <div class="sidebar">
        <div>
            <div class="user-profile">
                <img src="<?php echo htmlspecialchars($foto_perfil_usuario_logado); ?>" alt="Foto de Perfil" id="fotoPerfil">
                <div class="edit-photo" id="btnAlterarFoto">Alterar foto</div>
                <h3><?php echo htmlspecialchars($nome_usuario_logado); ?></h3>
            </div>
            <button onclick="window.location.href='inicio.php'">
                <i class="fas fa-home"></i> Início
            </button>

            <button onclick="window.location.href='mensagens.php'" id="sidebarMensagensButton">
                <i class="fas fa-envelope"></i> Mensagens
                <span class="sidebar-badge" id="sidebarUnreadCount" style="display:none;"></span>
            </button>

            <button onclick="window.location.href='contatos.php'">
                <i class="fas fa-user-friends"></i> Contatos
            </button>
            
            <button onclick="window.location.href='lista_desaparecidos.php'">
                <i class="fas fa-list"></i> Lista de Desaparecidos
            </button>
            <button onclick="window.location.href='registrar_desaparecido.php'">
                <i class="fas fa-user-plus"></i> Registrar Desaparecimento
            </button>
            <button><i class="fa-solid fa-circle-user"></i> Comunidade</button>

            <button onclick="window.location.href='dadosferramentas.php'">
                <i class="fas fa-database"></i> Dados e Ferramentas
            </button>

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

    <div class="main-chat">
        <div class="chat-header">Chat em Tempo Real</div>

        <div class="chat-container">
            <div class="contact-list-panel" id="contactListPanel">
                <h4>Conversas</h4>
                <div class="contact-search">
                    <input type="text" id="contactSearchInput" placeholder="Buscar contatos...">
                    <i class="fas fa-search"></i>
                </div>
                <div id="contactList" class="contact-list-items">
                    <p style="padding: 10px; text-align: center; color: #666;">Carregando contatos...</p>
                </div>
            </div>

            <div class="chat-area" id="chatArea">
                <div class="chat-partner-header" id="chatPartnerHeader">
                    <img src="https://lh3.googleusercontent.com/a/ACg8ocJxiK2NM9FwrnfbB3aJ7rN5hJ0l6qATGGQVIaqwjakwPzSnS8Yc=s288-c-no" alt="Foto de Perfil" id="chatPartnerPhoto">
                    <h3 id="chatPartnerName"></h3>
                    <div class="action-buttons">
                        <button id="blockToggleBtn" title="Bloquear ou Desbloquear">
                            <i class="fas fa-user-slash"></i>
                        </button>
                        <button id="reportBtn" title="Denunciar Usuário">
                            <i class="fas fa-flag"></i>
                        </button>
                    </div>
                </div>

                <div class="no-chat-selected" id="noChatSelected">
                    <i class="fas fa-comments"></i>
                    <p>Selecione uma conversa para começar a conversar.</p>
                </div>

                <div class="chat-messages" id="chatMessages" style="display: none;">
                </div>

                <div class="chat-input-area" id="chatInputArea" style="display: none;">
                    <input type="text" id="chatMessageInput" placeholder="Digite sua mensagem...">
                    <button id="sendMessageButton"><i class="fas fa-paper-plane"></i></button>
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
        // --- Variáveis Globais do Chat ---
        const chat_contactListPanel = document.getElementById('contactListPanel');
        const chat_contactSearchInput = document.getElementById('contactSearchInput'); // Novo
        const chat_contactList = document.getElementById('contactList'); // Novo ID para o container dos contatos

        const chat_chatArea = document.getElementById('chatArea');
        const chat_chatPartnerHeader = document.getElementById('chatPartnerHeader'); // NOVO
        const chat_chatPartnerPhoto = document.getElementById('chatPartnerPhoto');   // NOVO
        const chat_chatPartnerName = document.getElementById('chatPartnerName');      // NOVO
        const chat_blockToggleBtn = document.getElementById('blockToggleBtn');        // NOVO
        const chat_reportBtn = document.getElementById('reportBtn');                 // NOVO
        const chat_chatMessagesDiv = document.getElementById('chatMessages');
        const chat_chatMessageInput = document.getElementById('chatMessageInput');
        const chat_sendMessageButton = document.getElementById('sendMessageButton');
        const chat_noChatSelected = document.getElementById('noChatSelected');
        const chat_chatInputArea = document.getElementById('chatInputArea');

        const chat_sidebarUnreadCount = document.getElementById('sidebarUnreadCount');
        const chat_sidebarMensagensButton = document.getElementById('sidebarMensagensButton');
        const chat_notificationSound = document.getElementById('notificationSound');

        let chat_currentChatPartnerId = null; // ID do usuário com quem o chat está ativo
        let chat_currentChatPartnerName = null; // Nome do usuário com quem o chat está ativo
        let chat_currentChatPartnerBlockedStatus = false; // Status de bloqueio do parceiro
        let chat_pollingInterval; // Para polling de novas mensagens no chat ativo

        // --- Funções Auxiliares ---

        // Função para escapar caracteres HTML
        function chat_htmlspecialchars(str) {
            if (typeof str !== 'string') return str;
            var map = {
                '&': '&', // Correção para escapar o '&' corretamente
                '<': '<',
                '>': '>',
                '"': '"',
                "'": '\u0027' // Correção para escapar o ' corretamente
            };
            return str.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        // Função para formatar data/hora
        function chat_formatDateTime(dateTimeString) {
            const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
            const date = new Date(dateTimeString);
            return date.toLocaleDateString('pt-BR', options);
        }

        // Função para carregar a lista de contatos (usuários)
        async function chat_loadContacts(searchTerm = '') {
            chat_contactList.innerHTML = '<p style="padding: 10px; text-align: center; color: #666;">Carregando contatos...</p>';
            try {
                // REVERTIDO: Nome da API de contatos voltou para o original
                const response_contacts = await fetch('api/chat_contatos.php');
                const data_contacts = await response_contacts.json();

                if (data_contacts.error) {
                    chat_contactList.innerHTML = `<p style="padding: 10px; text-align: center; color: red;">Erro: ${chat_htmlspecialchars(data_contacts.error)}</p>`;
                    return;
                }

                chat_contactList.innerHTML = '';
                const currentUser_id = <?php echo json_encode($id_usuario_logado); ?>;
                let foundContacts = false;

                data_contacts.users.forEach(user_item => {
                    if (user_item.id == currentUser_id) {
                        return; // Pula o próprio usuário
                    }

                    // Filtra pelo termo de busca (case-insensitive)
                    if (searchTerm && !user_item.nome.toLowerCase().includes(searchTerm.toLowerCase())) {
                        return; // Pula se não corresponder ao termo de busca
                    }

                    foundContacts = true;
                    const contactItem_element = document.createElement('div');
                    contactItem_element.className = 'contact-item';
                    contactItem_element.dataset.userId = user_item.id;
                    contactItem_element.dataset.userPhoto = user_item.foto_perfil || 'https://lh3.googleusercontent.com/a/ACg8ocJxiK2NM9FwrnfbB3aJ7rN5hJ0l6qATGGQVIaqwjakwPzSnS8Yc=s288-c-no';
                    contactItem_element.dataset.isBlocked = user_item.is_blocked ? 'true' : 'false';

                    contactItem_element.innerHTML = `
                        <img src="${chat_htmlspecialchars(user_item.foto_perfil || 'https://lh3.googleusercontent.com/a/ACg8ocJxiK2NM9FwrnfbB3aJ7rN5hJ0l6qATGGQVIaqwjakwPzSnS8Yc=s288-c-no')}" alt="Foto">
                        <span class="contact-name">${chat_htmlspecialchars(user_item.nome)}</span>
                        <span class="unread-badge" id="chatUnread-${user_item.id}" style="display:none;">0</span>
                        <i class="fas fa-trash-alt delete-contact-btn" data-delete-id="${user_item.id}" title="Excluir conversa"></i>
                    `;
                    
                    // Adicione o event listener para o botão de exclusão
                    const deleteButton = contactItem_element.querySelector('.delete-contact-btn');
                    deleteButton.addEventListener('click', (event) => {
                        event.stopPropagation(); // Impede que o clique no botão abra o chat
                        chat_deleteConversation(user_item.id, user_item.nome);
                    });

                    // Adicione a foto e o nome e o status de bloqueio aos parâmetros da função chat_startChat
                    contactItem_element.addEventListener('click', () => 
                        chat_startChat(user_item.id, user_item.nome, user_item.foto_perfil || 'https://lh3.googleusercontent.com/a/ACg8ocJxiK2NM9FwrnfbB3aJ7rN5hJ0l6qATGGQVIaqwjakwPzSnS8Yc=s288-c-no', user_item.is_blocked)
                    );
                    chat_contactList.appendChild(contactItem_element);
                });

                if (!foundContacts) {
                    chat_contactList.innerHTML = '<p style="padding: 10px; text-align: center; color: #666;">Nenhum contato encontrado.</p>';
                }

                // Chamar a atualização de contagem de não lidas de chat após carregar a lista
                chat_updateChatUnreadCounts();

            } catch (error) {
                console.error('Erro ao carregar contatos de chat:', error);
                chat_contactList.innerHTML = '<p style="padding: 10px; text-align: center; color: red;">Erro ao carregar contatos.</p>';
            }
        }

        // Função para iniciar ou mudar para um chat específico
        async function chat_startChat(partnerId_param, partnerName_param, partnerPhoto_param, isBlocked_param) {
            // Limpa o polling anterior, se houver
            if (chat_pollingInterval) {
                clearInterval(chat_pollingInterval);
            }

            chat_currentChatPartnerId = partnerId_param;
            chat_currentChatPartnerName = partnerName_param;
            chat_currentChatPartnerBlockedStatus = isBlocked_param;

            chat_noChatSelected.style.display = 'none';
            chat_chatMessagesDiv.style.display = 'flex';
            chat_chatInputArea.style.display = 'flex';
            chat_chatPartnerHeader.style.display = 'flex';

            // Atualiza o cabeçalho do parceiro de chat
            chat_chatPartnerPhoto.src = chat_htmlspecialchars(partnerPhoto_param);
            chat_chatPartnerName.textContent = chat_htmlspecialchars(partnerName_param);
            chat_updateBlockButton(isBlocked_param);

            // Remove a classe 'active' de todos os contatos e adiciona ao clicado
            document.querySelectorAll('.contact-item').forEach(item => item.classList.remove('active'));
            const activeContactItem = document.querySelector(`.contact-item[data-user-id="${partnerId_param}"]`);
            if (activeContactItem) {
                activeContactItem.classList.add('active');
            }

            // Resetar o badge de não lidas para este contato
            const badge_element = document.getElementById(`chatUnread-${partnerId_param}`);
            if (badge_element) {
                badge_element.textContent = '0';
                badge_element.style.display = 'none';
            }

            await chat_loadChatMessages(partnerId_param); // Carrega as mensagens iniciais
            chat_chatMessagesDiv.scrollTop = chat_chatMessagesDiv.scrollHeight; // Rola para o final
            chat_chatMessageInput.focus(); // Foca no campo de input

            // Inicia o polling para este chat
            chat_pollingInterval = setInterval(() => chat_loadChatMessages(partnerId_param, true), 3000); // Poll a cada 3 segundos
            chat_updateChatUnreadCounts(); // Atualiza a contagem geral
        }

        // Função para atualizar o estado do botão de Bloquear/Desbloquear
        function chat_updateBlockButton(isBlocked) {
            const icon = chat_blockToggleBtn.querySelector('i');
            if (isBlocked) {
                icon.className = 'fas fa-unlock-alt'; // Ícone de desbloquear
                chat_blockToggleBtn.title = 'Desbloquear Usuário';
                chat_blockToggleBtn.classList.add('blocked'); // Adiciona classe para estilo visual de bloqueado
                chat_chatMessageInput.placeholder = 'Você bloqueou este usuário.';
                chat_chatMessageInput.disabled = true;
                chat_sendMessageButton.disabled = true;
                chat_sendMessageButton.style.opacity = 0.5;
                chat_sendMessageButton.style.cursor = 'not-allowed';

            } else {
                icon.className = 'fas fa-user-slash'; // Ícone de bloquear
                chat_blockToggleBtn.title = 'Bloquear Usuário';
                chat_blockToggleBtn.classList.remove('blocked'); // Remove classe de bloqueado
                chat_chatMessageInput.placeholder = 'Digite sua mensagem...';
                chat_chatMessageInput.disabled = false;
                chat_sendMessageButton.disabled = false;
                chat_sendMessageButton.style.opacity = 1;
                chat_sendMessageButton.style.cursor = 'pointer';
            }
        }

        // Função para lidar com o bloqueio/desbloqueio de usuário
        chat_blockToggleBtn.addEventListener('click', async () => {
            if (!chat_currentChatPartnerId) return;

            const action = chat_currentChatPartnerBlockedStatus ? 'unblock' : 'block'; // Determina a ação
            const confirm_message = chat_currentChatPartnerBlockedStatus ?
                `Tem certeza que deseja desbloquear ${chat_htmlspecialchars(chat_currentChatPartnerName)}?` :
                `Tem certeza que deseja bloquear ${chat_htmlspecialchars(chat_currentChatPartnerName)}? Você não receberá mais mensagens dele(a) e ele(a) não poderá enviar para você.`;

            if (!confirm(confirm_message)) {
                return;
            }

            try {
                // ATUALIZADO: Nome da API de bloquear/desbloquear
                const response = await fetch('api/bloquear_usuariobatepapo.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ partner_id: chat_currentChatPartnerId, action: action })
                });
                const data = await response.json();

                if (data.success) {
                    chat_currentChatPartnerBlockedStatus = !chat_currentChatPartnerBlockedStatus; // Inverte o status
                    chat_updateBlockButton(chat_currentChatPartnerBlockedStatus); // Atualiza o botão
                    alert(data.message);
                    chat_loadContacts(); // Recarregar a lista para atualizar visualmente
                    if(chat_currentChatPartnerBlockedStatus){ // Se bloqueou, limpa e esconde o chat
                        chat_chatMessagesDiv.innerHTML = `<p style="padding: 20px; text-align: center; color: #888;">Você bloqueou este usuário. Nenhuma mensagem será exibida ou enviada.</p>`;
                    } else { // Se desbloqueou, carrega as mensagens novamente
                        chat_loadChatMessages(chat_currentChatPartnerId);
                    }
                } else {
                    alert('Erro ao ' + action + ' usuário: ' + chat_htmlspecialchars(data.error));
                }
            } catch (error) {
                console.error('Erro na requisição de bloqueio/desbloqueio:', error);
                alert('Erro na comunicação com o servidor ao bloquear/desbloquear.');
            }
        });

        // Função para denunciar um usuário
        chat_reportBtn.addEventListener('click', async () => {
            if (!chat_currentChatPartnerId) return;

            const reason = prompt(`Por favor, descreva o motivo da denúncia contra ${chat_htmlspecialchars(chat_currentChatPartnerName)}:`);
            if (reason === null || reason.trim() === '') {
                alert('A denúncia foi cancelada ou o motivo não foi fornecido.');
                return;
            }

            try {
                // ATUALIZADO: Nome da API de denunciar usuário
                const response = await fetch('api/denunciar_usuariobatepapo.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ reported_user_id: chat_currentChatPartnerId, reason: reason })
                });
                const data = await response.json();

                if (data.success) {
                    alert('Denúncia enviada com sucesso! Obrigado por nos ajudar a manter a comunidade segura.');
                } else {
                    alert('Erro ao denunciar usuário: ' + chat_htmlspecialchars(data.error));
                }
            } catch (error) {
                console.error('Erro na requisição de denúncia:', error);
                alert('Erro na comunicação com o servidor ao denunciar.');
            }
        });

        // Função para carregar as mensagens de chat
        async function chat_loadChatMessages(partnerId, isPolling = false) {
            // Se o chat com este parceiro não estiver ativo, não carrega mensagens em polling
            if (isPolling && chat_currentChatPartnerId !== partnerId) {
                return;
            }
            if (!isPolling) {
                chat_chatMessagesDiv.innerHTML = '<p style="padding: 20px; text-align: center; color: #666;">Carregando mensagens...</p>';
            }

            try {
                // ATUALIZADO: Nome da API de mensagens
                const response_messages = await fetch(`api/mensagens_chat.php?partner_id=${partnerId}`);
                const data_messages = await response_messages.json();

                if (data_messages.error) {
                    chat_chatMessagesDiv.innerHTML = `<p style="padding: 20px; text-align: center; color: red;">Erro: ${chat_htmlspecialchars(data_messages.error)}</p>`;
                    return;
                }

                const currentScrollBottom = chat_chatMessagesDiv.scrollHeight - chat_chatMessagesDiv.clientHeight - chat_chatMessagesDiv.scrollTop;
                const shouldScroll = currentScrollBottom <= 20; // Rola se estiver perto do final (20px de margem)

                chat_chatMessagesDiv.innerHTML = ''; // Limpa antes de adicionar

                if (data_messages.messages.length === 0) {
                    chat_chatMessagesDiv.innerHTML = '<p style="padding: 20px; text-align: center; color: #888;">Nenhuma mensagem nesta conversa ainda.</p>';
                } else {
                    data_messages.messages.forEach(msg => {
                        const message_element = document.createElement('div');
                        message_element.className = `chat-message ${msg.remetente_id == <?php echo json_encode($id_usuario_logado); ?> ? 'sent' : 'received'}`;
                        message_element.innerHTML = `
                            ${chat_htmlspecialchars(msg.corpo_mensagem)}
                            <div class="message-time">${chat_htmlspecialchars(msg.data_envio_formatado)}</div>
                        `;
                        chat_chatMessagesDiv.appendChild(message_element);
                    });

                    if (shouldScroll || !isPolling) { // Se não é polling, ou se estava no final, rola
                        chat_chatMessagesDiv.scrollTop = chat_chatMessagesDiv.scrollHeight;
                    }
                }

                // Marcar mensagens como lidas APÓS CARREGAR E EXIBIR
                if (!isPolling) { // Só marca como lida na carga inicial ou quando o chat é ativado
                    await chat_markMessagesAsRead(partnerId);
                    chat_updateChatUnreadCounts(); // Atualiza contagem de não lidas na sidebar
                }
            } catch (error) {
                console.error('Erro ao carregar mensagens de chat:', error);
                if (!isPolling) {
                    chat_chatMessagesDiv.innerHTML = '<p style="padding: 20px; text-align: center; color: red;">Erro ao carregar mensagens.</p>';
                }
            }
        }

        // Função para enviar mensagem
        chat_sendMessageButton.addEventListener('click', chat_sendMessage);
        chat_chatMessageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                chat_sendMessage();
            }
        });

        async function chat_sendMessage() {
            const messageText = chat_chatMessageInput.value.trim();
            if (messageText === '' || !chat_currentChatPartnerId) {
                return;
            }

            if (chat_currentChatPartnerBlockedStatus) {
                alert('Você bloqueou este usuário e não pode enviar mensagens para ele(a).');
                return;
            }

            chat_chatMessageInput.value = ''; // Limpa o input imediatamente

            try {
                // ATUALIZADO: Nome da API de enviar mensagem
                const response_send_msg = await fetch('api/enviar_mensagemchat.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        destinatario_id: chat_currentChatPartnerId,
                        corpo_mensagem: messageText
                    })
                });
                const data_send_msg = await response_send_msg.json();

                if (data_send_msg.success) {
                    // Recarrega as mensagens para exibir a nova
                    chat_loadChatMessages(chat_currentChatPartnerId);
                } else {
                    alert('Erro ao enviar mensagem: ' + chat_htmlspecialchars(data_send_msg.error));
                }
            } catch (error) {
                console.error('Erro ao enviar mensagem:', error);
                alert('Erro na comunicação com o servidor ao enviar mensagem.');
            }
        }

        // Função para marcar mensagens como lidas
        async function chat_markMessagesAsRead(partnerId) {
            try {
                // ATUALIZADO: Nome da API de marcar como lido
                const response = await fetch('api/marcar_lida_chatbatepapo.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ partner_id: partnerId })
                });
                const data = await response.json();
                if (data.success) {
                    // console.log('Mensagens marcadas como lidas para', partnerId);
                } else {
                    console.error('Erro ao marcar como lido:', data.error);
                }
            } catch (error) {
                console.error('Erro na requisição de marcar como lido:', error);
            }
        }

        // Função para atualizar as contagens de mensagens não lidas nos contatos da sidebar
        async function chat_updateChatUnreadCounts() {
            try {
                // ATUALIZADO: Nome da API de não lidas do chat
                const response_chat_unread = await fetch('api/naolidas_chat.php');
                const data_chat_unread = await response_chat_unread.json();

                if (data_chat_unread.success) {
                    // **CORREÇÃO:** Use a contagem total fornecida pela API naolidas_chat.php
                    // A API retorna 'unread_chat_count', não 'unread_counts' por usuário.
                    const totalUnreadChat = data_chat_unread.unread_chat_count || 0; // Garante que seja um número

                    // Atualiza o badge geral do chat (se você tiver um elemento com ID 'chatBadge')
                    const chatBadge = document.getElementById('chatBadge');
                    if (chatBadge) {
                        chatBadge.textContent = totalUnreadChat > 0 ? totalUnreadChat : '';
                        chatBadge.style.display = totalUnreadChat > 0 ? 'block' : 'none';
                    }

                    // **REMOVIDO/COMENTADO:** A lógica para atualizar badges individuais dos contatos,
                    // pois a API 'naolidas_chat.php' atual não fornece contagens por contato.
                    // Se precisar disso, a API precisa ser modificada para retornar um objeto de contagens por ID.
                    document.querySelectorAll('.contact-item .unread-badge').forEach(badge => {
                        badge.style.display = 'none'; // Esconde todos os badges individuais, pois não há dados para eles
                    });
                    
                    // Atualiza o contador geral na sidebar (para "Mensagens" tradicionais e chat)
                    // **NOTA:** A contagem de mensagens "tradicionais" foi removida deste arquivo
                    // devido à sua incerteza sobre a funcionalidade. Agora, o badge da sidebar
                    // refletirá apenas a contagem total de mensagens do chat em tempo real.
                    let currentTradUnread = 0; // Removido, pois a funcionalidade foi desativada
                    let totalCombinedUnread = totalUnreadChat + currentTradUnread;

                    if (totalCombinedUnread > 0) {
                        chat_sidebarUnreadCount.textContent = totalCombinedUnread;
                        chat_sidebarUnreadCount.style.display = 'inline-block';
                        chat_notificationSound.play().catch(e => console.error("Erro ao tocar som de notificação:", e));
                    } else {
                        chat_sidebarUnreadCount.style.display = 'none';
                    }

                } else {
                    console.error('Erro ao obter contagens de não lidas de chat:', data_chat_unread.error);
                }
            } catch (error) {
                console.error('Erro na requisição de contagens de não lidas de chat:', error);
            }
            // A chamada para chat_updateTraditionalMessagesUnreadCount() foi removida daqui.
        }

        // A função chat_updateTraditionalMessagesUnreadCount() foi removida deste arquivo.


        // Função para excluir uma conversa
        async function chat_deleteConversation(partnerId, partnerName) {
            if (!confirm(`Tem certeza que deseja excluir o histórico de conversa com ${chat_htmlspecialchars(partnerName)}? Esta ação não pode ser desfeita.`)) {
                return;
            }

            try {
                // ATUALIZADO: Nome da API de excluir conversa
                const response = await fetch('api/excluir_chat_conversabatepapo.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, // Importante para enviar form data
                    body: `partner_id=${partnerId}`
                });
                const data = await response.json();

                if (data.success) {
                    alert(data.message);
                    chat_loadContacts(); // Recarrega a lista de contatos
                    if (chat_currentChatPartnerId === partnerId) {
                        // Se a conversa ativa foi excluída, reseta a interface do chat
                        chat_currentChatPartnerId = null;
                        chat_noChatSelected.style.display = 'flex';
                        chat_chatMessagesDiv.style.display = 'none';
                        chat_chatInputArea.style.display = 'none';
                        chat_chatPartnerHeader.style.display = 'none';
                        clearInterval(chat_pollingInterval); // Para o polling se a conversa ativa foi excluída
                    }
                } else {
                    alert('Erro ao excluir conversa: ' + chat_htmlspecialchars(data.error));
                }
            } catch (error) {
                console.error('Erro na requisição de exclusão de conversa:', error);
                alert('Erro na comunicação com o servidor ao excluir conversa.');
            }
        }

        // --- Event Listeners e Início ---

        // Carrega contatos na inicialização
        document.addEventListener('DOMContentLoaded', () => {
            chat_loadContacts();
            // Inicia o polling geral para contagens de não lidas a cada 5 segundos
            setInterval(chat_updateChatUnreadCounts, 5000);
            // A chamada para chat_updateTraditionalMessagesUnreadCount() foi removida daqui.
        });

        // Event listener para a busca de contatos
        chat_contactSearchInput.addEventListener('input', (e) => {
            chat_loadContacts(e.target.value);
        });


        // --- Funções e Eventos do Modal de Foto de Perfil (Mantidos como estão) ---
        const btnAlterarFoto = document.getElementById('btnAlterarFoto');
        const modalAlterarFoto = document.getElementById('modalAlterarFoto');
        const closeModal = document.getElementById('closeModal');

        btnAlterarFoto.addEventListener('click', () => {
            modalAlterarFoto.style.display = 'flex';
        });

        closeModal.addEventListener('click', () => {
            modalAlterarFoto.style.display = 'none';
        });

        // Fechar modal ao clicar fora
        window.addEventListener('click', (event) => {
            if (event.target == modalAlterarFoto) {
                modalAlterarFoto.style.display = 'none';
            }
        });
    </script>
</body>
</html>