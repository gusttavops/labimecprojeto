<?php
// Ensure this path is correct for your environment
require 'conexao.php';

// Start the session at the very beginning of the script
session_start();

// Consulta os 3 casos mais recentes de crianças e adolescentes (idade < 18)
// Limita a 3 resultados para cada categoria
$consulta_criancas = $pdo->prepare("
    SELECT * FROM pessoas_desaparecidas
    WHERE idade < 18
    ORDER BY data_fato_ocorrido DESC
    LIMIT 3
");
$consulta_criancas->execute();
$desaparecidos_criancas = $consulta_criancas->fetchAll(PDO::FETCH_ASSOC);

// Consulta os 3 casos mais recentes de adultos (idade >= 18)
// Limita a 3 resultados para cada categoria
$consulta_adultos = $pdo->prepare("
    SELECT * FROM pessoas_desaparecidas
    WHERE idade >= 18
    ORDER BY data_fato_ocorrido DESC
    LIMIT 3
");
$consulta_adultos->execute();
$desaparecidos_adultos = $consulta_adultos->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>SUMIDOS PB</title>
    <style>
        
        /* No seu bloco <style> ou no seu arquivo CSS */

.intro-section-with-border {
    background-color: #ffffff; /* Fundo branco para destacar */
    padding: 30px 50px; /* Aumentei o padding horizontal para dar mais "ar" */
    margin-bottom: 30px;
    
    /* Estilo para parecer um "balão" */
    border: 1px solid #ddd; /* Borda sutil */
    border-radius: 20px; /* Bordas mais arredondadas para o efeito de "balão" */
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1); /* Uma sombra um pouco mais pronunciada, mas suave */
    
    width: 90%;
    max-width: 900px; /* Diminuí um pouco a largura máxima para que não fique tão esticado */
    margin-left: auto; /* Centraliza a caixa na página */
    margin-right: auto; /* Centraliza a caixa na página */
    text-align: center; /* Centraliza todo o conteúdo dentro da caixa */
}

/* Mantenha o CSS para intro-heading e os parágrafos */
.intro-heading {
    font-size: 32px;
    color: #2e7d32;
    margin-bottom: 28px;
    text-align: center; /* Garante que o título continue centralizado */
}

.intro-section-with-border p {
    font-size: 16px;
    line-height: 1.6;
    color: #333;
    margin-bottom: 15px;
    text-align: left; /* <<< Mudei para 'center' para que os parágrafos também fiquem centralizados */
    padding-left: 0;
}

/* Nos Media Queries (para responsividade) */
@media (max-width: 768px) {
    .intro-section-with-border {
        padding: 20px 25px; /* Ajuste o padding para telas menores */
        border-radius: 15px; /* Ajuste o arredondamento para telas menores */
    }

    .intro-heading {
        font-size: 28px;
    }

    .intro-section-with-border p {
        font-size: 15px;
    }
}
        
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: #f3f3f3;
        }

        header {
            background-color: black;
            color: white;
            text-align: center;
            padding: 20px 0;
        }

        header img {
            width: 295px;
        }

        header h1 {
            margin: 10px 0 0;
            font-size: 36px;
        }

        header h1 span {
            color: red;
        }

        .subtitle {
            background-color: #003300;
            color: white;
            display: inline-block;
            padding: 5px 15px;
            border-radius: 10px;
            font-size: 14px;
            margin-top: 10px;
        }

        nav {
            background-color: #119C2F;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            padding: 10px 0;
            position: relative;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin: 0 20px;
            font-weight: 500;
        }

        nav a:hover {
            text-decoration: underline;
        }

        .login-button {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 50px;
            border-radius: 50px;
            background-color: rgba(0, 128, 0, 0.5);
            position: absolute;
            top: -330px; /* This value might need adjustment based on final header height */
            right: 15px;
            transition: background-color 0.3s ease;
            font-size: 12px;
            z-index: 10;
        }

        .login-button:hover {
            background-color: rgba(0, 128, 0, 0.8);
        }

        .login-icon {
            font-size: 1em;
            margin-bottom: 3px;
        }

        main {
            padding: 50px 5%;
            background-color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: calc(100vh - 400px); /* Ajuste para o rodapé */
        }

        .section-title {
            font-size: 26px;
            margin-bottom: 20px;
            color: #119C2F;
            text-align: left;
            border-left: 5px solid #119C2F;
            padding-left: 10px;
            align-self: flex-start;
            width: 100%;
        }

        .categories {
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
            justify-content: center;
            width: 100%;
        }

        .category-box {
            flex: 1 1 45%;
            background-color: #fefefe;
            border: 2px solid #ccc;
            border-top: 8px solid #119C2F;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .category-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            background-color: #119C2F;
            color: white;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 25px;
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
        }

        .card {
            background-color: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .card img {
            width: 100%;
            height: 220px;
            object-fit: cover;
        }

        .card-content {
            padding: 10px 12px 15px;
        }

.card-content h3 {
    margin: 0;
    font-size: 16px;
    color: #222;
    font-weight: bold; /* Adiciona negrito "real" ao texto */
    /* text-shadow: none; OU remova a linha se existir */
}

        .card-content p {
            margin: 5px 0 0;
            font-size: 13px;
            color: #666;
        }

        .view-button {
            display: block;
            margin: 30px auto 0;
            background-color: #119C2F;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            border-radius: 25px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .view-button:hover {
            background-color: #0e8026;
        }

        .search-bar {
            background-color: white;
            padding: 30px;
            margin-bottom: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            width: 90%;
            max-width: 1100px;
        }

        .search-bar h2 {
            font-size: 39px;
            margin-bottom: 49px;
            color: #2e7d32;
            text-align: center;
        }

        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 43px;
            justify-content: center;
        }

        .search-form input,
        .search-form select {
            flex: 1 1 150px;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 14px;
        }

        .search-form button {
            padding: 10px 20px;
            background-color: #d32f2f;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s ease;
            flex: 1 1 auto;
            min-width: 100px;
        }

        .search-form button:hover {
            background-color: #b71c1c;
        }

        /* Estilos do Rodapé */
        footer {
            background-color: #000;
            color: white;
            padding: 9px 5%;
            text-align: center;
            border-top: 5px solid #119C2F;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 46px;
        }

        .footer-section {
            flex: 1;
            min-width: 250px;
            text-align: left;
        }

        .footer-section h3 {
            color: #119C2F;
            font-size: 19px;
            margin-bottom: 29px;
            border-bottom: 2px solid #119C2F;
            padding-bottom: 5px;
        }

        .footer-section p, 
        .footer-section a {
            color: #ccc;
            line-height: 1.9;
            font-size: 14px;
            text-decoration: none;
            display: block;
            margin-bottom: 8px;
        }

        .footer-section a:hover {
            color: #119C2F;
        }

        .footer-bottom {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #333;
            font-size: 14px;
            color: #999;
        }

        .social-icons {
            margin-top: 15px;
        }

        .social-icons a {
            color: white;
            margin: 0 10px;
            font-size: 20px;
            display: inline-block;
        }

        .social-icons a:hover {
            color: #119C2F;
        }

        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
            }

            .search-form input,
            .search-form select,
            .search-form button {
                flex: 1 1 100%;
                min-width: auto;
            }

            .categories {
                flex-direction: column;
            }

            .category-box {
                flex: 1 1 100%;
            }

            nav {
                justify-content: center;
            }

            nav a {
                margin: 10px 15px;
            }

            .login-button {
                position: static; /* Change to static or relative for better mobile positioning if it overlaps */
                margin-top: 10px; /* Add some space if static */
                right: auto;
                flex-direction: row;
                justify-content: center;
                align-items: center;
                font-size: 14px;
                padding: 8px 20px; /* Adjust padding for smaller screens */
            }

            .login-icon {
                margin-bottom: 0;
                margin-right: 5px;
                font-size: 1.2em;
            }

            .footer-content {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .footer-section {
                text-align: center;
            }
        }
    </style>
    </head>
<body>

    <header>
        <img src="https://i.postimg.cc/brqVVSjZ/GGGGXXXXX.png" alt="Logo SUMIDOS PB">
        <h1>SUMIDOS <span>PB</span></h1>
        <div class="subtitle">INTERMEDIANDO A LOCALIZAÇÃO</div>
    </header>

    <nav>
        <a href="publico.php">Início</a>
        <a href="quem_somos.php">Quem Somos</a>
        <a href="lista_desaparecidos.php">Lista de Desaparecidos</a>
        <a href="registrar_desaparecimento.php">Registrar Desaparecimento</a>
        <a href="dados.php">Dados</a>
        <?php if (isset($_SESSION['usuario'])): ?>
            <a href="perfil.php" class="login-button">
                <span class="login-icon">&#128100;</span> <?php echo htmlspecialchars($_SESSION['nome']); ?>
            </a>
        <?php else: ?>
            <a href="login.php" class="login-button">
                <span class="login-icon">&#128100;</span> Login
            </a>
        <?php endif; ?>
    </nav>

    <main>
       <section class="intro-section-with-border">
    <h2 class="intro-heading">SUMIDOS PB</h2>
    <p>
        O SUMIDOS PB constitui uma plataforma dedicada à localização de indivíduos desaparecidos no Estado da Paraíba. Nosso propósito primordial reside na facilitação da conexão entre familiares, órgãos de segurança pública e a sociedade civil, visando agilizar os processos de busca e oferecer suporte às famílias em momentos de vulnerabilidade.
    </p>
    <p>
        Atualmente, a presente plataforma encontra-se em fase de desenvolvimento e rigorosos testes, com o intuito de garantir sua máxima eficácia e confiabilidade. Este projeto, de natureza inovadora e de relevante impacto social, é concebido e implementado no âmbito do LABIMEC - ( Laboratório de Inteligência Artificial e Macroeconomia Computacional ), unidade de pesquisa formalmente vinculada à prestigiosa Universidade Federal da Paraíba (UFPB).
    </p>
    <p>
        Nossa equipe está integralmente empenhada na construção de uma rede de apoio robusta e eficiente, empregando tecnologias avançadas para otimizar os procedimentos de busca. Acreditamos firmemente que a colaboração e a participação ativa da comunidade são elementos cruciais para o êxito de nossa missão. A contribuição de cada cidadão, seja através do fornecimento de informações, da divulgação de casos ou do apoio à iniciativa, é indispensável.
    </p>
    <p>
        Convidamos Vossa Senhoria a explorar os registros de casos recentes e a familiarizar-se com as funcionalidades disponíveis. Reiteramos que, no presente momento, o registro formal de novos desaparecimentos está temporariamente desabilitado, uma vez que a plataforma se encontra em período de testes e aprimoramentos contínuos. Juntos, consolidaremos esta ferramenta como um recurso essencial e de grande valia para a população paraibana.
    </p>
</section>
        
        <h2 class="section-title">Desaparecimentos Recentes</h2>

        <div class="categories">
            <div class="category-box">
                <div class="category-title">Crianças e Adolescentes</div>
                <div class="grid-container">
                    <?php foreach ($desaparecidos_criancas as $pessoa): ?>
                        <div class="card">
                           <img src="/login/uploads/<?= htmlspecialchars($pessoa['foto']) ?>" alt="<?= htmlspecialchars($pessoa['nome']) ?>">
                            <div class="card-content">
                                <h3><?= htmlspecialchars($pessoa['nome']) ?></h3>
                                <p>Desaparecido(a) em: <?= date('d/m/Y', strtotime($pessoa['data_fato_ocorrido'])) ?> com <?= htmlspecialchars($pessoa['idade']) ?> anos</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="view-button" onclick="location.href='lista_desaparecidos.php?categoria=criancas'">Todos os desaparecimentos de crianças</button>
            </div>

            <div class="category-box">
                <div class="category-title">Adultos</div>
                <div class="grid-container">
                    <?php foreach ($desaparecidos_adultos as $pessoa): ?>
                        <div class="card">
                           <img src="/login/uploads/<?= htmlspecialchars($pessoa['foto']) ?>" alt="<?= htmlspecialchars($pessoa['nome']) ?>">
                            <div class="card-content">
                                <h3><?= htmlspecialchars($pessoa['nome']) ?></h3>
    <p>Desaparecido(a) em: <?= date('d/m/Y', strtotime($pessoa['data_fato_ocorrido'])) ?><br>Idade: <?= htmlspecialchars($pessoa['idade']) ?> anos</p>
</div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="view-button" onclick="location.href='lista_desaparecidos.php?categoria=adultos'">Todos os desaparecimentos de Adultos</button>
            </div>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>SUMIDOS PB</h3>
                <blockquote>
                    <p><img src="https://i.postimg.cc/BQkkvwGp/image.png" width="275" height="185" alt="Logo SUMIDOS PB"/>
                    </p>
                </blockquote>
                <p>A plataforma Sumidos PB é uma solução tecnológica que integra inteligência artificial, georreferenciamento e análise de dados para agilizar e modernizar a busca por pessoas desaparecidas na Paraíba, conectando órgãos públicos, ONGs e a sociedade.</p>
                <blockquote>&nbsp; </blockquote>
                <p>&nbsp;</p>
            </div>

            <div class="footer-section">
                <h3>Links Úteis</h3>
                <a href="publico.php">Início</a>
                <a href="lista_desaparecidos.php">Lista de Desaparecidos</a>
                <a href="registrar_desaparecimento.php">Registrar Desaparecimento</a>
                <a href="dados.php">Dados e Estatísticas</a>
            </div>

            <div class="footer-section">
                <h3>Contato</h3>
                <p>Email: sumidospb@gmail.com</p>
                <p>Instagram: Sumidos PB</p>
                <div class="social-icons">
                    </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>© 2025 SUMIDOS PB - Todos os direitos reservados</p>
            <p>Desenvolvido por Gustavo Pereira. Coordenado pelo Labimec</p>
        </div>
    </footer>
</body>
</html>