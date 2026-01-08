<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$pagina_atual = basename($_SERVER['PHP_SELF']);
?>

<header class="main-header">
    <div class="header-logo">
        <a href="#">
            <h1 style="margin:0; font-size: 1.4rem; letter-spacing: 1px;">
                🚌 Rota<span style="color: var(--van-amarelo);">Kids</span>
            </h1>
            <small style="color: #ccc; font-size: 0.8rem;">Olá, <?php echo $_SESSION['nome'] ?? 'Motorista'; ?></small>
        </a>
    </div>

    <div class="header-actions">
        <a href="minha-rota.php" class="btn-nav btn-escolas <?php echo ($pagina_atual == 'minha-rota.php') ? 'ativo' : ''; ?>">
            🏫 Escolas
        </a>

        <a href="meus-bairros.php" class="btn-nav btn-escolas <?php echo ($pagina_atual == 'meus-bairros.php') ? 'ativo' : ''; ?>">
            🏘️ Bairros
        </a>

        <a href="meu-veiculo.php" class="btn-nav btn-escolas <?php echo ($pagina_atual == 'meu-veiculo.php') ? 'ativo' : ''; ?>">
            🚐 Frota
        </a>
        
        <a href="alugar-van.php" class="btn-nav btn-escolas <?php echo ($pagina_atual == 'alugar-van.php') ? 'ativo' : ''; ?>">
            🤝 Alugar
        </a>
        
        <a href="negocios.php" class="btn-nav btn-escolas" >
            💰 Negócios
        </a>
        
        <a href="cadastrar-aluno.php" class="btn-nav btn-escolas <?php echo ($pagina_atual == 'cadastrar-aluno.php') ? 'ativo' : ''; ?>">
            👥 Alunos
        </a>

        <a href="logout.php" class="btn-nav btn-sair-menu">
            Sair
        </a>
    </div>
</header>