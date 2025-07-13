<?php
// api/apicontatos_chat.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Usuário não autenticado.']);
    exit;
}

$current_user_id = $_SESSION['user_id'];

// Incluir a conexão com o banco de dados
require_once __DIR__ . '/../includes/db.php'; // Ajuste o caminho se sua estrutura de pastas for diferente

try {
    // Busca todos os usuários, exceto o próprio usuário logado
    $stmt = $pdo->prepare("SELECT id, nome, foto_perfil FROM usuarios WHERE id != ?");
    $stmt->execute([$current_user_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response_users = [];
    foreach ($users as $user) {
        // Garante uma foto padrão se 'foto_perfil' for nula ou vazia
        $user['foto_perfil'] = $user['foto_perfil'] ?? 'https://lh3.googleusercontent.com/a/ACg8ocJxiK2NM9FwrnfbB3aJ7rN5hJ0l6qATGGQVIaqwjakwPzSnS8Yc=s288-c-no'; 

        // Verifica se o usuário atual (logado) bloqueou este contato
        $stmt_blocked = $pdo->prepare("SELECT COUNT(*) FROM bloqueios WHERE bloqueador_id = ? AND bloqueado_id = ?");
        $stmt_blocked->execute([$current_user_id, $user['id']]);
        $user['is_blocked'] = ($stmt_blocked->fetchColumn() > 0); // true se bloqueado, false caso contrário

        $response_users[] = $user;
    }

    echo json_encode(['success' => true, 'users' => $response_users]);

} catch (PDOException $e) {
    error_log("Erro ao buscar contatos na API apicontatos_chat.php: " . $e->getMessage());
    echo json_encode(['error' => 'Erro interno do servidor ao carregar contatos.']);
}
?>