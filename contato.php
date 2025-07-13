<?php
function obterContatos($usuario_id, $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT u.id, u.nome, u.foto_perfil
            FROM contatos c
            JOIN usuarios u ON (
                u.id = CASE
                    WHEN c.usuario_id_1 = :usuario_id THEN c.usuario_id_2
                    ELSE c.usuario_id_1
                END
            )
            WHERE c.usuario_id_1 = :usuario_id OR c.usuario_id_2 = :usuario_id
        ");
        $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}
