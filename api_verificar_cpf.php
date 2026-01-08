<?php
require 'config.php';
header('Content-Type: application/json');

// Recebe os dados
$data = json_decode(file_get_contents('php://input'), true);

// Limpa o CPF (remove pontos e traços) para garantir que bata com o banco
// Se no banco você salva com ponto e traço, remova esta linha de preg_replace.
// Se no banco salva apenas números, mantenha esta linha.
$cpf = isset($data['cpf']) ? $data['cpf'] : ''; 
// DICA: Se no seu banco o CPF tem pontos, use: $cpf = $data['cpf']; 

if (empty($cpf)) {
    echo json_encode(['bloqueado' => false]);
    exit;
}

// AQUI ESTÁ A MÁGICA:
// Buscamos o aluno devedor E os dados do motorista credor ao mesmo tempo
$sql = "SELECT m.nome, m.telefone 
        FROM alunos a
        JOIN motoristas m ON a.motorista_id = m.id
        WHERE a.cpf_responsavel = ? 
          AND a.status_financeiro = 'inadimplente'
        LIMIT 1"; // Pega o primeiro registro que achar

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $cpf);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // ENCONTROU DÍVIDA!
    $dados = $result->fetch_assoc();
    
    echo json_encode([
        'bloqueado' => true,
        'motorista_nome' => $dados['nome'],
        'motorista_tel'  => $dados['telefone']
    ]);
} else {
    // TUDO LIMPO
    echo json_encode(['bloqueado' => false]);
}
?>