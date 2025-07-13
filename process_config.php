<?php
// public/process_config.php

session_start();

// Inclui o arquivo de conexão com o banco de dados
require_once __DIR__ . '/includes/db.php'; // Ajuste o caminho se necessário

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario']) || !isset($_SESSION['nome'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['usuario'];
$user_id = null;

try {
    // Busca o ID do usuário a partir do nome de usuário
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
    $stmt->execute([$username]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_data) {
        $user_id = $user_data['id'];
    } else {
        // Usuário não encontrado, redirecionar para login
        header("Location: login.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar ID do usuário em process_config.php: " . $e->getMessage());
    header('Location: configuracoes.php?status=error_general');
    exit();
}

// Verifica se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'update_name':
            $new_name = trim($_POST['new_name'] ?? '');

            if (empty($new_name)) {
                header('Location: configuracoes.php?section=alterar_nome&status=error_name');
                exit();
            }

            try {
                $stmt = $pdo->prepare("UPDATE usuarios SET nome = :name WHERE id = :id"); // 'nome' é a coluna do nome do usuário
                $stmt->bindParam(':name', $new_name);
                $stmt->bindParam(':id', $user_id);
                $stmt->execute();

                // Atualiza o nome na sessão para refletir a mudança imediatamente
                if ($stmt->rowCount() > 0) {
                    $_SESSION['nome'] = $new_name;
                    header('Location: configuracoes.php?section=alterar_nome&status=success_name');
                } else {
                    header('Location: configuracoes.php?section=alterar_nome&status=success_name'); // Considera sucesso mesmo se não houver mudança
                }
                exit();
            } catch (PDOException $e) {
                error_log("Erro ao atualizar nome: " . $e->getMessage());
                header('Location: configuracoes.php?section=alterar_nome&status=error_name');
                exit();
            }
            break;

        case 'update_email':
            $new_email = trim($_POST['new_email'] ?? '');

            if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
                header('Location: configuracoes.php?section=alterar_email&status=error_email');
                exit();
            }

            try {
                // Verificar se o email já existe para outro usuário
                $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email AND id != :id");
                $stmt->bindParam(':email', $new_email);
                $stmt->bindParam(':id', $user_id);
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                    header('Location: configuracoes.php?section=alterar_email&status=error_email&msg=email_exists');
                    exit();
                }

                $stmt = $pdo->prepare("UPDATE usuarios SET email = :email WHERE id = :id");
                $stmt->bindParam(':email', $new_email);
                $stmt->bindParam(':id', $user_id);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    header('Location: configuracoes.php?section=alterar_email&status=success_email');
                } else {
                    header('Location: configuracoes.php?section=alterar_email&status=success_email');
                }
                exit();
            } catch (PDOException $e) {
                error_log("Erro ao atualizar email: " . $e->getMessage());
                header('Location: configuracoes.php?section=alterar_email&status=error_email');
                exit();
            }
            break;

        case 'update_password':
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            // 1. Validar campos
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                header('Location: configuracoes.php?section=alterar_senha&status=error_password&msg=empty_fields');
                exit();
            }
            if ($new_password !== $confirm_password) {
                header('Location: configuracoes.php?section=alterar_senha&status=error_password&msg=passwords_mismatch');
                exit();
            }
            // Adicione validações de complexidade de senha aqui (tamanho mínimo, etc.)
            if (strlen($new_password) < 6) { // Exemplo de validação
                header('Location: configuracoes.php?section=alterar_senha&status=error_password&msg=short_password');
                exit();
            }

            try {
                // 2. Obter hash da senha atual do banco de dados
                $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = :id"); // 'senha' é a coluna da senha do usuário
                $stmt->bindParam(':id', $user_id);
                $stmt->execute();
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user_data || !password_verify($current_password, $user_data['senha'])) {
                    // Senha atual incorreta
                    header('Location: configuracoes.php?section=alterar_senha&status=error_password&msg=incorrect_current_password');
                    exit();
                }

                // 3. Hash da nova senha
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // 4. Atualizar senha no banco de dados
                $stmt = $pdo->prepare("UPDATE usuarios SET senha = :password WHERE id = :id");
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':id', $user_id);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    header('Location: configuracoes.php?section=alterar_senha&status=success_password');
                } else {
                    // Nenhuma linha afetada, pode significar que a senha era a mesma
                    header('Location: configuracoes.php?section=alterar_senha&status=success_password');
                }
                exit();
            } catch (PDOException $e) {
                error_log("Erro ao atualizar senha: " . $e->getMessage());
                header('Location: configuracoes.php?section=alterar_senha&status=error_password');
                exit();
            }
            break;

        case 'update_dob':
            $new_dob = trim($_POST['new_dob'] ?? '');

            // Validação de formato de data YYYY-MM-DD
            if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $new_dob)) {
                header('Location: configuracoes.php?section=alterar_data_nascimento&status=error_dob&msg=invalid_format');
                exit();
            }
            // Opcional: validação de data real (ex: checar se é uma data válida)
            list($year, $month, $day) = explode('-', $new_dob);
            if (!checkdate($month, $day, $year)) {
                 header('Location: configuracoes.php?section=alterar_data_nascimento&status=error_dob&msg=invalid_date');
                 exit();
            }

            try {
                $stmt = $pdo->prepare("UPDATE usuarios SET data_nascimento = :dob WHERE id = :id"); // Assumindo 'data_nascimento' para Data de Nascimento
                $stmt->bindParam(':dob', $new_dob);
                $stmt->bindParam(':id', $user_id);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    header('Location: configuracoes.php?section=alterar_data_nascimento&status=success_dob');
                } else {
                    header('Location: configuracoes.php?section=alterar_data_nascimento&status=success_dob');
                }
                exit();
            } catch (PDOException $e) {
                error_log("Erro ao atualizar data de nascimento: " . $e->getMessage());
                header('Location: configuracoes.php?section=alterar_data_nascimento&status=error_dob');
                exit();
            }
            break;

        default:
            // Redireciona para a página de configurações com um status de erro genérico
            header('Location: configuracoes.php?status=error_general');
            exit();
            break;
    }
} else {
    // Se não for uma requisição POST, redireciona para a página de configurações
    header('Location: configuracoes.php');
    exit();
}
?>