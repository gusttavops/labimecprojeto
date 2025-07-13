<?php
session_start();

// Define variáveis para a sidebar (seções já existentes do seu código)
$nome_usuario_para_exibir = isset($_SESSION['nome']) ? htmlspecialchars($_SESSION['nome'], ENT_QUOTES, 'UTF-8') : 'Usuário';
$foto_perfil_para_exibir = isset($_SESSION['foto_perfil_usuario']) ? htmlspecialchars($_SESSION['foto_perfil_usuario'], ENT_QUOTES, 'UTF-8') : 'caminho/para/foto_padrao.png';

// Verifica se o usuário está logado (seções já existentes do seu código)
if (!isset($_SESSION['usuario']) || !isset($_SESSION['nome']) || !isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

require 'conexao.php'; // Certifique-se de que este arquivo existe e faz a conexão PDO

$errors = []; // Array para armazenar mensagens de erro
$success_message = ''; // Variável para a mensagem de sucesso

// --- Lista de cidades da Paraíba ---
// Esta lista é essencial para popular o <select> e para a validação.
$cidades_paraiba = [
    'Água Branca', 'Aguiar', 'Alagoa Grande', 'Alagoa Nova', 'Alagoinha', 'Alcantil', 'Algodão de Jandaíra', 'Alhandra',
    'Amparo', 'Aparecida', 'Araçagi', 'Arara', 'Araruna', 'Areia', 'Areia de Baraúnas', 'Areial', 'Arroyo do Padre',
    'Aroeiras', 'Assunção', 'Baía da Traição', 'Bananeiras', 'Baraúna', 'Barra de Santa Rosa', 'Barra de Santana',
    'Barra de São Miguel', 'Bayeux', 'Belém', 'Belém do Brejo do Cruz', 'Bernardino Batista', 'Boa Ventura',
    'Boa Vista', 'Bom Jesus', 'Bom Sucesso', 'Bonito de Santa Fé', 'Boqueirão', 'Borborema', 'Brejo do Cruz',
    'Brejo dos Santos', 'Caaporã', 'Cabaceiras', 'Cabedelo', 'Cachoeira dos Índios', 'Cacimba de Areia',
    'Cacimba de Dentro', 'Cacimbas', 'Caiçara', 'Cajazeiras', 'Cajazeirinhas', 'Campina Grande', 'Caraúbas',
    'Carrapateira', 'Casserengue', 'Catingueira', 'Catolé do Rocha', 'Caturité', 'Conceição', 'Condado',
    'Conde', 'Congo', 'Coremas', 'Coxixola', 'Cruz do Espírito Santo', 'Cubati', 'Cuité', 'Cuité de Mamanguape',
    'Cuitegi', 'Curral de Cima', 'Curral Velho', 'Damião', 'Desterro', 'Diamante', 'Dona Inês', 'Duas Estradas',
    'Emas', 'Esperança', 'Fagundes', 'Frei Martinho', 'Gado Bravo', 'Guarabira', 'Gurinhém', 'Gurjão', 'Ibiara',
    'Igaracy', 'Imaculada', 'Ingá', 'Itabaiana', 'Itaporanga', 'Itapororoca', 'Itatuba', 'Jacaraú', 'Jericó',
    'João Pessoa', 'Joca Claudino', 'Juarez Távora', 'Juazeirinho', 'Junco do Seridó', 'Juripiranga', 'Juru',
    'Lagoa', 'Lagoa de Dentro', 'Lagoa Seca', 'Lastro', 'Livramento', 'Logradouro', 'Lucena', 'Mãe d\'Água',
    'Malta', 'Mamanguape', 'Manaíra', 'Marcação', 'Mari', 'Marizópolis', 'Massaranduba', 'Mataraca', 'Matinhas',
    'Mato Grosso', 'Maturéia', 'Mogeiro', 'Montadas', 'Monte Horebe', 'Monteiro', 'Mulungu', 'Natuba', 'Nazarezinho',
    'Nova Floresta', 'Nova Olinda', 'Nova Palmeira', 'Olho d\'Água', 'Olivedos', 'Ouro Velho', 'Parari', 'Passagem',
    'Patos', 'Paulista', 'Pedra Branca', 'Pedra Lavrada', 'Pedras de Fogo', 'Piancó', 'Picuí', 'Pilar', 'Pilões',
    'Pilõezinhos', 'Pindoba', 'Piraí', 'Pirpirituba', 'Pitimbu', 'Pocinhos', 'Poço Dantas', 'Poço de José de Moura',
    'Pombal', 'Prata', 'Princesa Isabel', 'Puxinanã', 'Queimadas', 'Quixaba', 'Remígio', 'Riacho de Santo Antônio',
    'Riacho dos Cavalos', 'Rio Tinto', 'Salgadinho', 'Salgado de São Félix', 'Santa Cecília', 'Santa Cruz',
    'Santa Helena', 'Santa Inês', 'Santa Luzia', 'Santa Rita', 'Santa Teresinha', 'Santana de Mangueira',
    'Santana dos Garrotes', 'Santo André', 'São Bento', 'São Bentinho', 'São Domingos de Pombal',
    'São Domingos do Cariri', 'São Francisco', 'São João do Cariri', 'São João do Tigre', 'São José da Lagoa Tapada',
    'São José de Caiana', 'São José de Espinharas', 'São José de Piranhas', 'São José de Princesa',
    'São José do Bonfim', 'São José do Brejo do Cruz', 'São José do Sabugi', 'São José dos Ramos', 'São Mamede',
    'São Miguel de Taipu', 'São Sebastião de Lagoa de Roça', 'São Sebastião do Umbuzeiro', 'Sapé', 'Serra Branca',
    'Serra da Raiz', 'Serra Grande', 'Serra Redonda', 'Serraria', 'Sertãozinho', 'Sobrado', 'Solânea', 'Soledade',
    'Sossêgo', 'Sousa', 'Sumé', 'Tacima', 'Taperoá', 'Tavares', 'Teixeira', 'Tenório', 'Tharezinho', 'Triunfo',
    'Uiraúna', 'Umbuzeiro', 'Várzea', 'Vieirópolis', 'Vista Serrana', 'Zabelê'
];
sort($cidades_paraiba); // É uma boa prática ordenar a lista alfabeticamente.

// --- INICIALIZAÇÃO DE TODAS AS VARIÁVEIS USADAS NO FORMULÁRIO ---
// Isso evita as warnings "Undefined variable" quando a página é carregada pela primeira vez (GET request).
$foto_nome = '';
$nome = '';
$nome_mae = '';
$nome_pai = '';
$data_nascimento = ''; // Mantenha como string para o input type="date"
$idade = '';
$sexo = '';
$estado_civil = '';
$grau_instrucao = '';
$cpf = '';
$identidade = '';
$emissor_identidade = '';
$telefone = '';
$pais_nascimento = '';
$uf_nascimento = '';
$municipio_nascimento = '';
$logradouro = '';
$numero = '';
$complemento = '';
$cep = '';
$bairro = '';
$meio_locomocao = '';
$estava_acompanhada = '';
$possui_bagagem = ''; // Adicionando a nova variável
$altura = '';
$boca = '';
$cabelo = '';
$cor_cabelo = '';
$compleicao = '';
$cutis = '';
$labios = '';
$olhos = '';
$cor_olhos = '';
$rosto = '';
$testa = '';
$data_fato_ocorrido = ''; // Mantenha como string para o input type="date"
$bairro_ocorrido = '';
$cidade_ocorrido = '';
$descricao_ocorrido = '';
$descricao_adicional = ''; // Esta é a variável do campo 'descricao' no HTML

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Coleta e Validação dos Dados do Formulário ---
    // Usando o operador Null Coalescing (??) para garantir que as variáveis sejam definidas.
    // Use trim() para remover espaços em branco no início e fim.

    $nome = trim($_POST['nome'] ?? '');
    $nome_mae = trim($_POST['nome_mae'] ?? '');
    $nome_pai = trim($_POST['nome_pai'] ?? '');
    $data_nascimento = !empty($_POST['data_nascimento']) ? $_POST['data_nascimento'] : null;
    $idade = isset($_POST['idade']) && is_numeric($_POST['idade']) ? (int)$_POST['idade'] : null;
    $sexo = trim($_POST['sexo'] ?? '');
    $estado_civil = trim($_POST['estado_civil'] ?? '');
    $grau_instrucao = trim($_POST['grau_instrucao'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $identidade = trim($_POST['identidade'] ?? '');
    $emissor_identidade = trim($_POST['emissor_identidade'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $pais_nascimento = trim($_POST['pais_nascimento'] ?? '');
    $uf_nascimento = trim($_POST['uf_nascimento'] ?? '');
    $municipio_nascimento = trim($_POST['municipio_nascimento'] ?? '');
    $logradouro = trim($_POST['logradouro'] ?? '');
    $numero = isset($_POST['numero']) && is_numeric($_POST['numero']) ? (int)$_POST['numero'] : null;
    $complemento = trim($_POST['complemento'] ?? '');
    $cep = trim($_POST['cep'] ?? '');
    $bairro = trim($_POST['bairro'] ?? '');
    $meio_locomocao = trim($_POST['meio_locomocao'] ?? '');
    $estava_acompanhada = trim($_POST['estava_acompanhada'] ?? '');
    $possui_bagagem = trim($_POST['possui_bagagem'] ?? ''); // Coletando o novo campo
    $altura = trim($_POST['altura'] ?? '');
    $boca = trim($_POST['boca'] ?? '');
    $cabelo = trim($_POST['cabelo'] ?? '');
    $cor_cabelo = trim($_POST['cor_cabelo'] ?? '');
    $compleicao = trim($_POST['compleicao'] ?? '');
    $cutis = trim($_POST['cutis'] ?? '');
    $labios = trim($_POST['labios'] ?? '');
    $olhos = trim($_POST['olhos'] ?? '');
    $cor_olhos = trim($_POST['cor_olhos'] ?? '');
    $rosto = trim($_POST['rosto'] ?? '');
    $testa = trim($_POST['testa'] ?? '');
    $data_fato_ocorrido = !empty($_POST['data_fato_ocorrido']) ? $_POST['data_fato_ocorrido'] : null;
    $bairro_ocorrido = trim($_POST['bairro_ocorrido'] ?? '');
    $cidade_ocorrido = trim($_POST['cidade_ocorrido'] ?? '');
    $descricao_ocorrido = trim($_POST['descricao_ocorrido'] ?? '');
    $descricao_adicional = trim($_POST['descricao'] ?? ''); // Nome do campo no HTML é 'descricao'

    // --- Validações ---
    if (empty($nome)) {
        $errors[] = "O campo 'Nome completo' é obrigatório.";
    }
    if ($idade === null || $idade <= 0) {
        $errors[] = "O campo 'Idade' é obrigatório e deve ser um número válido.";
    }
    if (empty($sexo)) {
        $errors[] = "Por favor, selecione o 'Sexo'.";
    }
    if (empty($estava_acompanhada)) {
        $errors[] = "Por favor, selecione se 'Estava Acompanhada'.";
    }
    // Nova validação para 'Possuía Bagagem?'
    if (empty($possui_bagagem)) {
        $errors[] = "Por favor, selecione se 'Possuía Bagagem'.";
    }
    // Validação para a cidade ocorrido (select)
    if (empty($cidade_ocorrido)) {
        $errors[] = "Por favor, selecione a 'Cidade onde ocorreu o desaparecimento'.";
    } elseif (!in_array($cidade_ocorrido, $cidades_paraiba)) {
        $errors[] = "A 'Cidade onde ocorreu o desaparecimento' selecionada é inválida.";
        // Resetar para evitar que um valor inválido seja exibido ou salvo
        $cidade_ocorrido = '';
    }

    // --- Upload da foto ---
    // A variável $foto_nome já foi inicializada como vazia no início do script.
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
        $foto_nome = uniqid() . "." . $ext;
        $upload_dir = 'fotos/'; // Certifique-se que este diretório existe e tem permissões de escrita (0755)
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true); // Cria o diretório se não existir
        }
        $target_file = $upload_dir . $foto_nome;

        if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $target_file)) {
            $errors[] = "Erro ao fazer upload da imagem.";
            $foto_nome = ''; // Limpa o nome da foto se o upload falhar
        }
    } else if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] != UPLOAD_ERR_NO_FILE) {
        $errors[] = "Erro no upload da imagem: " . $_FILES['imagem']['error'];
    }

    // --- Inserção no Banco de Dados ---
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO pessoas_desaparecidas (
                usuario_id, foto, nome, nome_mae, nome_pai, sexo, estado_civil, data_nascimento, grau_instrucao, cpf, idade,
                identidade, emissor_identidade, telefone, pais_nascimento, uf_nascimento, municipio_nascimento, logradouro, numero, complemento,
                cep, bairro, meio_locomocao, estava_acompanhada, possui_bagagem, altura, boca, cabelo, cor_cabelo, compleicao,
                cutis, labios, olhos, cor_olhos, rosto, testa, data_fato_ocorrido, bairro_ocorrido, cidade_ocorrido, descricao_ocorrido, descricao_adicional
            ) VALUES (
                :usuario_id, :foto, :nome, :nome_mae, :nome_pai, :sexo, :estado_civil, :data_nascimento, :grau_instrucao, :cpf, :idade,
                :identidade, :emissor_identidade, :telefone, :pais_nascimento, :uf_nascimento, :municipio_nascimento, :logradouro, :numero, :complemento,
                :cep, :bairro, :meio_locomocao, :estava_acompanhada, :possui_bagagem, :altura, :boca, :cabelo, :cor_cabelo, :compleicao,
                :cutis, :labios, :olhos, :cor_olhos, :rosto, :testa, :data_fato_ocorrido, :bairro_ocorrido, :cidade_ocorrido, :descricao_ocorrido, :descricao_adicional
            )";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':usuario_id' => $_SESSION['id'],
                ':foto' => $foto_nome,
                ':nome' => $nome,
                ':nome_mae' => $nome_mae,
                ':nome_pai' => $nome_pai,
                ':sexo' => $sexo,
                ':estado_civil' => $estado_civil,
                ':data_nascimento' => $data_nascimento,
                ':grau_instrucao' => $grau_instrucao,
                ':cpf' => $cpf,
                ':idade' => $idade,
                ':identidade' => $identidade,
                ':emissor_identidade' => $emissor_identidade,
                ':telefone' => $telefone,
                ':pais_nascimento' => $pais_nascimento,
                ':uf_nascimento' => $uf_nascimento,
                ':municipio_nascimento' => $municipio_nascimento,
                ':logradouro' => $logradouro,
                ':numero' => $numero,
                ':complemento' => $complemento,
                ':cep' => $cep,
                ':bairro' => $bairro,
                ':meio_locomocao' => $meio_locomocao,
                ':estava_acompanhada' => $estava_acompanhada,
                ':possui_bagagem' => $possui_bagagem,
                ':altura' => $altura,
                ':boca' => $boca,
                ':cabelo' => $cabelo,
                ':cor_cabelo' => $cor_cabelo,
                ':compleicao' => $compleicao,
                ':cutis' => $cutis,
                ':labios' => $labios,
                ':olhos' => $olhos,
                ':cor_olhos' => $cor_olhos,
                ':rosto' => $rosto,
                ':testa' => $testa,
                ':data_fato_ocorrido' => $data_fato_ocorrido,
                ':bairro_ocorrido' => $bairro_ocorrido,
                ':cidade_ocorrido' => $cidade_ocorrido,
                ':descricao_ocorrido' => $descricao_ocorrido,
                ':descricao_adicional' => $descricao_adicional
            ]);

            $success_message = "Registro realizado com sucesso!";
            // Opcional: Limpar os campos do formulário após o sucesso para um novo registro
            // Isso garante que os campos no HTML fiquem vazios após um envio bem-sucedido.
            $foto_nome = '';
            $nome = '';
            $nome_mae = '';
            $nome_pai = '';
            $data_nascimento = '';
            $idade = '';
            $sexo = '';
            $estado_civil = '';
            $grau_instrucao = '';
            $cpf = '';
            $identidade = '';
            $emissor_identidade = '';
            $telefone = '';
            $pais_nascimento = '';
            $uf_nascimento = '';
            $municipio_nascimento = '';
            $logradouro = '';
            $numero = '';
            $complemento = '';
            $cep = '';
            $bairro = '';
            $meio_locomocao = '';
            $estava_acompanhada = '';
            $possui_bagagem = '';
            $altura = '';
            $boca = '';
            $cabelo = '';
            $cor_cabelo = '';
            $compleicao = '';
            $cutis = '';
            $labios = '';
            $olhos = '';
            $cor_olhos = '';
            $rosto = '';
            $testa = '';
            $data_fato_ocorrido = '';
            $bairro_ocorrido = '';
            // A cidade ocorrido será preenchida pelo loop das cidades da Paraíba.
            $cidade_ocorrido = '';
            $descricao_ocorrido = '';
            $descricao_adicional = '';

        } catch (PDOException $e) {
            $errors[] = "Erro ao registrar no banco de dados: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Registrar Desaparecimento - Sumidos PB</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; display: flex; }
        .main-content { flex: 1; background-color: #f4f4f4; padding: 20px; padding-left: 270px; overflow-y: auto; height: 100vh; box-sizing: border-box; }
        .form-container { background: #fff; padding: 20px; border-radius: 10px; max-width: 900px; margin: auto; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .form-container h2 { text-align: center; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 8px 10px; border: 1px solid #ccc; border-radius: 5px; }
        .form-actions { text-align: center; margin-top: 20px; }
        .form-actions button { background-color: #006400; color: #fff; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }

        /* --- Estilos do Sidebar (mantidos como estavam) --- */
        .sidebar {
            background-color: #000;
            color: #fff;
            width: 251px;
            height: 100vh;
            padding: 10px 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 10;
        }

        .sidebar .user-profile {
            text-align: center;
            margin-bottom: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 20px;
        }

        .sidebar .user-profile img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
        }

        .sidebar .edit-photo {
            background-color: #006400;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            cursor: pointer;
            color: #fff;
            font-weight: bold;
            margin-top: 10px;
        }

        .sidebar .user-profile h3 {
            margin-top: 10px;
            font-size: 16px;
            margin-bottom: 20px;
        }

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

        .sidebar .logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .sidebar .logo img {
            width: 100px;
            margin-bottom: 8px;
        }

        .sidebar .logo p {
            margin: 0;
        }

        .sidebar .logo small {
            display: inline-block;
            margin-top: 5px;
            background-color: #006400;
            padding: 2px 6px;
            border-radius: 5px;
            color: white;
        }

        /* Adicionado para mensagens de erro/sucesso */
        .message-container { margin-bottom: 20px; padding: 10px; border-radius: 5px; }
        .message-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

<div class="sidebar">
    <div>
        <div class="user-profile">
            <img src="<?php echo htmlspecialchars($foto_perfil_para_exibir); ?>" alt="Foto de Perfil" id="fotoPerfil">
            <div class="edit-photo" id="btnAlterarFoto">Alterar foto</div>
            <h3><?php echo htmlspecialchars($nome_usuario_para_exibir); ?></h3>
        </div>
        <button onclick="window.location.href='Inicio.php'">
            <i class="fas fa-home"></i> Início
        </button>
        <button onclick="window.location.href='chat.php'">
            <i class="fas fa-comments"></i> Mensagens
        </button>
        <button><i class="fas fa-user-friends"></i> Contatos</button>
        <button onclick="window.location.href='lista_desaparecidos.php'">
            <i class="fas fa-list"></i> Lista de Desaparecidos
        </button>
        <button onclick="window.location.href='registrar_desaparecido.php'">
            <i class="fas fa-user-plus"></i> Registrar Desaparecimento
        </button>
        <button><i class="fa-solid fa-circle-user"></i> Comunidade</button>
        <button><i class="fas fa-database"></i> Dados e Ferramentas</button>
        <button><i class="fas fa-cogs"></i> Configurações</button>
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

<div class="main-content">
    <div class="form-container">
        <h2>Registrar Desaparecimento</h2>

        <?php if (!empty($success_message)): ?>
            <div class="message-container message-success">
                <p><?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="message-container message-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="registrar_desaparecido.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nome">Nome completo:</label>
                <input type="text" name="nome" id="nome" required value="<?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="nome_mae">Nome da Mãe:</label>
                <input type="text" name="nome_mae" id="nome_mae" value="<?php echo htmlspecialchars($nome_mae, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="nome_pai">Nome do Pai:</label>
                <input type="text" name="nome_pai" id="nome_pai" value="<?php echo htmlspecialchars($nome_pai, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="data_nascimento">Data de Nascimento:</label>
                <input type="date" name="data_nascimento" id="data_nascimento" value="<?php echo htmlspecialchars($data_nascimento ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="idade">Idade:</label>
                <input type="number" name="idade" id="idade" required value="<?php echo htmlspecialchars($idade ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="sexo">Sexo:</label>
                <select name="sexo" id="sexo" required>
                    <option value="">Selecione</option>
                    <option value="Masculino" <?php echo ($sexo === 'Masculino') ? 'selected' : ''; ?>>Masculino</option>
                    <option value="Feminino" <?php echo ($sexo === 'Feminino') ? 'selected' : ''; ?>>Feminino</option>
                    <option value="Outro" <?php echo ($sexo === 'Outro') ? 'selected' : ''; ?>>Outro</option>
                </select>
            </div>

            <div class="form-group">
                <label for="estado_civil">Estado Civil:</label>
                <input type="text" name="estado_civil" id="estado_civil" value="<?php echo htmlspecialchars($estado_civil, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="grau_instrucao">Grau de Instrução:</label>
                <input type="text" name="grau_instrucao" id="grau_instrucao" value="<?php echo htmlspecialchars($grau_instrucao, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="cpf">CPF:</label>
                <input type="text" name="cpf" id="cpf" pattern="\d{3}\.\d{3}\.\d{3}-\d{2}" placeholder="Ex: 123.456.789-00" value="<?php echo htmlspecialchars($cpf, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="identidade">Identidade (RG):</label>
                <input type="text" name="identidade" id="identidade" value="<?php echo htmlspecialchars($identidade, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="emissor_identidade">Órgão Emissor da Identidade:</label>
                <input type="text" name="emissor_identidade" id="emissor_identidade" value="<?php echo htmlspecialchars($emissor_identidade, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="telefone">Telefone para Contato:</label>
                <input type="tel" name="telefone" id="telefone" pattern="[0-9]{2} [0-9]{4,5}-[0-9]{4}" placeholder="Ex: 83 91234-5678" value="<?php echo htmlspecialchars($telefone, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="pais_nascimento">País de Nascimento:</label>
                <input type="text" name="pais_nascimento" id="pais_nascimento" value="<?php echo htmlspecialchars($pais_nascimento, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="uf_nascimento">UF de Nascimento:</label>
                <input type="text" name="uf_nascimento" id="uf_nascimento" maxlength="2" placeholder="Ex: PB" value="<?php echo htmlspecialchars($uf_nascimento, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="municipio_nascimento">Município de Nascimento:</label>
                <input type="text" name="municipio_nascimento" id="municipio_nascimento" value="<?php echo htmlspecialchars($municipio_nascimento, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="logradouro">Logradouro:</label>
                <input type="text" name="logradouro" id="logradouro" value="<?php echo htmlspecialchars($logradouro, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="numero">Número:</label>
                <input type="text" name="numero" id="numero" value="<?php echo htmlspecialchars($numero ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="complemento">Complemento:</label>
                <input type="text" name="complemento" id="complemento" value="<?php echo htmlspecialchars($complemento, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="cep">CEP:</label>
                <input type="text" name="cep" id="cep" pattern="\d{5}-\d{3}" placeholder="Ex: 58000-000" value="<?php echo htmlspecialchars($cep, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="bairro">Bairro:</label>
                <input type="text" name="bairro" id="bairro" value="<?php echo htmlspecialchars($bairro, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="meio_locomocao">Meio de Locomoção:</label>
                <input type="text" name="meio_locomocao" id="meio_locomocao" value="<?php echo htmlspecialchars($meio_locomocao, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="estava_acompanhada">Estava Acompanhada?</label>
                <select name="estava_acompanhada" id="estava_acompanhada" required>
                    <option value="">Selecione</option>
                    <option value="Sim" <?php echo ($estava_acompanhada === 'Sim') ? 'selected' : ''; ?>>Sim</option>
                    <option value="Não" <?php echo ($estava_acompanhada === 'Não') ? 'selected' : ''; ?>>Não</option>
                </select>
            </div>

            <div class="form-group">
                <label for="possui_bagagem">Possuía Bagagem?</label>
                <select name="possui_bagagem" id="possui_bagagem" required>
                    <option value="">Selecione</option>
                    <option value="Sim" <?php echo ($possui_bagagem === 'Sim') ? 'selected' : ''; ?>>Sim</option>
                    <option value="Não" <?php echo ($possui_bagagem === 'Não') ? 'selected' : ''; ?>>Não</option>
                </select>
            </div>
            <div class="form-group">
                <label for="cidade_ocorrido">Cidade onde ocorreu o desaparecimento:</label>
                <select name="cidade_ocorrido" id="cidade_ocorrido" required>
                    <option value="">Selecione a cidade</option>
                    <?php
                    foreach ($cidades_paraiba as $cidade) {
                        $selected = ($cidade_ocorrido === $cidade) ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($cidade, ENT_QUOTES, 'UTF-8') . '" ' . $selected . '>' . htmlspecialchars($cidade, ENT_QUOTES, 'UTF-8') . '</option>';
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="bairro_ocorrido">Bairro Ocorrido:</label>
                <input type="text" name="bairro_ocorrido" id="bairro_ocorrido" value="<?php echo htmlspecialchars($bairro_ocorrido, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="altura">Altura:</label>
                <input type="text" name="altura" id="altura" placeholder="Ex: 1.75m" value="<?php echo htmlspecialchars($altura, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="compleicao">Compleição:</label>
                <input type="text" name="compleicao" id="compleicao" value="<?php echo htmlspecialchars($compleicao, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="cutis">Cútis:</label>
                <input type="text" name="cutis" id="cutis" value="<?php echo htmlspecialchars($cutis, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="cabelo">Cabelo (Tipo):</label>
                <input type="text" name="cabelo" id="cabelo" value="<?php echo htmlspecialchars($cabelo, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="cor_cabelo">Cor do Cabelo:</label>
                <input type="text" name="cor_cabelo" id="cor_cabelo" value="<?php echo htmlspecialchars($cor_cabelo, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="olhos">Olhos (Formato):</label>
                <input type="text" name="olhos" id="olhos" value="<?php echo htmlspecialchars($olhos, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="cor_olhos">Cor dos Olhos:</label>
                <input type="text" name="cor_olhos" id="cor_olhos" value="<?php echo htmlspecialchars($cor_olhos, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="boca">Boca:</label>
                <input type="text" name="boca" id="boca" value="<?php echo htmlspecialchars($boca, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="labios">Lábios:</label>
                <input type="text" name="labios" id="labios" value="<?php echo htmlspecialchars($labios, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="rosto">Rosto:</label>
                <input type="text" name="rosto" id="rosto" value="<?php echo htmlspecialchars($rosto, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="testa">Testa:</label>
                <input type="text" name="testa" id="testa" value="<?php echo htmlspecialchars($testa, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="data_fato_ocorrido">Data do Fato Ocorrido:</label>
                <input type="date" name="data_fato_ocorrido" id="data_fato_ocorrido" value="<?php echo htmlspecialchars($data_fato_ocorrido ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="descricao_ocorrido">Descrição do Ocorrido:</label>
                <textarea name="descricao_ocorrido" id="descricao_ocorrido"><?php echo htmlspecialchars($descricao_ocorrido, ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="form-group">
                <label for="imagem">Foto do desaparecido:</label>
                <input type="file" name="imagem" id="imagem" accept="image/*">
            </div>

            <div class="form-group">
                <label for="descricao">Descrição adicional:</label>
                <textarea name="descricao" id="descricao"><?php echo htmlspecialchars($descricao_adicional, ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <button type="submit" name="acao">Registrar</button>
        </form>

    </div>
</div>

</body>
</html>