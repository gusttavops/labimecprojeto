<?php
session_start();

// Redireciona se o usuário não estiver logado
if (!isset($_SESSION['usuario']) || !isset($_SESSION['nome'])) {
    header("Location: login.php");
    exit;
}

$nome = htmlspecialchars($_SESSION['nome']);

// Inclui a conexão com o banco de dados
require_once __DIR__ . '/includes/db.php';

// Carregar foto do usuário e ID
$foto_perfil = 'https://i.postimg.cc/FsYtGz3V/perfil.jpg'; // Padrão
$user_id = null;

try {
    // Buscar o ID do usuário e a foto de perfil do banco de dados
    $stmt = $pdo->prepare("SELECT id, foto_perfil FROM usuarios WHERE usuario = ?");
    $stmt->execute([$_SESSION['usuario']]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_data) {
        $user_id = $user_data['id'];
        if (!empty($user_data['foto_perfil']) && file_exists($user_data['foto_perfil'])) {
            $foto_perfil = htmlspecialchars($user_data['foto_perfil']);
        }
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Sua sessão expirou ou usuário não encontrado. Por favor, faça login novamente.'];
        header("Location: login.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar dados do usuário: " . $e->getMessage());
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Ocorreu um erro ao carregar seus dados. Por favor, tente novamente.'];
    header("Location: login.php");
    exit;
}

// Lógica de upload de foto (mantida aqui para consistência com o modal da sidebar)
$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['nova_foto_modal'])) {
    if ($user_id === null) {
        $message = ['type' => 'error', 'text' => 'Erro: ID de usuário não disponível para upload.'];
    } elseif ($_FILES['nova_foto_modal']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['nova_foto_modal']['name'];
        $file_tmp_name = $_FILES['nova_foto_modal']['tmp_name'];
        $file_size = $_FILES['nova_foto_modal']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $max_file_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file_ext, $allowed_extensions)) {
            $message = ['type' => 'error', 'text' => 'Tipo de arquivo não permitido. Apenas JPG, JPEG, PNG e GIF são aceitos.'];
        } elseif ($file_size > $max_file_size) {
            $message = ['type' => 'error', 'text' => 'O arquivo é muito grande. O tamanho máximo permitido é 5MB.'];
        } else {
            $novo_nome = 'perfil_' . $user_id . '_' . time() . '.' . $file_ext;
            $destino = 'uploads/' . $novo_nome;

            // Antes de mover, exclua a foto antiga se existir e não for a padrão
            if ($user_data['foto_perfil'] && $user_data['foto_perfil'] != 'https://i.postimg.cc/FsYtGz3V/perfil.jpg' && file_exists($user_data['foto_perfil'])) {
                unlink($user_data['foto_perfil']);
            }

            if (move_uploaded_file($_FILES['nova_foto_modal']['tmp_name'], $destino)) {
                try {
                    $stmt = $pdo->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id = ?");
                    $stmt->execute([$destino, $user_id]);
                    $foto_perfil = htmlspecialchars($destino);
                    $message = ['type' => 'success', 'text' => 'Foto de perfil atualizada com sucesso!'];
                } catch (PDOException $e) {
                    error_log("Erro ao atualizar foto de perfil no DB: " . $e->getMessage());
                    $message = ['type' => 'error', 'text' => 'Erro ao salvar a foto no banco de dados.'];
                }
            } else {
                $message = ['type' => 'error', 'text' => 'Erro ao fazer upload da foto.'];
            }
        }
    } else {
        $message = ['type' => 'error', 'text' => 'Ocorreu um erro durante o upload: Código ' . $_FILES['nova_foto_modal']['error']];
    }
}

// Flash message check
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Clear the message after displaying
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Estatísticas - Sumidos PB</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <style>
        /* Variáveis CSS para cores */
        :root {
            --cor-primaria: #006400; /* Verde escuro */
            --cor-secundaria: #004d00; /* Verde mais escuro */
            --cor-texto-claro: #fff;
            --cor-texto-escuro: #333;
            --cor-fundo: #f5f5f5;
            --cor-card-fundo: #fff;
            --cor-destaque-verde: #007bff; /* Azul para gráficos, como na imagem */
            --cor-destaque-vermelho: #dc3545;
            --cor-destaque-amarelo: #ffcc00; /* Amarelo para variação negativa */
            --cor-sombra: rgba(0,0,0,0.08);
            --transicao-padrao: 0.3s ease;
        }

        /* Reset e base */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            display: flex;
            min-height: 100vh;
            color: var(--cor-texto-escuro);
            background-color: var(--cor-fundo);
            line-height: 1.6;
        }

        /* Sidebar */
        .sidebar {
            background-color: var(--cor-texto-escuro); /* Cor preta */
            color: var(--cor-texto-claro);
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
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.3); /* Sombra mais forte */
        }
        .user-profile { text-align: center; margin-bottom: 20px; display: flex; flex-direction: column; align-items: center; }
        .user-profile img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid var(--cor-primaria); /* Borda verde */ }
        .edit-photo {
            background-color: var(--cor-primaria);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            cursor: pointer;
            color: var(--cor-texto-claro);
            font-weight: 500;
            margin-top: 12px;
            transition: background-color var(--transicao-padrao);
        }
        .edit-photo:hover { background-color: var(--cor-secundaria); }
        .user-profile h3 { margin-top: 10px; font-size: 1.1em; font-weight: 600; color: #eee; }

        .sidebar button {
            width: 85%; /* Um pouco mais largo */
            padding: 12px;
            margin: 8px auto; /* Espaçamento ajustado */
            background-color: var(--cor-secundaria); /* Verde mais escuro para botões */
            border: none;
            color: var(--cor-texto-claro);
            cursor: pointer;
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: flex-start; /* Alinhar texto e ícone à esquerda */
            gap: 12px; /* Espaçamento entre ícone e texto */
            font-size: 15px;
            font-weight: 500;
            transition: background-color var(--transicao-padrao), transform 0.2s ease;
        }
        .sidebar button:hover {
            background-color: var(--cor-primaria);
            transform: translateX(5px); /* Efeito de deslize ao passar o mouse */
        }
        .sidebar button i {
            font-size: 18px; /* Ícones um pouco maiores */
            width: 25px; /* Largura fixa para alinhar ícones */
            text-align: center;
        }

        .sidebar .logo { text-align: center; margin-top: 20px; padding-bottom: 10px; }
        .sidebar .logo img { width: 90px; margin-bottom: 5px; } /* Logo um pouco menor */
        .sidebar .logo p { margin: 0; font-size: 1.2em; font-weight: 700; color: var(--cor-texto-claro); }
        .sidebar .logo small { display: inline-block; margin-top: 5px; background-color: var(--cor-primaria); padding: 3px 8px; border-radius: 5px; color: white; font-size: 0.7em; font-weight: 500; }

        /* Main Content Area */
        .main {
            margin-left: 251px;
            width: calc(100% - 251px);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            position: relative; /* Para posicionamento das mensagens */
        }
        .header {
            background-color: var(--cor-secundaria);
            color: var(--cor-texto-claro);
            padding: 15px 25px;
            font-size: 1.3em;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .user-info-header {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--cor-texto-claro);
            font-size: 0.9em;
        }
        .user-info-header img {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--cor-texto-claro);
        }

        /* Mensagens de feedback */
        .message {
            padding: 12px 20px;
            margin: 15px 25px 0 25px; /* Ajuste para centralizar no main content */
            border-radius: 8px;
            font-weight: 500;
            z-index: 10;
            position: relative;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* Header Governo Federal */
        .gov-header {
            background-color: #1a4d1a;
            color: var(--cor-texto-claro);
            padding: 5px 25px;
            font-size: 0.75em;
            text-align: right;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 5px;
        }
        .gov-header span { font-weight: 300; } /* Fonte um pouco mais fina */

        /* Seção de Filtros */
        .filters-container-wrapper {
            background-color: var(--cor-secundaria); /* Mais escuro */
            padding: 15px 25px; /* Mais preenchimento */
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--cor-texto-claro);
            flex-wrap: wrap;
            gap: 15px; /* Mais espaço entre os elementos */
            position: sticky;
            top: 68px; /* Abaixo do header principal */
            z-index: 998;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .filters-left, .filters-right {
            display: flex;
            align-items: center;
            gap: 20px; /* Mais espaçamento entre filtros */
            flex-wrap: wrap;
        }
        .filters-container-wrapper label {
            font-weight: 500;
            white-space: nowrap;
            font-size: 0.9em;
        }
        .filters-container-wrapper select {
            padding: 9px 15px; /* Mais padding */
            border-radius: 8px; /* Mais arredondado */
            border: 1px solid #005a00; /* Borda mais escura */
            font-size: 0.9em;
            background-color: #113a11; /* Fundo do select mais escuro */
            color: var(--cor-texto-claro);
            appearance: none;
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20256%20512%22%3E%3Cpath%20fill%3D%22%23ffffff%22%20d%3D%22M192%20256l-64-64-64%2064h128z%22%2F%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 14px;
            cursor: pointer;
            transition: background-color var(--transicao-padrao), border-color var(--transicao-padrao);
        }
        .filters-container-wrapper select:hover { background-color: #1a4d1a; border-color: #007000; }
        .filters-container-wrapper select option { background-color: #003300; color: var(--cor-texto-claro); }

        .filters-container-wrapper button {
            background-color: var(--cor-destaque-vermelho);
            color: var(--cor-texto-claro);
            border: none;
            padding: 9px 18px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: 600;
            transition: background-color var(--transicao-padrao);
        }
        .filters-container-wrapper button:hover { background-color: #c82333; }
        .flag-icon { width: 35px; height: auto; border: 1px solid rgba(255,255,255,0.3); border-radius: 3px; }

        /* Títulos do Dashboard */
        .dashboard-main-title {
            text-align: center;
            font-size: 2.2em;
            color: var(--cor-secundaria);
            margin: 30px 0 10px 0;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }
        .dashboard-sub-title {
            text-align: center;
            font-size: 0.95em;
            color: #666;
            margin-bottom: 30px;
            font-weight: 400;
        }

        /* Área de Conteúdo - Grid Responsivo */
        .content-area {
            background-color: var(--cor-fundo);
            padding: 25px; /* Mais preenchimento */
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); /* Mais flexível */
            gap: 25px; /* Mais espaçamento entre cards */
            flex-grow: 1;
            align-items: start; /* Alinha os itens no topo */
        }

        /* Agrupamentos de Cards */
        .card-group-main {
            display: grid;
            grid-template-columns: 1fr;
            gap: 25px;
        }
        .card-group-chart {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); /* Gráficos maiores responsivos */
            gap: 25px;
        }
        .card-col-uf-sexo, .card-col-idade-cta {
            display: grid;
            grid-template-columns: 1fr;
            gap: 25px;
        }

        /* Media Queries para Layout em Telas Maiores (Desktop) */
        @media (min-width: 992px) {
            .content-area {
                grid-template-columns: 0.8fr 1.5fr 1fr; /* Três colunas com proporções específicas */
            }
            .card-group-main {
                grid-column: 1; /* Primeira coluna */
                grid-row: 1 / span 2; /* Ocupa duas linhas */
            }
            .card-group-chart {
                grid-column: 2 / span 2; /* Ocupa as duas colunas restantes da primeira linha */
                grid-template-columns: 1.2fr 0.8fr; /* Subgrid para os dois gráficos principais */
            }
            .card-col-uf-sexo {
                grid-column: 2; /* Segunda coluna, segunda linha */
            }
            .card-col-idade-cta {
                grid-column: 3; /* Terceira coluna, segunda linha */
            }
            .card.chart-card[style*="grid-column: 1 / -1;"] { /* Gráfico do mês em tela cheia na parte inferior */
                grid-column: 1 / -1; /* Ocupa todas as colunas */
            }
        }

        @media (min-width: 1200px) { /* Ajuste para telas muito grandes */
            .content-area {
                gap: 30px;
                padding: 30px;
            }
            .card-group-main, .card-group-chart, .card-col-uf-sexo, .card-col-idade-cta {
                gap: 30px;
            }
        }
        
        /* Media Query para Telas Menores (Mobile) */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative; /* Volta para fluxo normal para mobile */
                box-shadow: 0 2px 8px rgba(0,0,0,0.15);
                padding-bottom: 20px;
            }
            .sidebar .logo { margin-bottom: 10px; }
            .sidebar button { width: 90%; font-size: 14px; padding: 10px; }
            .user-profile { margin-top: 20px; }

            .main {
                margin-left: 0;
                width: 100%;
            }
            .header { font-size: 1.1em; padding: 12px 20px; text-align: center; justify-content: center; }
            .user-info-header { margin-top: 10px; justify-content: center; width: 100%; }

            .filters-container-wrapper {
                flex-direction: column;
                align-items: flex-start;
                padding: 15px 20px;
                top: 55px; /* Ajuste para o header menor */
            }
            .filters-left, .filters-right {
                width: 100%;
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .filters-container-wrapper select,
            .filters-container-wrapper button {
                width: 100%;
            }
            .flag-icon { display: none; } /* Oculta a bandeira no mobile */

            .dashboard-main-title { font-size: 1.8em; margin-top: 20px; }
            .dashboard-sub-title { font-size: 0.85em; margin-bottom: 20px; }

            .content-area {
                padding: 15px;
                grid-template-columns: 1fr; /* Uma coluna no mobile */
                gap: 15px;
            }
            .card-group-chart {
                grid-template-columns: 1fr; /* Uma coluna no mobile */
            }
            .card { padding: 20px; }
            .card.main-indicator-card .value { font-size: 42px; }
            .card.main-indicator-card .value.red-percent,
            .card.main-indicator-card .value.green-percent { font-size: 28px; }

            .message { margin: 15px 20px 0 20px; }
        }


        /* Estilo dos Cards */
        .card {
            background-color: var(--cor-card-fundo);
            border-radius: 15px; /* Mais arredondado */
            box-shadow: 0 6px 20px var(--cor-sombra); /* Sombra mais suave e pronunciada */
            padding: 25px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            min-height: 160px; /* Altura mínima consistente */
            transition: transform var(--transicao-padrao), box-shadow var(--transicao-padrao);
            position: relative; /* Para loading spinner */
            overflow: hidden; /* Garante que o conteúdo não vaze */
        }
        .card:hover { transform: translateY(-8px); box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
        .card h3 {
            font-size: 1.1em;
            color: var(--cor-secundaria);
            margin-bottom: 12px;
            font-weight: 600;
        }
        .card .value {
            font-size: 3.2em; /* Tamanho maior */
            font-weight: 700;
            color: var(--cor-primaria);
            margin-bottom: 8px;
            line-height: 1.1;
            text-shadow: 0 1px 2px rgba(0,0,0,0.05); /* Leve sombra no texto */
        }
        .card .value.red { color: var(--cor-destaque-vermelho); }
        .card .description {
            font-size: 0.85em;
            color: #777;
            line-height: 1.5;
            font-weight: 400;
        }
        .card .icon {
            font-size: 45px;
            color: var(--cor-primaria);
            margin-bottom: 15px;
        }

        /* Estilos específicos para os cards da primeira coluna (principais) */
        .card.main-indicator-card {
            background: linear-gradient(145deg, var(--cor-primaria), var(--cor-secundaria)); /* Gradiente */
            color: var(--cor-texto-claro);
            padding: 30px 25px;
            justify-content: center;
            align-items: flex-start;
            text-align: left;
            min-height: 180px; /* Altura mínima um pouco maior */
            box-shadow: 0 8px 25px rgba(0,0,0,0.2); /* Sombra mais intensa */
        }
        .card.main-indicator-card h3 {
            color: var(--cor-texto-claro);
            font-size: 1.2em;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .card.main-indicator-card .value {
            color: var(--cor-texto-claro);
            font-size: 4.5em; /* Valor principal ainda maior */
            margin-bottom: 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .card.main-indicator-card .description {
            color: rgba(255,255,255,0.9);
            font-size: 0.9em;
            margin-top: 8px;
            font-weight: 300;
        }
        .card.main-indicator-card .value.red-percent {
            color: var(--cor-destaque-amarelo); /* Amarelo */
            font-size: 2.5em;
            font-weight: 700;
        }
        .card.main-indicator-card .value.green-percent {
            color: #ccffcc; /* Verde claro */
            font-size: 2.5em;
            font-weight: 700;
        }
        .card.main-indicator-card .percentage-indicator {
            display: flex;
            align-items: center;
            font-size: 0.9em;
            font-weight: 600;
            margin-top: 15px;
        }
        .card.main-indicator-card .percentage-indicator i {
            margin-right: 8px;
            font-size: 1.1em;
            filter: drop-shadow(0 1px 1px rgba(0,0,0,0.2));
        }
        .card.main-indicator-card .percentage-indicator.up { color: #ccffcc; }
        .card.main-indicator-card .percentage-indicator.down { color: var(--cor-destaque-amarelo); }

        /* Estilo para o mapa (placeholder) */
        .card.map-card {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 280px; /* Altura ajustada */
        }
        .card.map-card img {
            max-width: 90%;
            height: auto;
            margin-top: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        /* Estilo dos gráficos */
        .card.chart-card {
            min-height: 380px; /* Altura maior para gráficos */
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            padding: 25px;
            position: relative; /* Para o spinner de carregamento */
        }
        .chart-card canvas {
            max-width: 100%;
            flex-grow: 1; /* Ocupa o espaço disponível */
            margin-top: 15px;
        }
        .chart-card .description {
            margin-top: 10px;
            font-style: italic;
            font-size: 0.75em;
            text-align: center;
            color: #888;
        }

        /* Botão no card de Call to Action */
        .card .btn-saiba-mais {
            background-color: var(--cor-primaria);
            color: var(--cor-texto-claro);
            padding: 14px 30px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1em;
            margin-top: 25px;
            transition: background-color var(--transicao-padrao), transform 0.2s ease;
            box-shadow: 0 4px 10px rgba(0,100,0,0.2);
        }
        .card .btn-saiba-mais:hover {
            background-color: var(--cor-secundaria);
            transform: translateY(-3px);
        }

        /* Modal para alterar foto */
        .modal-foto-bg {
            display: none;
            position: fixed;
            z-index: 99999;
            left: 0; top: 0; width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.7);
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(8px);
        }
        .modal-foto {
            background: var(--cor-card-fundo);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            position: relative;
            min-width: 320px;
            max-width: 90%;
            box-shadow: 0 8px 20px rgba(0,0,0,0.4);
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .modal-foto .close-modal {
            position: absolute;
            right: 15px; top: 10px;
            font-size: 28px;
            color: #888;
            cursor: pointer;
            transition: color var(--transicao-padrao);
        }
        .modal-foto .close-modal:hover { color: var(--cor-destaque-vermelho); }
        .modal-foto label {
            font-weight: 600;
            display: block;
            margin-bottom: 20px;
            font-size: 1.1em;
            color: var(--cor-texto-escuro);
        }
        .modal-foto input[type="file"] {
            margin-bottom: 25px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            width: calc(100% - 20px);
            font-size: 0.9em;
            background-color: #fcfcfc;
        }
        .modal-foto button[type="submit"] {
            background-color: var(--cor-primaria);
            color: var(--cor-texto-claro);
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1em;
            transition: background-color var(--transicao-padrao), transform 0.2s ease;
        }
        .modal-foto button[type="submit"]:hover { background-color: var(--cor-secundaria); transform: translateY(-2px); }

        /* Loading Spinner */
        .loading-spinner {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10;
            border-radius: 15px;
            flex-direction: column;
            gap: 10px;
            font-weight: 500;
            color: #555;
            transition: opacity 0.3s ease;
        }
        .loading-spinner.hidden {
            opacity: 0;
            pointer-events: none;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--cor-primaria);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error-message-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 11;
            border-radius: 15px;
            text-align: center;
            padding: 20px;
            color: var(--cor-destaque-vermelho);
            font-weight: 600;
        }
        .error-message-overlay .fa-exclamation-triangle {
            font-size: 4em;
            margin-bottom: 20px;
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
        <button onclick="window.location.href='painel_estatisticas.php'">
            <i class="fas fa-chart-bar"></i> Estatísticas
        </button>
        <button onclick="window.location.href='dados_e_ferramentas.php'">
            <i class="fas fa-database"></i> Dados e Ferramentas
        </button>
        <button onclick="window.location.href='configuracoes.php'"> <i class="fas fa-cogs"></i> Configurações
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

<div class="main">
    <div class="header">
        Painel de Estatísticas
        <div class="user-info-header">
            Olá, <?php echo htmlspecialchars($nome); ?>!
            <img src="<?php echo htmlspecialchars($foto_perfil); ?>" alt="Foto de Perfil">
        </div>
    </div>

    <?php if ($message): ?>
        <div class="message <?php echo $message['type']; ?>">
            <?php echo htmlspecialchars($message['text']); ?>
        </div>
        <script>
            // Remove a mensagem após 5 segundos
            setTimeout(function() {
                const messageElement = document.querySelector('.message');
                if (messageElement) {
                    messageElement.style.opacity = '0';
                    setTimeout(() => messageElement.remove(), 500); // Remove completamente após a transição
                }
            }, 5000);
        </script>
    <?php endif; ?>

    <div class="gov-header">
        <span>Fonte: Dados informados pelos Estados e Distrito Federal.</span>
        MINISTÉRIO DA JUSTIÇA E SEGURANÇA PÚBLICA &nbsp; | &nbsp; GOVERNO FEDERAL
    </div>

    <div class="filters-container-wrapper">
        <div class="filters-left">
            <label for="selectTipo">Selecione o Indicador</label>
            <select id="selectTipo">
                <option value="desaparecidos" selected>Pessoas Desaparecidas</option>
                <option value="localizados">Pessoas Localizadas</option>
            </select>

            <label for="selectAno">Ano</label>
            <select id="selectAno">
                <?php
                $currentYear = date('Y');
                for ($i = $currentYear; $i >= 2000; $i--) { // Ex: de 2025 até 2000
                    echo "<option value='{$i}'" . ($i == $currentYear ? " selected" : "") . ">{$i}</option>";
                }
                ?>
            </select>

            <label for="selectMes">Mês</label>
            <select id="selectMes">
                <option value="Todos" selected>Todos</option>
                <option value="Janeiro">Janeiro</option>
                <option value="Fevereiro">Fevereiro</option>
                <option value="Março">Março</option>
                <option value="Abril">Abril</option>
                <option value="Maio">Maio</option>
                <option value="Junho">Junho</option>
                <option value="Julho">Julho</option>
                <option value="Agosto">Agosto</option>
                <option value="Setembro">Setembro</option>
                <option value="Outubro">Outubro</option>
                <option value="Novembro">Novembro</option>
                <option value="Dezembro">Dezembro</option>
            </select>

            <label for="selectRegiao">Região/Estado</label>
            <select id="selectRegiao">
                <option value="Todos" selected>Todos</option>
                <option value="AC">AC</option> <option value="AL">AL</option> <option value="AM">AM</option>
                <option value="AP">AP</option> <option value="BA">BA</option> <option value="CE">CE</option>
                <option value="DF">DF</option> <option value="ES">ES</option> <option value="GO">GO</option>
                <option value="MA">MA</option> <option value="MG">MG</option> <option value="MS">MS</option>
                <option value="MT">MT</option> <option value="PA">PA</option> <option value="PB">PB</option>
                <option value="PE">PE</option> <option value="PI">PI</option> <option value="PR">PR</option>
                <option value="RJ">RJ</option> <option value="RN">RN</option> <option value="RO">RO</option>
                <option value="RR">RR</option> <option value="RS">RS</option> <option value="SC">SC</option>
                <option value="SE">SE</option> <option value="SP">SP</option> <option value="TO">TO</option>
            </select>
        </div>
        <div class="filters-right">
            <img src="https://i.postimg.cc/QtxK3z93/bandeira-brasil.png" alt="Bandeira do Brasil" class="flag-icon">
            <button id="limparFiltrosBtn">Limpar Filtros</button>
        </div>
    </div>

    <h2 class="dashboard-main-title" id="dashboardMainTitle">PESSOAS DESAPARECIDAS E LOCALIZADAS</h2>
    <p class="dashboard-sub-title" id="dashboardSubTitle">Visão geral dos dados de <span id="currentYearSubtitle"><?php echo date('Y'); ?></span> até o momento</p>

    <div class="content-area">
        <div class="card-group-main">
            <div class="card main-indicator-card" id="cardTotalPessoas">
                <div class="loading-spinner hidden"><div class="spinner"></div>Carregando...</div>
                <h3>Total de Pessoas <span id="cardTotalTitle">Desaparecidas</span></h3>
                <div class="value" id="totalPessoasValue">0</div>
                <p class="description" id="totalPessoasDescription">Dados Nacionais (Estimativa <span id="totalAnoDesc">2025</span>)</p>
            </div>

            <div class="card main-indicator-card" id="cardPessoasPorDia">
                <div class="loading-spinner hidden"><div class="spinner"></div>Carregando...</div>
                <h3>Pessoas <span id="cardDiariaTitle">Desaparecidas</span> por Dia</h3>
                <div class="value" id="pessoasPorDiaValue">0</div>
                <p class="description" id="pessoasPorDiaDescription">Média diária (<span id="mediaAnoDesc">2025</span>)</p>
            </div>

            <div class="card main-indicator-card" id="cardVariacao">
                <div class="loading-spinner hidden"><div class="spinner"></div>Carregando...</div>
                <h3>Variação <span id="variacaoAnos">2024/2025</span></h3>
                <div class="value" id="variacaoPercentualValue">0,00%</div>
                <p class="description" id="variacaoPeriodoDesc">(Janeiro a Maio)</p>
                <div class="percentage-indicator" id="variacaoIcon">
                    </div>
            </div>
        </div>

        <div class="card-group-chart">
            <div class="card map-card" id="cardTaxa100Mil">
                <div class="loading-spinner hidden"><div class="spinner"></div>Carregando...</div>
                <h3>Taxa por 100 mil hab. por UF (<span id="taxaAno">2025</span>)</h3>
                <img src="https://i.postimg.cc/d1521hL0/mapa-brasil-placeholder.png" alt="Mapa do Brasil" id="mapaBrasil">
                <div class="value" id="taxa100MilValue">0,00</div>
                <p class="description" id="taxa100MilDescription">Taxa por 100 mil hab.</p>
            </div>

            <div class="card chart-card" id="chartCardAno">
                <div class="loading-spinner hidden"><div class="spinner"></div>Carregando...</div>
                <div class="error-message-overlay hidden" id="errorChartAno">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Dados insuficientes para este gráfico.</p>
                </div>
                <h3>Pessoas <span id="chartAnoTitle">Desaparecidas</span> por Ano</h3>
                <canvas id="chartDesaparecidosAno"></canvas>
                <p class="description" id="chartAnoDescription">*<span id="anoAtualizadoDesc">2025</span>: Janeiro a Maio</p>
            </div>
        </div>

        <div class="card-col-uf-sexo">
            <div class="card chart-card" id="chartCardUF">
                <div class="loading-spinner hidden"><div class="spinner"></div>Carregando...</div>
                <div class="error-message-overlay hidden" id="errorChartUF">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Dados insuficientes para este gráfico.</p>
                </div>
                <h3>Pessoas <span id="chartUFTitle">Desaparecidas</span> por UF</h3>
                <canvas id="chartDesaparecidosUF"></canvas>
            </div>

            <div class="card chart-card" id="chartCardSexo">
                <div class="loading-spinner hidden"><div class="spinner"></div>Carregando...</div>
                <div class="error-message-overlay hidden" id="errorChartSexo">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Dados insuficientes para este gráfico.</p>
                </div>
                <h3>Pessoas <span id="chartSexoTitle">Desaparecidas</span> por Sexo</h3>
                <canvas id="chartDesaparecidosSexo"></canvas>
            </div>
        </div>

        <div class="card-col-idade-cta">
            <div class="card chart-card" id="chartCardIdade">
                <div class="loading-spinner hidden"><div class="spinner"></div>Carregando...</div>
                <div class="error-message-overlay hidden" id="errorChartIdade">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Dados insuficientes para este gráfico.</p>
                </div>
                <h3>Pessoas <span id="chartIdadeTitle">Desaparecidas</span> por Faixa Etária</h3>
                <canvas id="chartDesaparecidosIdade"></canvas>
            </div>

            <div class="card">
                <h3>Acesse o Site da Política de Desaparecidos</h3>
                <p class="description">Conheça as diretrizes e ações de nosso projeto.</p>
                <button class="btn-saiba-mais" onclick="window.location.href='politica_desaparecidos.php'">Saiba Mais</button>
            </div>
        </div>

        <div class="card chart-card" style="grid-column: 1 / -1;" id="chartCardMes">
            <div class="loading-spinner hidden"><div class="spinner"></div>Carregando...</div>
            <div class="error-message-overlay hidden" id="errorChartMes">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Dados insuficientes para este gráfico.</p>
            </div>
            <h3>Pessoas <span id="chartMesTitle">Desaparecidas</span> por Mês</h3>
            <canvas id="chartDesaparecidosMes"></canvas>
            <p class="description" id="chartMesDescription">Com relação ao mês de Maio de 2025, na data da extração, a UFJ-RJ não havia enviado seus dados por completo, conforme Nota 04.</p>
        </div>
    </div>
</div>

<div class="modal-foto-bg" id="modalAlterarFoto">
    <div class="modal-foto">
        <span class="close-modal" id="closeModal">×</span>
        <form method="post" enctype="multipart/form-data" action="painel_estatisticas.php">
            <label for="inputFoto">Escolher nova foto:</label>
            <input type="file" name="nova_foto_modal" id="inputFoto" accept="image/*" required>
            <button type="submit">Salvar Foto</button>
        </form>
    </div>
</div>

<script>
    // Registra o plugin Datalabels globalmente
    Chart.register(ChartDataLabels);

    // JavaScript para abrir/fechar o modal de foto de perfil
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

    // --- Chart.js Instances ---
    let chartMes, chartUF, chartSexo, chartIdade, chartAno;

    const cardsToLoad = [
        'cardTotalPessoas', 'cardPessoasPorDia', 'cardVariacao',
        'cardTaxa100Mil', 'chartCardAno', 'chartCardUF',
        'chartCardSexo', 'chartCardIdade', 'chartCardMes'
    ];

    function showLoading() {
        cardsToLoad.forEach(id => {
            const card = document.getElementById(id);
            if (card) {
                const spinner = card.querySelector('.loading-spinner');
                const errorOverlay = card.querySelector('.error-message-overlay');
                if (spinner) spinner.classList.remove('hidden');
                if (errorOverlay) errorOverlay.classList.add('hidden'); // Esconde erro ao carregar
            }
        });
    }

    function hideLoading() {
        cardsToLoad.forEach(id => {
            const card = document.getElementById(id);
            if (card) {
                const spinner = card.querySelector('.loading-spinner');
                if (spinner) spinner.classList.add('hidden');
            }
        });
    }

    function showChartError(cardId, message = "Dados insuficientes para este gráfico.") {
        const card = document.getElementById(cardId);
        if (card) {
            const errorOverlay = card.querySelector('.error-message-overlay');
            if (errorOverlay) {
                errorOverlay.querySelector('p').textContent = message;
                errorOverlay.classList.remove('hidden');
            }
        }
    }

    function hideChartError(cardId) {
        const card = document.getElementById(cardId);
        if (card) {
            const errorOverlay = card.querySelector('.error-message-overlay');
            if (errorOverlay) {
                errorOverlay.classList.add('hidden');
            }
        }
    }


    // Função para inicializar/atualizar gráficos e indicadores
    async function updateDashboard() {
        showLoading(); // Mostra spinners de carregamento

        const tipo = document.getElementById('selectTipo').value;
        const ano = document.getElementById('selectAno').value;
        // const mes = document.getElementById('selectMes').value; // Não usado para filtrar dados da API Flask ainda
        // const regiao = document.getElementById('selectRegiao').value; // Não usado para filtrar dados da API Flask ainda

        // Atualiza títulos baseados no tipo selecionado
        const tipoText = tipo === 'desaparecidos' ? 'Desaparecidas' : 'Localizadas';
        document.getElementById('dashboardMainTitle').textContent = `PESSOAS ${tipoText.toUpperCase()} E LOCALIZADAS`;
        document.getElementById('currentYearSubtitle').textContent = ano; // Atualiza o ano no subtítulo
        document.getElementById('cardTotalTitle').textContent = tipoText;
        document.getElementById('cardDiariaTitle').textContent = tipoText;
        document.getElementById('chartAnoTitle').textContent = tipoText;
        document.getElementById('chartMesTitle').textContent = tipoText;
        document.getElementById('chartUFTitle').textContent = tipoText;
        document.getElementById('chartSexoTitle').textContent = tipoText;
        document.getElementById('chartIdadeTitle').textContent = tipoText;
        document.getElementById('totalAnoDesc').textContent = ano;
        document.getElementById('mediaAnoDesc').textContent = ano;
        document.getElementById('taxaAno').textContent = ano;
        document.getElementById('anoAtualizadoDesc').textContent = ano;


        // Fetch data from Flask backend
        const apiUrl = `http://127.0.0.1:5000/api/dashboard_data?tipo=${tipo}&ano=${ano}`;
        let data = {};
        try {
            const response = await fetch(apiUrl);
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status} - ${errorText}`);
            }
            data = await response.json();
            console.log("Dados recebidos do Flask:", data); // Para depuração
        } catch (error) {
            console.error("Erro ao buscar dados do dashboard:", error);
            alert("Não foi possível carregar os dados. Verifique se o servidor Flask está rodando e se a URL está correta. Detalhes: " + error.message);
            // Define dados vazios para evitar quebras nos gráficos
            data = {
                total_geral: 0,
                pessoas_por_dia: 0,
                variacao_percentual: null,
                taxa_100mil: 0,
                data_mes: {},
                data_uf: {},
                data_sexo: {},
                data_idade: {},
            };
        } finally {
            hideLoading(); // Esconde spinners de carregamento, mesmo em caso de erro
        }


        // Update indicator cards
        document.getElementById('totalPessoasValue').textContent = data.total_geral.toLocaleString('pt-BR');
        document.getElementById('pessoasPorDiaValue').textContent = data.pessoas_por_dia.toLocaleString('pt-BR');
        document.getElementById('taxa100MilValue').textContent = data.taxa_100mil.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        // Atualizar Variação Percentual
        const variacaoPercentualValue = document.getElementById('variacaoPercentualValue');
        const variacaoIcon = document.getElementById('variacaoIcon');
        variacaoPercentualValue.className = 'value'; // Reseta classes de cor
        variacaoIcon.innerHTML = ''; // Limpa ícones anteriores
        variacaoIcon.className = 'percentage-indicator'; // Reseta classes de ícone

        if (data.variacao_percentual !== null && data.variacao_percentual !== undefined) {
            const formattedPercentage = data.variacao_percentual.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '%';
            variacaoPercentualValue.textContent = formattedPercentage;

            if (data.variacao_percentual > 0) {
                variacaoIcon.innerHTML = '<i class="fas fa-arrow-up"></i>';
                variacaoIcon.classList.add('up');
                variacaoPercentualValue.classList.add('green-percent');
            } else if (data.variacao_percentual < 0) {
                variacaoIcon.innerHTML = '<i class="fas fa-arrow-down"></i>';
                variacaoIcon.classList.add('down');
                variacaoPercentualValue.classList.add('red-percent');
            } else {
                // Se for 0, mantém o texto, mas sem ícone de variação
            }
            document.getElementById('variacaoAnos').textContent = `2024/${ano}`;
            document.getElementById('variacaoPeriodoDesc').textContent = '(Janeiro a Maio)'; // Fixado como na imagem
        } else {
            variacaoPercentualValue.textContent = 'N/A';
            document.getElementById('variacaoAnos').textContent = '';
            document.getElementById('variacaoPeriodoDesc').textContent = '';
        }

        // Common chart options for all charts
        const commonChartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                datalabels: { // Configuração do plugin ChartDataLabels
                    color: '#444',
                    anchor: 'end',
                    align: 'end',
                    font: {
                        weight: 'bold',
                        size: 10
                    },
                    formatter: function(value) {
                        return value.toLocaleString('pt-BR'); // Formata os valores nos gráficos
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('pt-BR'); // Formata números do eixo Y
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)' // Linhas de grade mais suaves
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                }
            },
            animation: { // Animações suaves ao atualizar
                duration: 1000,
                easing: 'easeOutQuart'
            }
        };

        // Função auxiliar para criar/atualizar gráficos
        function createOrUpdateChart(chartVar, ctxId, type, labels, datasetData, colors, options = {}) {
            const ctx = document.getElementById(ctxId).getContext('2d');
            if (chartVar) chartVar.destroy(); // Destroi a instância anterior do gráfico

            // Verifica se há dados válidos para o gráfico
            const hasData = datasetData && datasetData.some(val => val > 0);
            if (!hasData && labels.length > 0) { // Se não houver dados, mostra erro
                showChartError(ctxId.replace('canvas', 'Card'), "Dados insuficientes para este gráfico.");
                return null;
            } else {
                hideChartError(ctxId.replace('canvas', 'Card'));
            }

            chartVar = new Chart(ctx, {
                type: type,
                data: {
                    labels: labels,
                    datasets: [{
                        label: tipoText,
                        data: datasetData,
                        backgroundColor: colors.backgroundColor || 'rgba(0, 123, 255, 0.7)',
                        borderColor: colors.borderColor || 'rgba(0, 123, 255, 1)',
                        borderWidth: 1,
                        fill: colors.fill || false,
                        tension: colors.tension || 0.4 // Para gráficos de linha
                    }]
                },
                options: {
                    ...commonChartOptions,
                    ...options,
                    plugins: {
                        ...commonChartOptions.plugins,
                        ...options.plugins
                    }
                }
            });
            return chartVar;
        }


        // Gráfico de Pessoas por Ano (Dados fixos do Flask, mas ajustado para o tipo)
        // Os dados de ano no Flask são independentes da seleção de 'tipo' para este gráfico específico
        // Replicando a lógica do Flask para obter os dados do ano com base no tipo
        const dataAnoChartRaw = {
            'desaparecidos': {
                '2020': 28000, '2021': 30000, '2022': 31500,
                '2023': 32500, '2024': 33400, '2025': 34784
            },
            'localizados': {
                '2020': 100, '2021': 150, '2022': 200,
                '2023': 250, '2024': 370, '2025': 289
            }
        };
        const labelsAno = Object.keys(dataAnoChartRaw[tipo]);
        const valuesAno = Object.values(dataAnoChartRaw[tipo]);

        chartAno = createOrUpdateChart(chartAno, 'chartDesaparecidosAno', 'bar', labelsAno, valuesAno, {
            backgroundColor: '#28a745', // Verde para o gráfico de ano
            borderColor: '#218838'
        }, {
            scales: {
                x: { title: { display: true, text: 'Ano' } }
            }
        });


        // Gráfico de Pessoas Desaparecidas/Localizadas por Mês
        chartMes = createOrUpdateChart(chartMes, 'chartDesaparecidosMes', 'line',
            Object.keys(data.data_mes || {}),
            Object.values(data.data_mes || {}),
            {
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                fill: true,
                tension: 0.4
            },
            {
                scales: {
                    y: { title: { display: true, text: `Número de ${tipoText}` } },
                    x: { title: { display: true, text: 'Mês' } }
                }
            }
        );

        // Gráfico de Pessoas Desaparecidas/Localizadas por UF
        chartUF = createOrUpdateChart(chartUF, 'chartDesaparecidosUF', 'bar',
            Object.keys(data.data_uf || {}),
            Object.values(data.data_uf || {}),
            {
                backgroundColor: '#007bff',
                borderColor: '#006cfa'
            },
            {
                indexAxis: 'y', // Barras horizontais
                scales: {
                    x: { title: { display: true, text: `Número de ${tipoText}` } },
                    y: { title: { display: true, text: 'Estado (UF)' } }
                },
                plugins: {
                    datalabels: { // Esconder datalabels em muitas barras (opcional)
                        display: function(context) {
                            return context.dataset.data[context.dataIndex] > 0; // Mostra apenas para valores > 0
                        },
                        font: { size: 9 } // Fonte menor para não poluir
                    }
                }
            }
        );

        // Gráfico de Pessoas Desaparecidas/Localizadas por Sexo
        chartSexo = createOrUpdateChart(chartSexo, 'chartDesaparecidosSexo', 'bar',
            Object.keys(data.data_sexo || {}),
            Object.values(data.data_sexo || {}),
            {
                backgroundColor: ['#007bff', '#17a2b8', '#6c757d'],
                borderColor: ['#007bff', '#17a2b8', '#6c757d']
            },
            {
                scales: {
                    y: { title: { display: true, text: `Número de ${tipoText}` } },
                    x: { title: { display: true, text: 'Sexo' } }
                }
            }
        );

        // Gráfico de Pessoas Desaparecidas/Localizadas por Faixa Etária
        chartIdade = createOrUpdateChart(chartIdade, 'chartDesaparecidosIdade', 'bar',
            Object.keys(data.data_idade || {}),
            Object.values(data.data_idade || {}),
            {
                backgroundColor: ['#007bff', '#28a745', '#6c757d'],
                borderColor: ['#007bff', '#28a745', '#6c757d']
            },
            {
                scales: {
                    y: { title: { display: true, text: `Número de ${tipoText}` } },
                    x: { title: { display: true, text: 'Faixa Etária' } }
                }
            }
        );
    }

    // Event Listeners para os filtros
    document.getElementById('selectTipo').addEventListener('change', updateDashboard);
    document.getElementById('selectAno').addEventListener('change', updateDashboard);
    document.getElementById('selectMes').addEventListener('change', updateDashboard); // Embora não filtre a API, pode ser usado no futuro
    document.getElementById('selectRegiao').addEventListener('change', updateDashboard); // Embora não filtre a API, pode ser usado no futuro

    // Botão Limpar Filtros
    document.getElementById('limparFiltrosBtn').addEventListener('click', function() {
        document.getElementById('selectTipo').value = 'desaparecidos';
        document.getElementById('selectAno').value = '<?php echo date('Y'); ?>'; // Ano atual
        document.getElementById('selectMes').value = 'Todos';
        document.getElementById('selectRegiao').value = 'Todos';
        updateDashboard(); // Recarrega os gráficos com os filtros padrão
    });

    // Carrega o dashboard quando a página é carregada pela primeira vez
    document.addEventListener('DOMContentLoaded', updateDashboard);
</script>

</body>
</html>