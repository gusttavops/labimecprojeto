<?php
session_start();

if (!isset($_SESSION['usuario']) || !isset($_SESSION['nome'])) {
    header("Location: login.php");
    exit;
}

$nome = $_SESSION['nome'];

// Incluir a conexão com o banco de dados
require_once __DIR__ . '/conexao.php';

// Carregar foto do usuário e ID
$foto_perfil = 'https://lh3.googleusercontent.com/a/ACg8ocJxiK2NM9FwrnfbB3aJ7rN5hJ0l6qATGGQVIaqwjakwPzSnS8Yc=s288-c-no'; // Padrão
$user_id = null;

try {
    $stmt = $pdo->prepare("SELECT id, foto_perfil FROM usuarios WHERE usuario = ?");
    $stmt->execute([$_SESSION['usuario']]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_data) {
        $user_id = $user_data['id'];
        $_SESSION['user_id'] = $user_id; // Armazenar user_id na sessão
        if (!empty($user_data['foto_perfil'])) {
            $foto_perfil = $user_data['foto_perfil'];
        }
    } else {
        header("Location: login.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar dados do usuário em contatos.php: " . $e->getMessage());
    header("Location: login.php");
    exit;
}

// Se recebeu upload de nova foto (da parte de configurações) - Mantido para consistência
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
                } catch (PDOException $e) {
                    error_log("Erro ao atualizar foto de perfil: " . $e->getMessage());
                }
            }
        }
    }
}

// Determinar qual aba exibir inicialmente
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'meus_contatos';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Contatos - Sumidos PB</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        /* CSS Base (copiado de suas outras páginas para consistência) */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Arial", sans-serif; display: flex; height: 100vh; color: #333; }

        /* Estilos da barra lateral */
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

        /* Main content styles for Contacts */
        .main-contacts {
            margin-left: 251px;
            width: calc(100% - 251px);
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        .contacts-header {
            background-color: #004d00; color: #fff; padding: 15px 25px; font-size: 20px; font-weight: 600;
            position: sticky; top: 0; z-index: 100;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .contacts-container {
            display: flex;
            flex: 1;
            background-color: #f5f5f5;
        }

        /* Left Panel: Contacts Navigation */
        .contacts-nav {
            width: 220px;
            background: #fff;
            border-right: 1px solid #ddd;
            overflow-y: auto;
            padding: 10px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
        }
        .contacts-nav .nav-link {
            display: block;
            padding: 12px 15px;
            margin: 5px 0;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
        }
        .contacts-nav .nav-link:hover {
            background-color: #e0ffe0;
        }
        .contacts-nav .nav-link.active {
            background-color: #c0f4c0;
            font-weight: bold;
            color: #003300;
        }
        .contacts-nav .badge {
            margin-left: auto;
            background-color: red;
            color: white;
            padding: 2px 7px;
            border-radius: 10px;
            font-size: 12px;
            min-width: 25px;
            text-align: center;
        }

        /* Right Panel: Content Area */
        .contacts-content-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 20px;
            overflow-y: auto;
        }
        .contacts-content-area h2 {
            font-size: 24px;
            color: #003300;
            margin-bottom: 20px;
        }
        .contacts-list, .invites-list, .suggestions-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); /* Responsive grid */
            gap: 20px;
        }
        .contact-card {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .contact-card img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
        }
        .contact-card h4 {
            margin: 5px 0 10px 0;
            font-size: 18px;
            color: #003300;
        }
        .contact-card .actions {
            display: flex;
            flex-wrap: wrap; /* Allow buttons to wrap */
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
        }
        .contact-card button {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .contact-card .btn-primary {
            background-color: #006400;
            color: #fff;
        }
        .contact-card .btn-primary:hover {
            background-color: #004d00;
        }
        .contact-card .btn-secondary {
            background-color: #e0e0e0;
            color: #333;
        }
        .contact-card .btn-secondary:hover {
            background-color: #ccc;
        }
        .contact-card .btn-danger {
            background-color: #dc3545;
            color: #fff;
        }
        .contact-card .btn-danger:hover {
            background-color: #c82333;
        }

        /* Message for empty lists */
        .empty-list-message {
            text-align: center;
            color: #666;
            padding: 40px;
            font-style: italic;
        }

        /* Modal for photo (from your other files) */
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
        <button onclick="window.location.href='mensagens.php'">
            <i class="fas fa-envelope"></i> Mensagens
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

<div class="main-contacts">
    <div class="contacts-header">Meus Contatos</div>

    <div class="contacts-container">
        <div class="contacts-nav">
            <a href="#" class="nav-link" id="navMeusContatos" data-tab="meus_contatos">
                <i class="fas fa-address-book"></i> Meus Contatos
            </a>
            <a href="#" class="nav-link" id="navConvitesPendentes" data-tab="convites_pendentes">
                <i class="fas fa-envelope-open-text"></i> Convites Recebidos
                <span class="badge" id="pendingInvitesCount" style="display:none;"></span>
            </a>
            <a href="#" class="nav-link" id="navSugestoes" data-tab="sugestoes">
                <i class="fas fa-user-plus"></i> Sugestões de Contato
            </a>
        </div>

        <div class="contacts-content-area" id="contactsContentArea">
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
    // Variáveis DOM
    const contactsContentArea = document.getElementById('contactsContentArea');
    const navMeusContatos = document.getElementById('navMeusContatos');
    const navConvitesPendentes = document.getElementById('navConvitesPendentes');
    const navSugestoes = document.getElementById('navSugestoes');
    const pendingInvitesCount = document.getElementById('pendingInvitesCount');

    // Funções Auxiliares
    function htmlspecialchars(str) {
        if (typeof str !== 'string') return str;
        var map = {
            '&': '&',
            '<': '<',
            '>': '>',
            '"': '"',
            "'": '\''
        };
        return str.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // FUNÇÃO ALTERADA AQUI
    function updateNavLinkActive(activeTabName) {
        document.querySelectorAll('.contacts-nav .nav-link').forEach(link => {
            link.classList.remove('active');
            if (link.dataset.tab === activeTabName) {
                link.classList.add('active');
            }
        });
    }

    function showLoading() {
        contactsContentArea.innerHTML = '<p class="empty-list-message">Carregando...</p>';
    }

    function displayEmptyMessage(message) {
        contactsContentArea.innerHTML = `<p class="empty-list-message">${message}</p>`;
    }

    // --- Funções de Carregamento de Conteúdo ---

    function loadMyContacts() {
        showLoading();
        updateNavLinkActive('meus_contatos');
        contactsContentArea.innerHTML = '<h2>Meus Contatos</h2><div id="myContactsList" class="contacts-list"></div>';
        const myContactsList = document.getElementById('myContactsList');

        fetch('api/obter_meus_contatos.php')
            .then(response => {
                if (!response.ok) throw new Error('Erro na rede ou servidor.');
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    displayEmptyMessage(`Erro: ${htmlspecialchars(data.error)}`);
                    return;
                }
                if (data.contacts.length === 0) {
                    displayEmptyMessage('Você não tem contatos adicionados ainda.');
                    return;
                }
                myContactsList.innerHTML = ''; // Clear loading message

                data.contacts.forEach(contact => {
                    const card = document.createElement('div');
                    card.className = 'contact-card';
                    card.innerHTML = `
                        <img src="${htmlspecialchars(contact.foto_perfil || 'https://lh3.googleusercontent.com/a/ACg8ocJxiK2NM9FwrnfbB3aJ7rN5hJ0l6qATGGQVIaqwjakwPzSnS8Yc=s288-c-no')}" alt="Foto de Perfil">
                        <h4>${htmlspecialchars(contact.nome)}</h4>
                        <div class="actions">
                            <button class="btn-primary btn-send-message" data-recipient-id="${contact.id}" data-recipient-name="${htmlspecialchars(contact.nome)}">
                                <i class="fas fa-comment"></i> Mensagem
                            </button>
                            <button class="btn-secondary btn-remove-contact" data-contact-id="${contact.id}" data-contact-name="${htmlspecialchars(contact.nome)}">
                                <i class="fas fa-user-minus"></i> Remover
                            </button>
                        </div>
                    `;
                    myContactsList.appendChild(card);
                });

                // Add event listeners for new buttons
                document.querySelectorAll('.btn-send-message').forEach(button => {
                    button.addEventListener('click', (e) => {
                        const recipientId = e.currentTarget.dataset.recipientId;
                        const recipientName = e.currentTarget.dataset.recipientName;
                        // Redireciona para mensagens.php e pré-seleciona o destinatário
                        window.location.href = `mensagens.php?action=compose&recipient_id=${recipientId}&recipient_name=${encodeURIComponent(recipientName)}`;
                    });
                });
                document.querySelectorAll('.btn-remove-contact').forEach(button => {
                    button.addEventListener('click', (e) => {
                        const contactId = e.currentTarget.dataset.contactId;
                        const contactName = e.currentTarget.dataset.contactName;
                        removeContact(contactId, contactName);
                    });
                });
            })
            .catch(error => {
                console.error('Erro ao carregar contatos:', error);
                displayEmptyMessage('Erro ao carregar seus contatos. Tente novamente.');
            });
    }

    function loadPendingInvites() {
        showLoading();
        updateNavLinkActive('convites_pendentes');
        contactsContentArea.innerHTML = '<h2>Convites Recebidos</h2><div id="pendingInvitesList" class="invites-list"></div>';
        const pendingInvitesList = document.getElementById('pendingInvitesList');

        fetch('api/obter_convites_pendentes.php')
            .then(response => {
                if (!response.ok) throw new Error('Erro na rede ou servidor.');
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    displayEmptyMessage(`Erro: ${htmlspecialchars(data.error)}`);
                    return;
                }
                if (data.invites.length === 0) {
                    displayEmptyMessage('Você não tem convites de contato pendentes.');
                    pendingInvitesCount.style.display = 'none';
                    return;
                }

                pendingInvitesList.innerHTML = ''; // Clear loading message
                pendingInvitesCount.textContent = data.invites.length;
                pendingInvitesCount.style.display = 'inline-block';

                data.invites.forEach(invite => {
                    const card = document.createElement('div');
                    card.className = 'contact-card';
                    card.innerHTML = `
                        <img src="${htmlspecialchars(invite.remetente_foto_perfil || 'https://lh3.googleusercontent.com/a/ACg8ocJxiK2NM9FwrnfbB3aJ7rN5hJ0l6qATGGQVIaqwjakwPzSnS8Yc=s288-c-no')}" alt="Foto de Perfil">
                        <h4>${htmlspecialchars(invite.remetente_nome)}</h4>
                        <div class="actions">
                            <button class="btn-primary btn-accept-invite" data-invite-id="${invite.id}" data-sender-name="${htmlspecialchars(invite.remetente_nome)}">
                                <i class="fas fa-check"></i> Aceitar
                            </button>
                            <button class="btn-secondary btn-decline-invite" data-invite-id="${invite.id}" data-sender-name="${htmlspecialchars(invite.remetente_nome)}">
                                <i class="fas fa-times"></i> Recusar
                            </button>
                        </div>
                    `;
                    pendingInvitesList.appendChild(card);
                });

                // Add event listeners for new buttons
                document.querySelectorAll('.btn-accept-invite').forEach(button => {
                    button.addEventListener('click', (e) => {
                        const inviteId = e.currentTarget.dataset.inviteId;
                        const senderName = e.currentTarget.dataset.senderName;
                        acceptInvite(inviteId, senderName);
                    });
                });
                document.querySelectorAll('.btn-decline-invite').forEach(button => {
                    button.addEventListener('click', (e) => {
                        const inviteId = e.currentTarget.dataset.inviteId;
                        const senderName = e.currentTarget.dataset.senderName;
                        declineInvite(inviteId, senderName);
                    });
                });
            })
            .catch(error => {
                console.error('Erro ao carregar convites pendentes:', error);
                displayEmptyMessage('Erro ao carregar convites recebidos. Tente novamente.');
            });
    }

    function loadSuggestions() {
        showLoading();
        updateNavLinkActive('sugestoes');
        contactsContentArea.innerHTML = '<h2>Sugestões de Contato</h2><div id="suggestionsList" class="suggestions-list"></div>';
        const suggestionsList = document.getElementById('suggestionsList');

        fetch('api/obter_sugestoes_contatos.php')
            .then(response => {
                if (!response.ok) throw new Error('Erro na rede ou servidor.');
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    displayEmptyMessage(`Erro: ${htmlspecialchars(data.error)}`);
                    return;
                }
                if (data.suggestions.length === 0) {
                    displayEmptyMessage('Não há novas sugestões de contato no momento.');
                    return;
                }
                suggestionsList.innerHTML = ''; // Clear loading message

                data.suggestions.forEach(user => {
                    const card = document.createElement('div');
                    card.className = 'contact-card';
                    card.innerHTML = `
                        <img src="${htmlspecialchars(user.foto_perfil || 'https://lh3.googleusercontent.com/a/ACg8ocJxiK2NM9FwrnfbB3aJ7rN5hJ0l6qATGGQVIaqwjakwPzSnS8Yc=s288-c-no')}" alt="Foto de Perfil">
                        <h4>${htmlspecialchars(user.nome)}</h4>
                        <div class="actions">
                            <button class="btn-primary btn-send-invite" data-recipient-id="${user.id}" data-recipient-name="${htmlspecialchars(user.nome)}">
                                <i class="fas fa-user-plus"></i> Enviar Convite
                            </button>
                        </div>
                    `;
                    suggestionsList.appendChild(card);
                });

                // Add event listeners for new buttons
                document.querySelectorAll('.btn-send-invite').forEach(button => {
                    button.addEventListener('click', (e) => {
                        const recipientId = e.currentTarget.dataset.recipientId;
                        const recipientName = e.currentTarget.dataset.recipientName;
                        sendInvite(recipientId, recipientName);
                    });
                });
            })
            .catch(error => {
                console.error('Erro ao carregar sugestões:', error);
                displayEmptyMessage('Erro ao carregar sugestões de contato. Tente novamente.');
            });
    }

    // --- Funções de Ação (Enviar, Aceitar, Recusar, Remover) ---

    function sendInvite(recipientId, recipientName) {
        if (!confirm(`Deseja enviar um convite de contato para ${recipientName}?`)) {
            return;
        }

        fetch('api/enviar_convite_contato.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ destinatario_id: recipientId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Convite enviado para ${recipientName} com sucesso!`);
                loadSuggestions(); // Recarrega sugestões
            } else {
                alert(`Erro ao enviar convite: ${htmlspecialchars(data.error || 'Erro desconhecido.')}`);
            }
        })
        .catch(error => {
            console.error('Erro ao enviar convite:', error);
            alert('Erro ao enviar convite. Tente novamente.');
        });
    }

    function acceptInvite(inviteId, senderName) {
        if (!confirm(`Deseja aceitar o convite de ${senderName}?`)) {
            return;
        }

        fetch('api/aceitar_convite_contato.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ convite_id: inviteId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Convite de ${senderName} aceito com sucesso!`);
                loadPendingInvites(); // Recarrega convites pendentes
                loadMyContacts(); // Recarrega meus contatos (o novo contato aparecerá aqui)
            } else {
                alert(`Erro ao aceitar convite: ${htmlspecialchars(data.error || 'Erro desconhecido.')}`);
            }
        })
        .catch(error => {
            console.error('Erro ao aceitar convite:', error);
            alert('Erro ao aceitar convite. Tente novamente.');
        });
    }

    function declineInvite(inviteId, senderName) {
        if (!confirm(`Deseja recusar o convite de ${senderName}?`)) {
            return;
        }

        fetch('api/recusar_convite_contato.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ convite_id: inviteId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Convite de ${senderName} recusado.`);
                loadPendingInvites(); // Recarrega convites pendentes
            } else {
                alert(`Erro ao recusar convite: ${htmlspecialchars(data.error || 'Erro desconhecido.')}`);
            }
        })
        .catch(error => {
            console.error('Erro ao recusar convite:', error);
            alert('Erro ao recusar convite. Tente novamente.');
        });
    }

    function removeContact(contactId, contactName) {
        if (!confirm(`Tem certeza que deseja remover ${contactName} dos seus contatos?`)) {
            return;
        }

        fetch('api/remover_contato.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ contact_user_id: contactId }) // Enviamos o ID do usuário do contato
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`${contactName} removido dos seus contatos.`);
                loadMyContacts(); // Recarrega meus contatos
            } else {
                alert(`Erro ao remover contato: ${htmlspecialchars(data.error || 'Erro desconhecido.')}`);
            }
        })
        .catch(error => {
            console.error('Erro ao remover contato:', error);
            alert('Erro ao remover contato. Tente novamente.');
        });
    }

    // --- Event Listeners e Inicialização ---

    document.addEventListener('DOMContentLoaded', () => {
        const initialTab = '<?php echo $tab; ?>';
        if (initialTab === 'meus_contatos') {
            loadMyContacts();
        } else if (initialTab === 'convites_pendentes') {
            loadPendingInvites();
        } else if (initialTab === 'sugestoes') {
            loadSuggestions();
        }
        // Atualiza a contagem de convites pendentes ao carregar a página
        updatePendingInvitesCount();
        setInterval(updatePendingInvitesCount, 30000); // Atualiza a cada 30 segundos
    });

    navMeusContatos.addEventListener('click', (e) => {
        e.preventDefault();
        loadMyContacts();
    });

    navConvitesPendentes.addEventListener('click', (e) => {
        e.preventDefault();
        loadPendingInvites();
    });

    navSugestoes.addEventListener('click', (e) => {
        e.preventDefault();
        loadSuggestions();
    });

    function updatePendingInvitesCount() {
        fetch('api/obter_contagem_convites_pendentes.php') // Nova API necessária
            .then(response => response.json())
            .then(data => {
                if (data.count > 0) {
                    pendingInvitesCount.textContent = data.count;
                    pendingInvitesCount.style.display = 'inline-block';
                } else {
                    pendingInvitesCount.style.display = 'none';
                }
            })
            .catch(error => console.error('Erro ao buscar contagem de convites pendentes:', error));
    }


    // --- Modal de Alterar Foto (se existir e for relevante para esta página) ---
    const modalAlterarFoto = document.getElementById('modalAlterarFoto');
    const btnAlterarFoto = document.getElementById('btnAlterarFoto');
    const closeModal = document.getElementById('closeModal');

    if (btnAlterarFoto) {
        btnAlterarFoto.addEventListener('click', () => {
            modalAlterarFoto.style.display = 'flex';
        });
    }

    if (closeModal) {
        closeModal.addEventListener('click', () => {
            modalAlterarFoto.style.display = 'none';
        });
    }

    if (modalAlterarFoto) {
        window.addEventListener('click', (event) => {
            if (event.target == modalAlterarFoto) {
                modalAlterarFoto.style.display = 'none';
            }
        });
    }
</script>
</body>
</html>