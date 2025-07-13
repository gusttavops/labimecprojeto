<?php
session_start();
header('Content-Type: application/json');

// Inclua seu arquivo de conexão com o banco de dados
// Certifique-se de que este caminho está correto para o seu setup
require_once __DIR__ . '/../includes/db.php'; 

// Verifica se a sessão do usuário está ativa
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Usuário não autenticado.']);
    exit;
}

// Usando 'chatList_loggedInUserId' para evitar conflitos
$chatList_loggedInUserId = $_SESSION['user_id'];

try {
    // Prepara a consulta para selecionar todos os usuários, exceto o próprio usuário logado
    $stmt_get_chat_users = $pdo->prepare("SELECT id, nome, foto_perfil FROM usuarios WHERE id != ?");
    $stmt_get_chat_users->execute([$chatList_loggedInUserId]);

    // Pega todos os resultados
    $fetched_chat_users = $stmt_get_chat_users->fetchAll(PDO::FETCH_ASSOC);

    // Itera sobre os usuários para ajustar a URL da foto de perfil, se estiver vazia
    foreach ($fetched_chat_users as &$user_data_for_chat) {
        if (empty($user_data_for_chat['foto_perfil'])) {
            $user_data_for_chat['foto_perfil'] = 'https://lh3.googleusercontent.com/a/ACg8ocJxiK2NM9FwrnfbB3aJ7rN5hJ0l6qATGGQVIaqwjakwPzSnS8Yc=s288-c-no'; // URL padrão para foto de perfil
        }
    }

    // Retorna a lista de usuários em formato JSON
    echo json_encode(['users' => $fetched_chat_users]);

} catch (PDOException $e) {
    // Registra o erro no log do servidor para depuração
    error_log("Erro ao obter lista de usuários para o chat: " . $e->getMessage());
    // Retorna uma mensagem de erro genérica para o cliente
    echo json_encode(['error' => 'Erro interno do servidor ao carregar contatos do chat.']);
}
?>