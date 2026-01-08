<?php
require 'config.php';
session_start();

// BUSCAR DADOS PARA OS FILTROS (Agrupando por nome para não repetir)

// 1. Escolas: Pega nomes únicos de escolas que tenham rotas ativas
$sql_escolas = "SELECT DISTINCT e.nome 
                FROM escolas e 
                JOIN rota_escolas re ON e.id = re.escola_id 
                ORDER BY e.nome ASC";
$res_escolas = $conn->query($sql_escolas);

// 2. Bairros: Pega TODOS os bairros cadastrados no banco (sem exceção)
// Removemos JOIN e WHERE para listar tudo
$sql_bairros = "SELECT DISTINCT nome 
                FROM bairros 
                ORDER BY nome ASC";

$res_bairros = $conn->query($sql_bairros);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RotaKids - Transporte Escolar</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        body { margin: 0; padding: 0; background-color: #f9f9f9; font-family: 'Segoe UI', sans-serif; }
        
        .navbar-home {
            display: flex; justify-content: space-between; align-items: center;
            padding: 20px 40px; background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .logo-home { font-size: 1.5rem; font-weight: bold; color: #333; text-decoration: none; }
        .btn-login-home {
            background-color: #333; color: #ffc107;
            padding: 10px 25px; border-radius: 30px; text-decoration: none; font-weight: bold;
            transition: 0.3s;
        }
        .btn-login-home:hover { transform: scale(1.05); background-color: black; }

        .hero {
            background: linear-gradient(135deg, #fff8e1 0%, #ffe082 100%);
            padding: 100px 20px; text-align: center; color: #333;
        }
        .hero h1 { font-size: 3rem; margin-bottom: 10px; color: #2d3436; }
        .hero p { font-size: 1.2rem; margin-bottom: 40px; opacity: 0.8; font-weight: 500; }

        /* Ajuste do box para caber mais campos */
        .busca-box {
            background: white; padding: 30px; border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            max-width: 900px;
            margin: 0 auto;
            display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;
        }
        .campo-busca { flex: 1; min-width: 150px; text-align: left; }
        .campo-busca label { display: block; font-weight: bold; margin-bottom: 5px; color: #666; }
        .campo-busca select, .campo-busca input {
            width: 100%; padding: 15px; border: 1px solid #ddd; border-radius: 8px; background: #fff;
            font-size: 1rem; color: #333; box-sizing: border-box;
        }
        .btn-buscar {
            background-color: #333; color: #ffc107; border: none;
            padding: 16px 40px; border-radius: 8px; font-size: 1.1rem; font-weight: bold; cursor: pointer;
            transition: 0.2s;
        }
        .btn-buscar:hover { background-color: black; }
        
        .footer-home { text-align: center; padding: 40px; color: #999; font-size: 0.9rem; }
    </style>
</head>
<body>

    <nav class="navbar-home">
        <a href="index.php" class="logo-home">
            🚌 Rota<span style="color: #fbc531;">Kids</span>
        </a>
        <div>
            <?php if(isset($_SESSION['id'])): ?>
                <a href="painel.php" class="btn-login-home">Acessar Painel</a>
            <?php else: ?>
                <a href="login.php" class="btn-login-home">Sou Motorista</a>
            <?php endif; ?>
        </div>
    </nav>

    <section class="hero">
        <h1>Encontre a van escolar ideal.</h1>
        <p>Conectamos pais e alunos aos melhores motoristas da região.</p>

        <form action="busca.php" method="GET">
            <div class="busca-box">
                
                <div class="campo-busca" style="flex: 2;">
                    <label>🏫 Escola</label>
                    <select name="escola_nome" required>
                        <option value="" disabled selected>Selecione...</option>
                        <?php 
                        if ($res_escolas) {
                            while($e = $res_escolas->fetch_assoc()): 
                        ?>
                            <option value="<?php echo htmlspecialchars($e['nome']); ?>">
                                <?php echo htmlspecialchars($e['nome']); ?>
                            </option>
                        <?php 
                            endwhile; 
                        }
                        ?>
                    </select>
                </div>

                <div class="campo-busca" style="flex: 2;">
                    <label>🏘️ Bairro</label>
                    <select name="bairro_nome" required>
                        <option value="" disabled selected>Selecione...</option>
                        <?php 
                        if ($res_bairros) {
                            while($b = $res_bairros->fetch_assoc()): 
                        ?>
                            <option value="<?php echo htmlspecialchars($b['nome']); ?>">
                                <?php echo htmlspecialchars($b['nome']); ?>
                            </option>
                        <?php 
                            endwhile;
                        }
                        ?>
                    </select>
                </div>

                <div class="campo-busca">
                    <label>🌞 Turno</label>
                    <select name="turno" required>
                        <option value="Manhã">Manhã</option>
                        <option value="Tarde">Tarde</option>
                        <option value="Noite">Noite</option>
                        <option value="Integral">Integral</option>
                    </select>
                </div>

                <div class="campo-busca" style="min-width: 80px; flex: 0.5;">
                    <label>👶 Vagas</label>
                    <input type="number" name="vagas" value="1" min="1" required>
                </div>

                <button type="submit" class="btn-buscar">🔍</button>
            </div>
        </form>
    </section>

    <div class="footer-home">
        &copy; <?php echo date('Y'); ?> RotaKids
    </div>

</body>
</html>