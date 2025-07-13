<?php
require_once 'conexao.php'; // Caminho corrigido
session_start();

// Adicione esta verificação para garantir que apenas usuários logados possam buscar
if (!isset($_SESSION['id'])) {
    http_response_code(401); // Define o status HTTP como Não Autorizado
    echo json_encode(['error' => 'Acesso não autorizado. Por favor, faça login.']);
    exit;
}

// === COMENTE ESTA LINHA TEMPORARIAMENTE PARA DEBUGAR ===
// header('Content-Type: application/json');

$termo = $_GET['termo'] ?? '';
$id_atual = $_SESSION['id'];

// --- DEBUGG AQUI ---
echo "<h2>Valores de Debug:</h2>";
echo "Termo de busca recebido: <strong>" . htmlspecialchars($termo) . "</strong><br>";
echo "ID do usuário logado (id_atual): <strong>" . htmlspecialchars($id_atual) . "</strong><br>";
echo "Termo formatado para SQL LIKE: <strong>" . htmlspecialchars("%" . $termo . "%") . "</strong><br>";
echo "<hr>";
// --- FIM DO DEBUGG ---

try {
    $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE nome LIKE ? AND id != ?");
    $stmt->execute(["%$termo%", $id_atual]);

    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- DEBUGG AQUI ---
    echo "<h2>Resultados da Query:</h2>";
    var_dump($resultados); // Isso mostrará o conteúdo do array de resultados
    echo "<hr>";
    // --- FIM DO DEBUGG ---

    // === DESCOMENTE ESTA LINHA DEPOIS DE DEBUGAR ===
    // header('Content-Type: application/json');
    echo json_encode($resultados);

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    // Mantenha o getMessage() para debug, mas remova em produção
    echo json_encode(['error' => 'Erro no banco de dados: ' . $e->getMessage()]);
}

?>