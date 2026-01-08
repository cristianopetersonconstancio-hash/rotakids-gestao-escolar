<?php
require 'config.php';

$erro = "";
$sucesso = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Usamos trim() para remover espaços vazios antes e depois
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $telefone = trim($_POST['telefone']);
    $senha = trim($_POST['senha']);
    $confirma_senha = trim($_POST['confirma_senha']);

    // Validações básicas
    if (empty($nome) || empty($email) || empty($senha)) {
        $erro = "Preencha todos os campos obrigatórios.";
    } elseif ($senha !== $confirma_senha) {
        $erro = "As senhas não coincidem!";
    } else {
        // --- CORREÇÃO DE SEGURANÇA AQUI ---
        // Verifica se e-mail já existe usando Prepared Statement
        $stmt_check = $conn->prepare("SELECT id FROM motoristas WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $result = $stmt_check->get_result();

        if ($result->num_rows > 0) {
            $erro = "Este e-mail já está cadastrado.";
        } else {
            // Criptografa a senha e insere
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            
            // Insere o novo motorista
            $stmt = $conn->prepare("INSERT INTO motoristas (nome, email, telefone, senha) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nome, $email, $telefone, $senha_hash);

            if ($stmt->execute()) {
                // Link para login direto na mensagem
                $sucesso = "Cadastro realizado com sucesso! <br><a href='login.php'><strong>Clique aqui para entrar</strong></a>";
            } else {
                $erro = "Erro ao cadastrar: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro - RotaKids</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body class="auth-page"> <div class="auth-card"> <h2>Criar Conta</h2>
        
        <?php if(!empty($erro)): ?>
            <div class="msg-erro"><?php echo $erro; ?></div>
        <?php endif; ?>

        <?php if(!empty($sucesso)): ?>
            <div class="msg-sucesso"><?php echo $sucesso; ?></div>
        <?php endif; ?>

        <?php if(empty($sucesso)): ?>
        <form method="POST">
            <label>Nome Completo</label>
            <input type="text" name="nome" required>

            <label>E-mail</label>
            <input type="email" name="email" required>
            
            <label>Telefone (WhatsApp)</label>
            <input type="text" name="telefone" placeholder="(11) 99999-9999" required>

            <label>Senha</label>
            <input type="password" name="senha" required>

            <label>Confirmar Senha</label>
            <input type="password" name="confirma_senha" required>

            <button type="submit">Cadastrar Minha Van</button>
        </form>
        <?php endif; ?>
        
        <a href="login.php" class="auth-link">Já tenho conta</a>
    </div>

</body>
</html>