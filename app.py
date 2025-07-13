from flask import Flask, jsonify, request
from flask_cors import CORS # Para permitir requisições de origens diferentes

app = Flask(__name__)
CORS(app) # Habilita CORS para todas as rotas (necessário para dev)

# --- Dados Simulados ---
# Em um ambiente real, estes dados viriam de um banco de dados
# e seriam filtrados com base nos parâmetros da requisição.

dados_desaparecidos_por_mes = {
    '2025': {
        'Janeiro': 7049, 'Fevereiro': 6487, 'Março': 7512,
        'Abril': 6908, 'Maio': 6828
    },
    '2024': {
        'Janeiro': 7100, 'Fevereiro': 6500, 'Março': 7600,
        'Abril': 7000, 'Maio': 6900
    },
    '2023': {
        'Janeiro': 6800, 'Fevereiro': 6200, 'Março': 7300,
        'Abril': 6700, 'Maio': 6600
    },
    # Adicione mais anos e meses conforme necessário para simular dados
}

dados_localizados_por_mes = {
    '2025': {
        'Janeiro': 59, 'Fevereiro': 49, 'Março': 61,
        'Abril': 60, 'Maio': 62
    },
    '2024': {
        'Janeiro': 55, 'Fevereiro': 45, 'Março': 58,
        'Abril': 57, 'Maio': 59
    },
    '2023': {
        'Janeiro': 50, 'Fevereiro': 40, 'Março': 53,
        'Abril': 52, 'Maio': 54
    },
    # Adicione mais anos e meses
}


dados_por_uf_desaparecidos = {
    '2025': {
        'AC': 132, 'AL': 294, 'AM': 384, 'AP': 148, 'BA': 1624, 'CE': 1052,
        'DF': 883, 'ES': 990, 'GO': 1476, 'MA': 435, 'MG': 3563, 'MS': 262,
        'MT': 861, 'PA': 459, 'PB': 355, 'PE': 1095, 'PI': 306, 'PR': 2722,
        'RJ': 2688, 'RN': 323, 'RO': 404, 'RR': 203, 'RS': 3141, 'SC': 1726,
        'SE': 264, 'SP': 8763, 'TO': 231
    },
    '2024': { # Apenas para simular mudança de dados
        'AC': 120, 'AL': 280, 'AM': 370, 'AP': 140, 'BA': 1500, 'CE': 1000,
        'DF': 850, 'ES': 950, 'GO': 1400, 'MA': 400, 'MG': 3400, 'MS': 250,
        'MT': 800, 'PA': 430, 'PB': 340, 'PE': 1050, 'PI': 290, 'PR': 2600,
        'RJ': 2550, 'RN': 310, 'RO': 390, 'RR': 190, 'RS': 3000, 'SC': 1650,
        'SE': 250, 'SP': 8500, 'TO': 220
    }
}

dados_por_uf_localizados = {
    '2025': {
        'AC': 6, 'AL': 20, 'AM': 18, 'AP': 4, 'BA': 69, 'CE': 41,
        'DF': 38, 'ES': 11, 'GO': 164, 'MA': 7, 'MG': 262, 'MS': 20,
        'MT': 406, 'PA': 24, 'PB': 15, 'PE': 68, 'PI': 16, 'PR': 142,
        'RJ': 144, 'RN': 20, 'RO': 8, 'RR': 1, 'RS': 164, 'SC': 63,
        'SE': 10, 'SP': 6518, 'TO': 1
    },
    '2024': {
        'AC': 5, 'AL': 18, 'AM': 15, 'AP': 3, 'BA': 60, 'CE': 35,
        'DF': 30, 'ES': 10, 'GO': 150, 'MA': 6, 'MG': 250, 'MS': 18,
        'MT': 380, 'PA': 22, 'PB': 12, 'PE': 60, 'PI': 14, 'PR': 130,
        'RJ': 135, 'RN': 18, 'RO': 7, 'RR': 0, 'RS': 150, 'SC': 55,
        'SE': 8, 'SP': 6000, 'TO': 0
    }
}

dados_por_sexo_desaparecidos = {
    '2025': {'Masculino': 22389, 'Feminino': 12163, 'Não Informado': 232},
    '2024': {'Masculino': 22000, 'Feminino': 12000, 'Não Informado': 200},
}

dados_por_sexo_localizados = {
    '2025': {'Masculino': 108, 'Feminino': 170, 'Não Informado': 11},
    '2024': {'Masculino': 100, 'Feminino': 160, 'Não Informado': 10},
}

dados_por_idade_desaparecidos = {
    '2025': {'+ 18 Anos': 24475, '0 a 17 Anos': 9580, 'N/I': 729},
    '2024': {'+ 18 Anos': 24000, '0 a 17 Anos': 9300, 'N/I': 700},
}

dados_por_idade_localizados = {
    '2025': {'+ 18 Anos': 200, '0 a 17 Anos': 80, 'N/I': 9},
    '2024': {'+ 18 Anos': 180, '0 a 17 Anos': 75, 'N/I': 8},
}

# Novos dados para a taxa por 100 mil habitantes (simulados)
taxa_por_100mil = {
    '2025': {'desaparecidos': 39.12, 'localizados': 4.66},
    '2024': {'desaparecidos': 38.50, 'localizados': 4.50},
    '2023': {'desaparecidos': 37.00, 'localizados': 4.30},
    '2022': {'desaparecidos': 36.00, 'localizados': 4.10},
    '2021': {'desaparecidos': 35.00, 'localizados': 3.90},
    '2020': {'desaparecidos': 34.00, 'localizados': 3.70},
}

# Novas totais anuais (simulados)
total_anual_desaparecidos = {
    '2025': 34784,
    '2024': 34000,
    '2023': 33500,
    '2022': 33000,
    '2021': 32500,
    '2020': 32000,
}

total_anual_localizados = {
    '2025': 289,
    '2024': 250,
    '2023': 220,
    '2022': 200,
    '2021': 180,
    '2020': 160,
}


@app.route('/api/dashboard_data')
def dashboard_data():
    tipo = request.args.get('tipo', 'desaparecidos') # 'desaparecidos' ou 'localizados'
    ano = request.args.get('ano', '2025') # Ano selecionado no filtro
    mes = request.args.get('mes', 'Todos') # Mês selecionado no filtro (ainda não usado para UF/Sexo/Idade, mas pode ser expandido)
    regiao = request.args.get('regiao', 'Todos') # Região/Estado selecionado (não usado para todos os gráficos aqui, mas pode ser expandido)

    # Lógica para selecionar os dados com base no tipo e ano
    if tipo == 'desaparecidos':
        data_mes = dados_desaparecidos_por_mes.get(ano, {})
        data_uf = dados_por_uf_desaparecidos.get(ano, {})
        data_sexo = dados_por_sexo_desaparecidos.get(ano, {})
        data_idade = dados_por_idade_desaparecidos.get(ano, {})
        total_geral = total_anual_desaparecidos.get(ano, 0)
        taxa_100mil = taxa_por_100mil.get(ano, {}).get('desaparecidos', 0)
        # Calcular pessoas por dia (simplificado para este exemplo)
        pessoas_por_dia = round(total_geral / 365) # Média simples
        if ano == '2025': # Ajuste para o dado específico da imagem
             pessoas_por_dia = 230
        
    else: # tipo == 'localizados'
        data_mes = dados_localizados_por_mes.get(ano, {})
        data_uf = dados_por_uf_localizados.get(ano, {})
        data_sexo = dados_por_sexo_localizados.get(ano, {})
        data_idade = dados_por_idade_localizados.get(ano, {})
        total_geral = total_anual_localizados.get(ano, 0)
        taxa_100mil = taxa_por_100mil.get(ano, {}).get('localizados', 0)
        # Calcular pessoas por dia (simplificado para este exemplo)
        pessoas_por_dia = round(total_geral / 365) # Média simples
        if ano == '2025': # Ajuste para o dado específico da imagem
            pessoas_por_dia = 2 # Exemplo, no seu vídeo mostra 2

    response_data = {
        'total_geral': total_geral,
        'pessoas_por_dia': pessoas_por_dia,
        'taxa_100mil': taxa_100mil,
        'data_mes': data_mes,
        'data_uf': data_uf,
        'data_sexo': data_sexo,
        'data_idade': data_idade,
    }
    return jsonify(response_data)

if __name__ == '__main__':
    app.run(debug=True, port=5000) # Rode na porta 5000