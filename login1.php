<?php
session_start();

// Conexão com banco
$host = "localhost";
$db = "sistema_login";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

$mensagem = "";
$form_atual = "loginForm";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['acao'])) {
        if ($_POST['acao'] === 'login') {
            $usuario = $_POST['login'] ?? '';
            $senha = $_POST['senha'] ?? '';

            $sql = "SELECT * FROM usuarios WHERE usuario = :usuario AND senha = :senha";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'usuario' => $usuario,
                'senha' => hash('sha256', $senha)
            ]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

           if ($user) {
    $_SESSION['id'] = $user['id'];
    $_SESSION['usuario'] = $user['usuario'];
    $_SESSION['nome'] = $user['nome'];
    // Adicione outros campos se quiser, exemplo: $_SESSION['foto'] = $user['foto'];
    header("Location: inicio.php");
    exit;
                } else {
                $mensagem = "<p style='color: red; text-align:center;'>Usuário ou senha incorretos.</p>";
                $form_atual = "loginForm";
            }
        } elseif ($_POST['acao'] === 'cadastro') {
            $usuario = $_POST['cadLogin'] ?? '';
            $nome = $_POST['cadNome'] ?? '';
            $senha = $_POST['cadSenha'] ?? '';
            $confirma = $_POST['cadConfirma'] ?? '';
            $email = $_POST['cadEmail'] ?? '';

            if ($senha !== $confirma) {
                $mensagem = "<p style='color: red; text-align:center;'>As senhas não conferem.</p>";
                $form_atual = "cadastroForm";
            } else {
                $sql = "SELECT * FROM usuarios WHERE usuario = :usuario";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['usuario' => $usuario]);
                if ($stmt->fetch()) {
                    $mensagem = "<p style='color: red; text-align:center;'>Usuário já existe.</p>";
                    $form_atual = "cadastroForm";
                } else {
                    $sql = "INSERT INTO usuarios (usuario, nome, senha, email) VALUES (:usuario, :nome, :senha, :email)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        'usuario' => $usuario,
                        'nome' => $nome,
                        'senha' => hash('sha256', $senha),
                        'email' => $email
                    ]);
                    $mensagem = "<p style='color: green; text-align:center;'>Cadastro realizado com sucesso! Faça login.</p>";
                    $form_atual = "loginForm";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Sumidos PB - Acesso</title>
  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-image: url('https://i.postimg.cc/52Jk8mcw/DESAPARECIMENTO-DE-PESSOAS-NA-PARA-BA.jpg');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      color: #fff;
    }
    .container {
      display: flex;
      gap: 80px;
      max-width: 1400px;
      width: 100%;
      padding: 60px;
      justify-content: center;
      align-items: center;
      background-color: transparent;
    }
    .left {
      text-align: center;
      flex: 1;
    }
    .left img {
      width: 400px;
      height: auto;
    }
    .left h1 {
      font-size: 50px;
      margin: 30px 0 20px;
    }
    .left h1 span {
      color: red;
    }
    .subtitle {
      background-color: rgba(10, 79, 28, 0.8);
      color: white;
      padding: 16px 30px;
      border-radius: 30px;
      font-size: 22px;
      font-weight: bold;
      display: inline-block;
    }
    .right {
      padding: 50px 40px;
      border-radius: 10px;
      width: 450px;
      background-color: rgba(0, 0, 0, 0.4);
      backdrop-filter: blur(6px);
      color: white;
    }
    .right h2 {
      text-align: center;
      font-size: 40px;
      margin-bottom: 10px;
    }
    .right label {
      display: block;
      font-size: 22px;
      margin-bottom: 10px;
    }
    .right input[type="text"],
    .right input[type="password"],
    .right input[type="email"] {
      width: 100%;
      padding: 18px;
      font-size: 20px;
      border: none;
      border-radius: 6px;
      margin-bottom: 30px;
      color: black;
    }
    .right button, .right input[type="submit"], .right a.button-link {
      width: 100%;
      padding: 16px;
      font-size: 20px;
      border: none;
      border-radius: 6px;
      background-color: #1e7f33;
      color: white;
      font-weight: bold;
      cursor: pointer;
      text-align: center;
      display: inline-block;
      text-decoration: none;
      transition: background-color 0.3s ease;
    }
    .right button:hover,
    .right input[type="submit"]:hover,
    .right a.button-link:hover {
      background-color: #155d26;
    }
    .right .actions {
      display: flex;
      justify-content: space-between;
      margin-top: 30px;
    }
    .right .actions a {
      color: white;
      text-decoration: none;
      font-size: 20px;
      font-weight: bold;
      cursor: pointer;
    }
    .right .actions a:hover {
      text-decoration: underline;
    }
    @media (max-width: 1000px) {
      .container {
        flex-direction: column;
        padding: 40px 20px;
        gap: 40px;
      }
      .left img {
        width: 250px;
      }
      .right {
        width: 100%;
      }
    }
    .mensagem {
      text-align: center;
      margin-bottom: 20px;
      font-weight: bold;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="left">
      <img src="https://i.postimg.cc/T31433nb/3dlogo-psd.png" alt="Logo Sumidos PB" />
      <h1>SUMIDOS <span>PB</span></h1>
      <div class="subtitle">INTERMEDIANDO A LOCALIZAÇÃO</div>
    </div>

    <div class="right">
      <!-- LOGIN -->
      <div id="loginForm" style="display: <?php echo ($form_atual === 'loginForm') ? 'block' : 'none'; ?>;">
        <h2>Acesso</h2>
        <?php if ($form_atual === 'loginForm' && trim($mensagem) !== '') : ?>
          <div class="mensagem"><?php echo $mensagem; ?></div>
        <?php endif; ?>
        <form method="post" action="">
          <input type="hidden" name="acao" value="login">
          <label for="login">Login:</label>
          <input type="text" id="login" name="login" required>

          <label for="senha">Senha:</label>
          <input type="password" id="senha" name="senha" required>

          <input type="submit" value="Entrar">
        </form>
        <div class="actions" style="margin-top: 40px;">
          <a onclick="showForm('cadastroForm')">Cadastro</a>
        </div>
      </div>

      <!-- CADASTRO -->
      <div id="cadastroForm" style="display: <?php echo ($form_atual === 'cadastroForm') ? 'block' : 'none'; ?>;">
        <h2>Cadastro</h2>
        <?php if ($form_atual === 'cadastroForm' && trim($mensagem) !== '') : ?>
          <div class="mensagem"><?php echo $mensagem; ?></div>
        <?php endif; ?>
        <form method="post" action="">
          <input type="hidden" name="acao" value="cadastro">

          <label for="cadLogin">Login:</label>
          <input type="text" id="cadLogin" name="cadLogin" required>

          <label for="cadNome">Nome Completo:</label>
          <input type="text" id="cadNome" name="cadNome" required>

          <label for="cadSenha">Senha:</label>
          <input type="password" id="cadSenha" name="cadSenha" required>

          <label for="cadConfirma">Confirmar Senha:</label>
          <input type="password" id="cadConfirma" name="cadConfirma" required>

          <label for="cadEmail">E-mail:</label>
          <input type="email" id="cadEmail" name="cadEmail" required>

          <input type="submit" value="Cadastrar">
        </form>
        <div class="actions" style="margin-top: 40px;">
          <a onclick="showForm('loginForm')">Login</a>
        </div>
      </div>

    </div>
  </div>

  <script>
    function showForm(formId) {
      document.getElementById('loginForm').style.display = 'none';
      document.getElementById('cadastroForm').style.display = 'none';
      document.getElementById(formId).style.display = 'block';
    }
  </script>
</body>
</html>
