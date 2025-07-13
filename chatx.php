<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['id'])) {
  header("Location: login.php");
  exit;
}

$usuario_id = $_SESSION['id'];
$stmt = $pdo->prepare("SELECT usuario, foto FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br" class="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Chat de Mensagens</title>
  <link href="https://cdn.jsdelivr.net/npm/remixicon@3.2.0/fonts/remixicon.css" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    body {
      font-family: 'Inter', sans-serif;
    }
  </style>
</head>
<body class="bg-black text-white">
  <div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside class="w-16 bg-zinc-900 flex flex-col items-center justify-between py-4 border-r border-zinc-800">
      <div class="flex flex-col items-center gap-6 text-2xl">
        <a href="#"><i class="ri-home-5-line"></i></a>
        <a href="#"><i class="ri-chat-3-line"></i></a>
        <a href="#"><i class="ri-heart-line"></i></a>
        <a href="#" onclick="abrirModalBusca()"><i class="ri-add-line"></i></a>
      </div>
      <div class="mb-2">
        <img src="<?php echo htmlspecialchars($usuario['foto'] ?? 'https://via.placeholder.com/40'); ?>"
             class="w-10 h-10 rounded-full border-2 border-blue-500"
             alt="Foto de perfil">
      </div>
    </aside>

    <!-- Lista de contatos -->
    <div class="w-80 bg-zinc-950 border-r border-zinc-800 flex flex-col">
      <div class="p-4">
        <div class="flex justify-between items-center">
          <h1 class="text-lg font-semibold">
            <?php echo htmlspecialchars($usuario['usuario']); ?>
            <i class="ri-arrow-down-s-line text-sm"></i>
          </h1>
        </div>
        <div class="mt-4 relative">
          <input type="text" placeholder="Pesquisar" class="w-full bg-zinc-800 rounded-lg py-2 px-4 pl-10 text-sm placeholder-zinc-400">
          <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-sm"></i>
        </div>
      </div>
      <div class="overflow-y-auto px-4 flex-1 pb-4">
        <h2 class="text-xs text-zinc-400 uppercase mb-2">Mensagens</h2>
        <ul class="space-y-3" id="lista-contatos"></ul>
      </div>
    </div>

    <!-- Área de conversa -->
    <div class="flex-1 bg-black flex flex-col">
      <div class="p-4 border-b border-zinc-800">
        <h2 class="text-xl font-semibold" id="titulo-chat">Selecione um contato</h2>
      </div>
      <div class="flex-1 overflow-y-auto p-4" id="chat-mensagens"></div>
      <form onsubmit="enviarMensagem(event)" class="flex p-4 gap-2 border-t border-zinc-800">
        <input id="mensagem-input" type="text" placeholder="Digite sua mensagem..." class="flex-1 bg-zinc-800 text-white px-4 py-2 rounded-lg text-sm">
        <button class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-md text-sm">Enviar</button>
      </form>
    </div>
  </div>

  <!-- Modal de busca de usuários -->
  <div id="modal-busca" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 hidden">
    <div class="bg-zinc-900 p-6 rounded-lg w-96 max-h-[80vh] overflow-y-auto">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold">Buscar usuários</h2>
        <button onclick="fecharModalBusca()" class="text-zinc-400 hover:text-white">
          <i class="ri-close-line text-2xl"></i>
        </button>
      </div>
      <input type="text" id="busca-usuarios" placeholder="Digite um nome..."
             class="w-full bg-zinc-800 text-white px-4 py-2 rounded-lg mb-4" oninput="buscarUsuarios()">
      <ul id="resultado-busca" class="space-y-3"></ul>
    </div>
  </div>

<script>
const remetenteId = <?php echo $_SESSION['id']; ?>;
let destinatarioId = null;

function carregarContatos() {
  fetch('contatos.php')
    .then(res => res.json())
    .then(contatos => {
      const lista = document.getElementById("lista-contatos");
      lista.innerHTML = "";
      contatos.forEach(contato => {
        const item = document.createElement("li");
        item.className = "flex gap-3 items-center hover:bg-zinc-800 rounded-lg p-2 cursor-pointer";
        item.innerHTML = `
          <img src="${contato.foto || 'https://via.placeholder.com/40'}" class="w-10 h-10 rounded-full">
          <div class="flex-1">
            <div class="text-sm font-medium">${contato.usuario}</div>
            <div class="text-xs text-zinc-400">Clique para conversar</div>
          </div>
        `;
        item.onclick = () => {
          destinatarioId = contato.id;
          document.getElementById("titulo-chat").innerText = contato.usuario;
          carregarMensagens();
        };
        lista.appendChild(item);
      });
    });
}

function carregarMensagens() {
  if (!destinatarioId) return;
  fetch(`mensagens.php?destinatario=${destinatarioId}`)
    .then(res => res.json())
    .then(mensagens => {
      const container = document.getElementById("chat-mensagens");
      container.innerHTML = "";
      mensagens.forEach(msg => {
        const alinhamento = msg.usuario_id == remetenteId ? "justify-end" : "justify-start";
        const cor = msg.usuario_id == remetenteId ? "bg-blue-600" : "bg-zinc-800";
        container.innerHTML += `
          <div class="flex ${alinhamento} mb-2">
            <div class="px-3 py-2 rounded-lg text-sm ${cor} max-w-xs">
              ${msg.mensagem}
            </div>
          </div>
        `;
      });
      container.scrollTop = container.scrollHeight;
    });
}

function enviarMensagem(event) {
  event.preventDefault();
  if (!destinatarioId) return;

  const input = document.getElementById("mensagem-input");
  const conteudo = input.value.trim();
  if (!conteudo) return;

  fetch("enviar.php", {
    method: "POST",
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
      destinatario_id: destinatarioId,
      mensagem: conteudo
    })
  }).then(res => res.json())
    .then(() => {
      input.value = "";
      carregarMensagens();
    });
}

// Modal de busca
function abrirModalBusca() {
  document.getElementById("modal-busca").classList.remove("hidden");
  document.getElementById("busca-usuarios").value = "";
  document.getElementById("resultado-busca").innerHTML = "";
}

function fecharModalBusca() {
  document.getElementById("modal-busca").classList.add("hidden");
}

function buscarUsuarios() {
  const termo = document.getElementById("busca-usuarios").value.trim();
  if (termo.length < 1) {
    document.getElementById("resultado-busca").innerHTML = "";
    return;
  }

  // === LINHA CORRIGIDA AQUI ===
  fetch(`buscar_usuarios.php?termo=${encodeURIComponent(termo)}`)
    .then(res => {
      // Adicione um log para inspecionar a resposta antes de tentar parsear como JSON
      // console.log('Resposta bruta da busca de usuários:', res);
      if (!res.ok) { // Verifica se a resposta não foi bem sucedida (status 200 OK)
          console.error('Erro na requisição:', res.status, res.statusText);
          // Opcional: throw new Error(`HTTP error! status: ${res.status}`);
      }
      return res.json();
    })
    .then(usuarios => {
      const lista = document.getElementById("resultado-busca");
      lista.innerHTML = "";
      // Verifique se 'usuarios' é um array e não está vazio
      if (Array.isArray(usuarios) && usuarios.length > 0) {
        usuarios.forEach(user => {
          const item = document.createElement("li");
          item.className = "flex items-center gap-3 cursor-pointer hover:bg-zinc-800 p-2 rounded";
          // CUIDADO: user.foto pode não existir se você não busca a foto do usuário na query
          // user.usuario também deve ser o nome da coluna no DB, que no seu caso é 'nome'
          item.innerHTML = `
            <img src="${user.foto || 'https://via.placeholder.com/40'}" class="w-10 h-10 rounded-full">
            <div class="flex-1">
              <div class="font-medium">${user.nome}</div> <div class="text-xs text-zinc-400">Clique para iniciar conversa</div>
            </div>
          `;
          item.onclick = () => {
            destinatarioId = user.id;
            document.getElementById("titulo-chat").innerText = user.nome; // Alterado para user.nome
            fecharModalBusca();
            carregarMensagens();
          };
          lista.appendChild(item);
        });
      } else {
          lista.innerHTML = '<li class="text-zinc-400 text-sm">Nenhum usuário encontrado.</li>';
      }
    })
    .catch(error => {
      console.error('Erro ao buscar usuários:', error);
      document.getElementById("resultado-busca").innerHTML = '<li class="text-red-500 text-sm">Erro ao carregar usuários.</li>';
    });
}

carregarContatos();
setInterval(() => {
  if (destinatarioId) carregarMensagens();
}, 3000);
</script>
</body>
</html>
