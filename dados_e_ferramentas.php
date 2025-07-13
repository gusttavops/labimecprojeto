<div class="tool-section">
    <h2>Dados e Ferramentas</h2>
    <p>Esta seção oferece acesso a diversas informações e ferramentas úteis para auxiliar na busca por pessoas desaparecidas e para entender melhor o cenário de desaparecimentos no estado da Paraíba.</p>

    <h3>Visão Geral</h3>
    <p>Aqui você encontrará um panorama dos dados coletados, bem como as ferramentas disponíveis para análise e apoio às investigações.</p>
    <ul>
        <li><strong>Estatísticas detalhadas:</strong> Acesse gráficos e números sobre desaparecimentos, faixa etária, gênero, localidade, etc.</li>
        <li><strong>Mapa de Calor de Desaparecimentos:</strong> Visualize áreas com maior incidência de desaparecimentos, auxiliando na identificação de padrões.</li>
        <li><strong>Recursos de Busca Avançados:</strong> Ferramentas para otimizar suas buscas, como filtros e integrações com outras bases de dados (se aplicável).</li>
    </ul>

    <p class="geolocation-info">A geolocalização é uma ferramenta crucial na busca por desaparecidos, permitindo mapear a última localização conhecida e possíveis rotas. As informações apresentadas aqui são apenas exemplos e em um sistema real seriam dinâmicas, baseadas em dados atualizados.</p>
</div>

<div class="tool-section">
    <h3>Exemplo de Geolocalização (Mapa Interativo)</h3>
    <p>Este é um placeholder para um mapa interativo que exibiria pontos de interesse, últimas localizações conhecidas ou um mapa de calor de desaparecimentos. Para que ele funcione de verdade, você precisaria integrar uma API de mapas (como Google Maps, OpenStreetMap, Leaflet.js, etc.) e alimentar com dados reais.</p>
    <div id="map">
        <p>O mapa seria carregado aqui.<br>Integre uma API de mapas para funcionalidade real.</p>
    </div>
    <p>Para implementar um mapa real, você precisaria adicionar bibliotecas JavaScript e uma chave de API (se for o caso) e codificar a lógica para exibir os dados no mapa.</p>
</div>

<script>
    // Placeholder para inicialização do mapa.
    // Em uma aplicação real, você carregaria uma biblioteca de mapeamento (ex: Leaflet.js ou Google Maps API)
    // e inicializaria o mapa aqui com dados relevantes.
    document.addEventListener('DOMContentLoaded', function() {
        const mapDiv = document.getElementById('map');
        if (mapDiv && typeof L !== 'undefined') { // Exemplo com Leaflet.js (você precisaria incluir a biblioteca)
            // var map = L.map('map').setView([-7.1194, -34.8645], 13); // Coordenadas de João Pessoa
            // L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            //     attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            // }).addTo(map);
            // L.marker([-7.1194, -34.8645]).addTo(map)
            //     .bindPopup('Ponto de exemplo em João Pessoa.')
            //     .openPopup();
        }
    });
</script>