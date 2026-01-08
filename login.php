<?php
session_start();
require 'config.php';

if (isset($_SESSION['id'])) {
    header("Location: painel.php");
    exit;
}

$erro = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $senha = $_POST['senha'];

    $sql = "SELECT * FROM motoristas WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
        if (password_verify($senha, $usuario['senha'])) {
            $_SESSION['id'] = $usuario['id'];
            $_SESSION['nome'] = $usuario['nome'];
            $_SESSION['email'] = $usuario['email'];
            header("Location: painel.php");
            exit;
        } else {
            $erro = "Senha incorreta!";
        }
    } else {
        $erro = "Usuário não encontrado!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login Motorista - RotaKids</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        body {
            /* MUDANÇA AQUI: Cor muito mais clara e agradável */
            background-color: #fff3cd; 
            /* Se quiser um pouquinho mais de vida, use: #ffe066 */
            
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: sans-serif;
        }
        .auth-card {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
            border-top: 5px solid #ffc107; /* Detalhe amarelo mais forte só na borda */
        }
        .logo-login {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            display: block;
            text-decoration: none;
        }
        .link-voltar {
            display: inline-block; margin-top: 20px; color: #666; text-decoration: none; font-size: 0.9rem; transition: 0.3s;
        }
        .link-voltar:hover { color: #333; text-decoration: underline; }
    </style>
</head>
<body>

    <div class="auth-card">
        <div class="logo-login">
            🚌 Rota<span style="color: #ffc107;">Kids</span>
        </div>
        
        <h3 style="margin-bottom: 20px; color: #555;">Acesso Motorista</h3>

        <?php if($erro): ?>
            <div class="msg-erro" style="background:#f8d7da; color:#721c24; padding:10px; border-radius:5px; margin-bottom:15px;"><?php echo $erro; ?></div>
        <?php endif; ?>

        <form method="POST">
            <label style="text-align: left; display: block; font-weight: bold; margin-bottom: 5px; color:#555;">Email</label>
            <input type="email" name="email" required placeholder="seu@email.com" style="width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box;">

            <label style="text-align: left; display: block; font-weight: bold; margin-bottom: 5px; color:#555;">Senha</label>
            <input type="password" name="senha" required placeholder="******" style="width: 100%; padding: 12px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box;">

            <button type="submit" style="width: 100%; padding: 12px; background: #333; color: #ffc107; border: none; font-weight: bold; border-radius: 5px; cursor: pointer; font-size: 1rem;">
                ENTRAR
            </button>
        </form>

        <a href="index.php" class="link-voltar">⬅ Voltar para o Início</a>
        
        <div style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px;">
            <small>Ainda não tem conta? <a href="cadastro.php" style="color: #333; font-weight: bold;">Cadastre-se</a></small>
        </div>
    </div>

</body>
</html>