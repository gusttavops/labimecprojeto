<?php
require 'conexao.php';

// Tratamento do upload da foto
$foto_nome = '';
if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    $foto_nome = uniqid() . "." . $ext;
    move_uploaded_file($_FILES['foto']['tmp_name'], 'fotos/' . $foto_nome);
}

$sql = "INSERT INTO pessoas_desaparecidas (
    foto, nome, nome_mae, nome_pai, sexo, estado_civil, data_nascimento, grau_instrucao, cpf, idade,
    identidade, emissor_identidade, telefone, pais_nascimento, uf_nascimento, municipio_nascimento, logradouro,
    numero, complemento, cep, bairro, meio_locomocao, estava_acompanhada, possui_bagagem, altura, boca, cabelo, cor_cabelo,
    compleicao, cutis, labios, olhos, cor_olhos, rosto, testa, data_fato_ocorrido, bairro_ocorrido, cidade_ocorrido,
    descricao_ocorrido
) VALUES (
    :foto, :nome, :nome_mae, :nome_pai, :sexo, :estado_civil, :data_nascimento, :grau_instrucao, :cpf, :idade,
    :identidade, :emissor_identidade, :telefone, :pais_nascimento, :uf_nascimento, :municipio_nascimento, :logradouro,
    :numero, :complemento, :cep, :bairro, :meio_locomocao, :estava_acompanhada, :possui_bagagem, :altura, :boca, :cabelo, :cor_cabelo,
    :compleicao, :cutis, :labios, :olhos, :cor_olhos, :rosto, :testa, :data_fato_ocorrido, :bairro_ocorrido, :cidade_ocorrido,
    :descricao_ocorrido
)";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':foto' => $foto_nome,
    ':nome' => $_POST['nome'],
    ':nome_mae' => $_POST['nome_mae'],
    ':nome_pai' => $_POST['nome_pai'],
    ':sexo' => $_POST['sexo'],
    ':estado_civil' => $_POST['estado_civil'],
    ':data_nascimento' => $_POST['data_nascimento'],
    ':grau_instrucao' => $_POST['grau_instrucao'],
    ':cpf' => $_POST['cpf'],
    ':idade' => $_POST['idade'],
    ':identidade' => $_POST['identidade'],
    ':emissor_identidade' => $_POST['emissor_identidade'],
    ':telefone' => $_POST['telefone'],
    ':pais_nascimento' => $_POST['pais_nascimento'],
    ':uf_nascimento' => $_POST['uf_nascimento'],
    ':municipio_nascimento' => $_POST['municipio_nascimento'],
    ':logradouro' => $_POST['logradouro'],
    ':numero' => $_POST['numero'],
    ':complemento' => $_POST['complemento'],
    ':cep' => $_POST['cep'],
    ':bairro' => $_POST['bairro'],
    ':meio_locomocao' => $_POST['meio_locomocao'],
    ':estava_acompanhada' => $_POST['estava_acompanhada'],
    ':possui_bagagem' => $_POST['possui_bagagem'],
    ':altura' => $_POST['altura'],
    ':boca' => $_POST['boca'],
    ':cabelo' => $_POST['cabelo'],
    ':cor_cabelo' => $_POST['cor_cabelo'],
    ':compleicao' => $_POST['compleicao'],
    ':cutis' => $_POST['cutis'],
    ':labios' => $_POST['labios'],
    ':olhos' => $_POST['olhos'],
    ':cor_olhos' => $_POST['cor_olhos'],
    ':rosto' => $_POST['rosto'],
    ':testa' => $_POST['testa'],
    ':data_fato_ocorrido' => $_POST['data_fato_ocorrido'],
    ':bairro_ocorrido' => $_POST['bairro_ocorrido'],
    ':cidade_ocorrido' => $_POST['cidade_ocorrido'],
    ':descricao_ocorrido' => $_POST['descricao_ocorrido']
]);

echo "Registro realizado com sucesso.";
?>
