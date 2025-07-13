<?php
require 'conexao.php'; // ou o arquivo de conexão correto

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $stmt = $pdo->prepare("SELECT p.*, u.nome AS usuario, u.foto_perfil FROM publicacoes p JOIN usuarios u ON p.usuario_id = u.id WHERE p.id = ?");
    $stmt->execute([$id]);
    $pub = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($pub):
?>
    <div class="post-detalhada" style="background: #fff; padding: 15px; border-radius: 10px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <img src="<?php echo !empty($pub["foto_perfil"]) ? htmlspecialchars($pub["foto_perfil"]) : 'https://exemplo.com/default.png'; ?>" width="50" style="border-radius: 50%;">
            <strong><?php echo htmlspecialchars($pub["usuario"]); ?></strong>
        </div>
        <p style="font-size: 12px; color: #666; margin: 5px 0;"><?php echo date('d/m/Y H:i', strtotime($pub["data_publicacao"])); ?></p>
        <p><?php echo nl2br(htmlspecialchars($pub["mensagem"])); ?></p>
        <?php if (!empty($pub["imagem"])): ?>
            <img src="uploads/<?php echo htmlspecialchars($pub["imagem"]); ?>" style="max-width: 100%; border-radius: 10px;">
        <?php endif; ?>
    </div>
<?php
    else:
        echo "<p>Publicação não encontrada.</p>";
    endif;
}
?>
