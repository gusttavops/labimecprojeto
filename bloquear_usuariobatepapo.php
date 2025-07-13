<?php
// api/bloquear_usuariobatepapo.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método de requisição inválido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$partner_id = $input['partner_id'] ?? null;
$action = $input['action'] ?? null; // 'block' ou 'unblock'

if (empty($partner_id) || !in_array($action, ['block', 'unblock'])) {
    echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos.']);
    exit;
}

$user_id = $_SESSION['user_id'];

require_once __DIR__ . '/../includes/db.php'; // Ajuste o caminho

try {
    if ($action === 'block') {
        // Verifica se já está bloqueado para evitar duplicidade
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM bloqueios WHERE bloqueador_id = ? AND bloqueado_id = ?");
        $stmt_check->execute([$user_id, $partner_id]);
        if ($stmt_check->fetchColumn() > 0) {
            echo json_encode(['success' => true, 'message' => 'Usuário já bloqueado.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO bloqueios (bloqueador_id, bloqueado_id, data_bloqueio) VALUES (?, ?, NOW())");
        $stmt->execute([$user_id, $partner_id]);
        
        echo json_encode(['success' => true, 'message' => 'Usuário bloqueado com sucesso.']);
    } elseif ($action === 'unblock') {
        $stmt = $pdo->prepare("DELETE FROM bloqueios WHERE bloqueador_id = ? AND bloqueado_id = ?");
        $stmt->execute([$user_id, $partner_id]);
        echo json_encode(['success' => true, 'message' => 'Usuário desbloqueado com sucesso.']);
    }
} catch (PDOException $e) {
    error_log("Erro no bloqueio/desbloqueio em bloquear_usuariobatepapo.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro interno do servidor.']);
}
?>