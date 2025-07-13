<div class="tool-section">
    <h2>Mapa de Calor de Desaparecimentos (Geolocalização)</h2>
    <p>Este mapa visualiza as áreas com maior concentração de casos de desaparecimento na Paraíba, utilizando dados de geolocalização. Isso pode ajudar a identificar "pontos quentes" e direcionar recursos de busca de forma mais eficaz.</p>

    <div id="heatmap-map" style="height: 450px; width: 100%; border-radius: 8px;">
        <p style="text-align: center;">Mapa de Calor seria renderizado aqui.<br>É necessária a integração com uma API de mapas e uma biblioteca de heatmap.</p>
    </div>

    <p style="margin-top: 20px;"><strong>Como funciona um Mapa de Calor?</strong></p>
    <p>Um mapa de calor representa a densidade de pontos de dados (neste caso, locais de desaparecimento) em uma área geográfica. Áreas mais quentes (geralmente em tons de vermelho/laranja) indicam uma maior concentração de eventos, enquanto áreas mais frias (tons de azul/verde) indicam menor concentração.</p>

    <p><strong>Implementação:</strong> Para ter um mapa de calor funcional, você precisaria de:</p>
    <ul>
        <li>Uma API de mapas (ex: Google Maps API, Leaflet com plugin de heatmap).</li>
        <li>Dados geográficos (latitude e longitude) dos locais de desaparecimento.</li>
        <li>Lógica JavaScript para renderizar o heatmap sobre o mapa.</li>
    </ul>
</div>