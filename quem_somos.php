<?php
// Certifique-se de que este caminho está correto para o seu ambiente
require 'conexao.php'; // Se a página 'quem_somos.php' precisar de conexão com o banco de dados

// Inicia a sessão no início do script
session_start();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quem Somos - SUMIDOS PB</title>
    <style>
        /* Seus estilos CSS do arquivo index.php - Copiados para consistência */
        body {
            font-family: 'Segoe UI', sans-serif; /* Usar a mesma fonte do seu index.php */
            margin: 0;
            padding: 0;
            background-color: #f3f3f3; /* Usar a mesma cor de fundo do seu index.php */
            color: #333;
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
            top: -330px; /* Ajuste conforme necessário para o seu layout */
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

        /* Estilos do "Quem Somos" aprimorados */
        .quem-somos-section {
            width: 80%;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            max-width: 1000px; /* Para harmonizar com o max-width da sua intro-section */
            margin-bottom: 30px; /* Espaço entre as seções */
        }

        .quem-somos-section h1, .quem-somos-section h2 {
            text-align: center;
            color: #2e7d32; /* Cor verde do seu intro-heading */
            margin-bottom: 30px;
            font-size: 32px; /* Tamanho do seu intro-heading */
        }

        .quem-somos-section h2 {
            font-size: 26px; /* Um pouco menor para subseções */
            margin-top: 40px; /* Espaço antes de cada nova subseção */
            padding-bottom: 10px;
            border-bottom: 2px solid #119C2F; /* Linha de destaque */
            display: inline-block; /* Para a borda se ajustar ao texto */
        }

        .quem-somos-section p {
            font-size: 16px;
            line-height: 1.6;
            color: #333;
            margin-bottom: 15px;
            text-align: justify; /* Alinhar o texto justificado para melhor leitura */
        }

        /* Estilos para a seção Nossa Equipe */
        .team-member {
            display: flex;
            align-items: center;
            margin-bottom: 23px;
            padding: 13px;
            border: 4px solid #ddd;
            border-radius: 8px;
            background-color: white;
        }
        .team-member:nth-child(odd) {
            flex-direction: row;
        }
        .team-member:nth-child(even) {
            flex-direction: row-reverse;
        }
        .team-member img {
            width: 171px;
            height: 200px;
            border-radius: 7%;
            object-fit: cover;
            margin: 0 30px;
            border: 4px solid #fefffe; /* Cor da borda do seu nav */
            flex-shrink: 0; /* Impede a imagem de encolher */
        }
        .member-info {
            flex: 1;
        }
        .member-info h3 { /* Mudado para h3 para hierarquia */
            color: black; /* Pode ajustar para uma cor mais alinhada com seu tema, ex: #119C2F */
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 20px; /* Um pouco maior para nome */
        }
        .member-info p {
            line-height: 1.6;
            font-size: 15px;
            color: #333;
            text-align: left; /* Biografia alinhada à esquerda */
        }

        /* Estilos do Rodapé (Copiados do seu index.php) */
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

        /* Media Queries (Copiados e adaptados do seu index.php) */
        @media (max-width: 768px) {
            .quem-somos-section {
                width: 95%;
                padding: 15px;
            }
            .quem-somos-section h1 {
                font-size: 28px;
            }
            .quem-somos-section h2 {
                font-size: 22px;
                text-align: right; /* Centraliza o h2 no mobile */
                width: 100%; /* Ocupa a largura total para centralizar borda inferior */
            }
            .quem-somos-section p {
                text-align: center; /* Manter justificado ou alinhado à esquerda */
            }
            .team-member {
                flex-direction: column !important; /* Força a coluna em telas pequenas */
                text-align: center;
            }
            .team-member img {
                margin: 0 0 20px 0;
            }
            .member-info h3, .member-info p {
                text-align: center;
            }
            nav {
                justify-content: center;
            }
            nav a {
                margin: 10px 15px;
            }
            .login-button {
                position: static;
                margin-top: 10px;
                right: auto;
                flex-direction: row;
                justify-content: center;
                align-items: center;
                font-size: 14px;
                padding: 8px 20px;
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
        <a href="registrar_desaparecido.php">Registrar Desaparecimento</a>
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
        <section class="quem-somos-section">
            <h1>Sobre o SUMIDOS PB</h1>

            <h2 id="perfil">Perfil</h2>
            <p>
                O SUMIDOS PB é uma iniciativa inovadora da Universidade Federal da Paraíba (UFPB), desenvolvida no âmbito do Laboratório de Inteligência Artificial e Macroeconomia Computacional (LABIMEC). Nossa plataforma nasce da necessidade urgente de otimizar e humanizar o processo de busca por pessoas desaparecidas no estado da Paraíba. Atuamos como um elo estratégico, conectando familiares, órgãos de segurança pública e a sociedade civil, utilizando tecnologia avançada para agilizar a localização e oferecer suporte em momentos de extrema vulnerabilidade.
            </p>
            <p>
                Em fase de testes e aprimoramentos contínuos, buscamos garantir a máxima eficácia, segurança e confiabilidade de todas as nossas funcionalidades. Nosso compromisso é com a inovação social, aplicando conhecimentos científicos e tecnológicos para construir uma ferramenta robusta e acessível a todos os paraibanos.
            </p>

            <h2 id="missao-valores">Missão e Valores</h2>
            <p>
                <strong>Nossa Missão:</strong> Facilitar a localização de pessoas desaparecidas na Paraíba, integrando tecnologia de ponta, colaboração interinstitucional e participação comunitária para oferecer suporte ágil e compassivo às famílias.
            </p>
            <p>
                <strong>Nossos Valores:</strong>
            </p>
            <ul>
                <li><strong>Humanidade:</strong> Colocamos as pessoas e suas famílias no centro de todas as nossas ações, agindo com empatia e respeito.</li>
                <li><strong>Inovação:</strong> Buscamos constantemente novas soluções tecnológicas e metodologias para aprimorar a eficácia da busca.</li>
                <li><strong>Colaboração:</strong> Acreditamos no poder da união entre diferentes setores da sociedade para alcançar resultados significativos.</li>
                <li><strong>Transparência:</strong> Mantemos a clareza e a integridade em todas as nossas operações e comunicações.</li>
                <li><strong>Comprometimento:</strong> Dedicamo-nos integralmente à nossa causa, com responsabilidade e persistência em cada caso.</li>
                <li><strong>Impacto Social:</strong> Geramos valor real para a comunidade, contribuindo para a redução da dor e da incerteza das famílias.</li>
            </ul>

            <h2 id="nossa-equipe">Nossa Equipe</h2>
            <p>
                
            </p>

            <div class="team-member">
                <img src="https://i.postimg.cc/SK81Gn4N/Cassio-Besarria.jpg" alt="Foto de Integrante 1">
                <div class="member-info">
                    <h3>Cássio da Nobrega Besarria - Coordenador</h3>
                    <p>Graduado (2008) e mestrado (2010) em Economia pela Universidade Federal da Paraíba (UFPB), complementando sua formação com um doutorado (2014) pelo Programa de Pós-Graduação em Economia (Pimes) da Universidade Federal de Pernambuco (UFPE). Atualmente,  integra o corpo docente da UFPB, concentrando suas atividades de ensino e pesquisa nas áreas de macroeconomia, finanças, econometria e economia internacional.</p>
                </div>
            </div>

            <div class="team-member">
                <img src="https://i.postimg.cc/Dw3XX0xc/gustavo-pereira.png" alt="Foto de Integrante 2">
                <div class="member-info">
                    <h3>Gustavo Pereira da Silva - Supervisor-Geral e Idealizador</h3>
                    <p>Graduando em Ciências Econômicas pela UFPB, possui experiência em liderança e gestão. Atuou como Diretor-Presidente da CAJE, Consultoria e Assessoria Júnior de Economia, e é o Idealizador e Supervisor-Geral do Sumidos PB.</p>
                </div>
            </div>

            <div class="team-member">
                <img src="https://i.postimg.cc/SStFNMKH/andre-luiz.png" alt="Foto de Integrante 3">
                <div class="member-info">
                    <h3>André Luiz de Azevedo Pontes - Integrante</h3>
                    <p>Graduando em Ciências Econômicas pela UFPB, possui tecnólogo em Análise e Desenvolvimento de Sistemas. Atualmente é Diretor-Presidente da CAJE e Integrante do Labimec.</p>
                </div>
            </div>

            <div class="team-member">
                <img src="https://i.postimg.cc/cCr8W8XQ/arthur.png" alt="Foto de Integrante 4">
                <div class="member-info">
                    <h3>Antonio Arthur de Souza Cardoso - Integrante</h3>
                    <p>Cientista de Dados e Analista de Negócios, com expertise em extrair insights valiosos de grandes volumes de dados. Contribui com análises estratégicas para aprimorar a eficácia das operações e tomadas de decisão.</p>
                </div>
            </div>
        
        
        
        <div class="team-member">
                <img src="https://i.postimg.cc/bdXY56r2/leonardo.png" alt="Foto de Integrante 4">
                <div class="member-info">
                    <h3>Leonardo da Silva Chanfrone - Integrante</h3>
                    <p>Leonardo da Silva Chanfrone, graduando em Ciências Econômicas pela Universidade Federal de Viçosa (UFV), é integrante do LABIMEC. Sendo ex-Diretor de Biomercado na Bioenergia - UFV e ex-Membro da equipe de gestão na LAMF5 - Mercado Financeiro.</p>
                </div>
            </div>
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        </section>
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
                <a href="index.php">Início</a>
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