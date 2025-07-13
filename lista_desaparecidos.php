<?php

// NO TOPO DO ARQUIVO, ANTES de require 'conexao.php';
echo "\n";

require_once 'conexao.php';

// ... (todo o seu código PHP existente) ...

// NO FINAL DO ARQUIVO, LOGO ANTES de </body>
echo "\n";


// Filtros e paginação
$nome = $_GET['nome'] ?? '';
$municipio = $_GET['municipio'] ?? '';
$ordem = $_GET['ordem'] ?? 'data';
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$limite = 5;
$offset = ($pagina - 1) * $limite;

// Monta SQL com filtros
$condicoes = [];
$params = [];

if (!empty($nome)) {
    $condicoes[] = "nome LIKE :nome";
    $params[':nome'] = "%$nome%";
}
if (!empty($municipio)) {
    $condicoes[] = "cidade_ocorrido LIKE :municipio";
    $params[':municipio'] = "%$municipio%";
}

$where = count($condicoes) ? 'WHERE ' . implode(' AND ', $condicoes) : '';
$orderBy = $ordem === 'nome' ? 'ORDER BY nome ASC' : 'ORDER BY data_registro DESC';

// Total de resultados para paginação
$sqlTotal = "SELECT COUNT(*) FROM pessoas_desaparecidas $where";
$stmtTotal = $pdo->prepare($sqlTotal);
$stmtTotal->execute($params);
$totalRegistros = $stmtTotal->fetchColumn();
$totalPaginas = ceil($totalRegistros / $limite);

// Consulta principal
$sql = "SELECT * FROM pessoas_desaparecidas $where $orderBy LIMIT :limite OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();


// Filtros e paginação
$nome = $_GET['nome'] ?? '';
$municipio = $_GET['municipio'] ?? '';
$ordem = $_GET['ordem'] ?? 'data';
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$limite = 5;
$offset = ($pagina - 1) * $limite;

// VARIÁVEIS DE FILTRO ESSENCIAIS
$idade_filtro = $_GET['idade_filtro'] ?? '';
$sexo_filtro = $_GET['sexo_filtro'] ?? '';
$cutis_filtro = $_GET['cutis_filtro'] ?? ''; // Cor de Pele


// Monta SQL com filtros
$condicoes = [];
$params = [];

if (!empty($nome)) {
    $condicoes[] = "nome LIKE :nome";
    $params[':nome'] = "%$nome%";
}
if (!empty($municipio)) {
    $condicoes[] = "cidade_ocorrido LIKE :municipio";
    $params[':municipio'] = "%$municipio%";
}

// CONDIÇÕES DE FILTRO ESSENCIAIS
if (!empty($idade_filtro)) {
    $condicoes[] = "idade = :idade_filtro";
    $params[':idade_filtro'] = (int)$idade_filtro;
}
if (!empty($sexo_filtro)) {
    $condicoes[] = "sexo = :sexo_filtro";
    $params[':sexo_filtro'] = $sexo_filtro;
}
if (!empty($cutis_filtro)) {
    $condicoes[] = "cutis LIKE :cutis_filtro";
    $params[':cutis_filtro'] = "%$cutis_filtro%";
}

$where = count($condicoes) ? 'WHERE ' . implode(' AND ', $condicoes) : '';
$orderBy = $ordem === 'nome' ? 'ORDER BY nome ASC' : 'ORDER BY data_registro DESC';


?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sumidos PB - Lista de Desaparecidos</title>
  <style>
    
  /* AJUSTAR O .filter-box (se já não estiver assim) */
.filter-box {
    background: #fff;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    gap: 10px; /* Mantém o espaçamento entre os elementos */
    flex-wrap: wrap;
    align-items: center;
    box-shadow: 0 0 6px rgba(0,0,0,0.1);
}

/* ESTILO PARA O BOTÃO CONSULTAR (DEIXAR VERDE) */
.filter-box button[type="submit"] {
    background-color: #119C2F; /* Verde principal do seu site */
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    transition: background-color 0.3s ease; /* Efeito suave ao passar o mouse */
}

.filter-box button[type="submit"]:hover {
    background-color: #0d7f26; /* Um tom um pouco mais escuro ao passar o mouse */
}

/* ESTILO PARA O LINK LIMPAR FILTROS */
.clear-filters-link {
    text-decoration: none;
    color: #000000; /* Preto original */
    font-size: 14px; /* Opcional: para alinhar com o tamanho do botão */
    margin-left: 10px; /* Espaço entre o botão e o link */
}

.clear-filters-link:hover {
    text-decoration: underline;
}

/* ESTILO PARA O GRUPO DE AÇÕES (BOTÃO E LINK) - EMPURRA PARA A DIREITA */
.filter-actions {
    display: flex;
    align-items: center;
    margin-left: auto; /* Empurra este grupo para a direita */
    gap: 0; /* Remove gap padrão, já que o margin-left do link já gerará o espaçamento */
}  

    body { margin: 0; font-family: Arial, sans-serif; background-color: #f5f5f5; }
    header { background-color: black; color: white; text-align: center; padding: 20px 0; }
    header img { width: 150px; }
    header h1 { margin: 10px 0 0; font-size: 36px; }
    header h1 span { color: red; }
    .subtitle { background-color: #003300; color: white; display: inline-block; padding: 5px 15px; border-radius: 10px; font-size: 14px; margin-top: 10px; }
    nav { background-color: #119C2F; display: flex; justify-content: center; flex-wrap: wrap; padding: 10px 0; }
    nav a { color: white; text-decoration: none; margin: 0 15px; font-weight: bold; }
    .container { padding: 20px; max-width: 1200px; margin: 0 auto; }
    .filter-box {
      background: #fff; padding: 15px; border-radius: 8px; margin-bottom: 20px;
      display: flex; gap: 10px; flex-wrap: wrap; align-items: center;
      box-shadow: 0 0 6px rgba(0,0,0,0.1);
    }
    .filter-box input, .filter-box select, .filter-box button {
      padding: 8px; font-size: 14px;
    }
    .card {
      display: flex; justify-content: space-between;
      background-color: #fff; margin-bottom: 15px;
      padding: 10px; border-radius: 4px;
      box-shadow: 0 0 4px rgba(0,0,0,0.1);
      gap: 15px;
    }
    .card img.foto {
      width: 130px; height: 150px; object-fit: cover;
      border-radius: 4px;
    }
    .card-content { flex: 1; }
    .card-content h3 { margin: 0 0 5px; font-size: 16px; color: #333; text-transform: uppercase; }
    .card-content p { margin: 3px 0; font-size: 14px; color: #444; }

    .municipio-box {
      text-align: center;
      width: 150px;
    }

    .municipio-box p {
      font-weight: bold;
      margin: 0 7 5px;
      font-size: 14px;
    }

    .municipio-box img {
      width: 130px; height: auto;
    }

    .pagination { margin: 20px 0; display: flex; gap: 5px; flex-wrap: wrap; }
    .pagination a, .pagination span {
      padding: 5px 10px; border-radius: 4px; font-size: 14px; text-decoration: none;
      background: #ddd; color: #000;
    }
    .pagination .active {
      background: #119C2F; color: white; font-weight: bold;
    }
    footer { background-color: #000; color: #ccc; text-align: center; padding: 10px; font-size: 13px; margin-top: 20px; }
  </style>
</head>
<body>

<header>
  <img src="https://i.postimg.cc/brqVVSjZ/GGGGXXXXX.png" alt="Logo SUMIDOS PB" />
  <h1>SUMIDOS <span>PB</span></h1>
  <div class="subtitle">INTERMEDIANDO A LOCALIZAÇÃO</div>
</header>

<nav>
  <a href="publico.php">Início</a>
  <a href="quem_somos.php">Quem Somos</a>
  <a href="lista_desaparecidos.php">Lista de Desaparecidos</a>
  <a href="registrar_desaparecido.php">Registrar Desaparecimento</a>
  <a href="#">Dados</a>
</nav>

<div class="container">

<form method="get" class="filter-box">
    <input type="text" name="nome" placeholder="Nome" value="<?= htmlspecialchars($nome) ?>">
    <input type="text" name="municipio" placeholder="Município" value="<?= htmlspecialchars($municipio) ?>">

    <input type="number" name="idade_filtro" placeholder="Idade" value="<?= htmlspecialchars($idade_filtro) ?>" min="0">
    
    <select name="sexo_filtro">
        <option value="">Sexo (Todos)</option>
        <option value="Masculino" <?= $sexo_filtro === 'Masculino' ? 'selected' : '' ?>>Masculino</option>
        <option value="Feminino" <?= $sexo_filtro === 'Feminino' ? 'selected' : '' ?>>Feminino</option>
        <option value="Outro" <?= $sexo_filtro === 'Outro' ? 'selected' : '' ?>>Outro</option>
    </select>

    <select name="ordem">
      <option value="data" <?= $ordem === 'data' ? 'selected' : '' ?>>Ordenado por Data</option>
      <option value="nome" <?= $ordem === 'nome' ? 'selected' : '' ?>>Ordenado por Nome</option>
      </select>
    <button type="submit">Consultar</button>
    <a href="?" style="margin-left: auto; text-decoration: none; color: #000000;">Limpar Filtros</a>
</form>

<?php if ($totalPaginas > 1): ?>
  <div class="pagination">
    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
      <?php if ($i == $pagina): ?>
        <span class="active">Atual</span>
      <?php else: ?>
        <a href="?nome=<?= urlencode($nome) ?>&municipio=<?= urlencode($municipio) ?>&ordem=<?= $ordem ?>&pagina=<?= $i ?>"><?= $i ?></a>
      <?php endif; ?>
    <?php endfor; ?>
  </div>
<?php endif; ?>

<?php
if ($stmt->rowCount() > 0) {
    while ($row = $stmt->fetch()) {
        $foto = !empty($row['foto']) ? 'fotos/' . htmlspecialchars($row['foto']) : 'https://via.placeholder.com/130x150?text=Sem+Foto';
        $municipio = htmlspecialchars($row['cidade_ocorrido']);
        $bandeira = 'bandeiras/' . strtolower(str_replace(' ', '_', $row['cidade_ocorrido'])) . '.png';

       echo '<div class="card">';
echo '<img class="foto" src="' . $foto . '" alt="' . htmlspecialchars($row['nome']) . '">';
echo '<div class="card-content">';
echo '<h3>' . htmlspecialchars($row['nome']) . '</h3>';
echo '<p><strong>Desaparecido desde:</strong> ' . (!empty($row['data_fato_ocorrido']) ? date('d/m/Y', strtotime($row['data_fato_ocorrido'])) : 'Data não informada') . '</p>';
echo '<p><strong>Município:</strong> ' . $municipio . '</p>';
echo '<p><strong>Idade:</strong> ' . htmlspecialchars($row['idade']) . ' anos</p>';

// BOTÃO CENTRALIZADO
echo '<div style="text-align:center; margin-top:10px;">';
echo '<a href="ver_desaparecido.php?id=' . $row['id'] . '" style="background-color:#119C2F; color:#fff; padding:8px 15px; border-radius:20px; text-decoration:none; font-weight:bold; font-size:14px;">Exibir Registro de Desaparecimento</a>';
echo '</div>';

echo '</div>';
echo '<div class="municipio-box">';
echo '<p>Município de ' . $municipio . '</p>';
        echo '<img src="' . $bandeira . '" alt="Bandeira" onerror="this.onerror=null; this.src=\'bandeiras/default.png\'">';
        echo '</div>';
        echo '</div>';

    }
} else {
    echo "<p>Nenhum desaparecido encontrado com os critérios informados.</p>";
}
?>

</div>

<footer>
  <p>© 2025 SUMIDOS PB - Todos os direitos reservados</p>
  <p>Desenvolvido por Gustavo Pereira - LABIMEC</p>
</footer>

</body>
</html>
