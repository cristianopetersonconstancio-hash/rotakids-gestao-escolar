<?php
session_start();
require 'config.php';

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$motorista_id = $_SESSION['id'];
$msg = "";
$veiculo_editar = null;

// --- 1. EXCLUSÃO ---
if (isset($_GET['excluir'])) {
    $id_del = (int)$_GET['excluir'];
    $conn->query("DELETE FROM veiculos WHERE id = $id_del AND motorista_id = $motorista_id");
    $msg = "<div class='msg-sucesso'>Veículo excluído!</div>";
}

// --- 2. PREPARAR EDIÇÃO ---
if (isset($_GET['editar'])) {
    $id_edit = (int)$_GET['editar'];
    $res = $conn->query("SELECT * FROM veiculos WHERE id = $id_edit AND motorista_id = $motorista_id");
    $veiculo_editar = $res->fetch_assoc();
}

// --- 3. SALVAR (INSERT OU UPDATE) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_atual = $_POST['id_veiculo'];
    $modelo = trim($_POST['modelo']);
    $placa = trim($_POST['placa']);
    $ano = !empty($_POST['ano']) ? (int)$_POST['ano'] : NULL; // Novo campo
    $capacidade = (int)$_POST['capacidade'];
    $alugar = isset($_POST['alugar']) ? 1 : 0; 

    if (!empty($id_atual)) {
        // UPDATE
        // Adicionado 'ano=?' e o tipo 'i' (inteiro) no bind_param
        $stmt = $conn->prepare("UPDATE veiculos SET modelo=?, placa=?, ano=?, capacidade=?, alugar=? WHERE id=? AND motorista_id=?");
        $stmt->bind_param("ssiiiis", $modelo, $placa, $ano, $capacidade, $alugar, $id_atual, $motorista_id);
        
        if ($stmt->execute()) {
            $msg = "<div class='msg-sucesso'>Veículo atualizado!</div>";
            $veiculo_editar = null;
        }
    } else {
        // INSERT
        // Adicionado 'ano' e o tipo 'i'
        $stmt = $conn->prepare("INSERT INTO veiculos (motorista_id, modelo, placa, ano, capacidade, alugar) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issiii", $motorista_id, $modelo, $placa, $ano, $capacidade, $alugar);
        
        if ($stmt->execute()) {
            $msg = "<div class='msg-sucesso'>Veículo cadastrado!</div>";
        }
    }
}

// --- LISTAGEM ---
$meus_veiculos = $conn->query("SELECT * FROM veiculos WHERE motorista_id = $motorista_id");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Frota - RotaKids</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        /* Estilos específicos para botões desta página */
        .btn-acao {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: bold;
            display: inline-block;
            margin-left: 5px;
        }
        
        .btn-editar {
            background-color: #007bff;
            color: white;
            border: 1px solid #007bff;
        }
        .btn-editar:hover { background-color: #0056b3; }

        .btn-excluir {
            background-color: #dc3545;
            color: white;
            border: 1px solid #dc3545;
        }
        .btn-excluir:hover { background-color: #a71d2a; }

        .btn-principal {
            background-color: var(--van-preto, #333); 
            color: var(--van-amarelo, #ffc107); 
            padding: 10px 20px; 
            border: none; 
            border-radius: 5px; 
            font-size: 1rem; 
            cursor: pointer;
        }
        
        .tag-aluguel {
            background: #28a745; 
            color: white; 
            padding: 3px 8px; 
            border-radius: 4px; 
            font-size: 0.75rem;
            font-weight: bold;
            margin-top: 5px;
            display: inline-block;
        }
    </style>
    <script>
        function toggleFormulario() {
            var box = document.getElementById('box-formulario');
            box.style.display = (box.style.display === 'none') ? 'block' : 'none';
        }
        
        // Remove msg de sucesso após 3 segundos
        document.addEventListener("DOMContentLoaded", function() {
            var mensagem = document.querySelector('.msg-sucesso');
            if (mensagem) {
                setTimeout(function() {
                    mensagem.style.display = "none";
                }, 3000);
            }
        });
    </script>
</head>
<body>

    <?php include 'menu.php'; ?>

    <div class="container" style="padding: 20px; max-width: 800px; margin: 0 auto;">
        
        <?php echo $msg; ?>

        <?php if(!$veiculo_editar): ?>
            <button onclick="toggleFormulario()" class="btn-principal">
                + Nova Van
            </button>
        <?php endif; ?>

        <div id="box-formulario" class="auth-card" 
             style="max-width: 100%; border-left: 5px solid var(--van-preto, #333); margin-bottom: 30px; margin-top: 20px;
             display: <?php echo ($veiculo_editar) ? 'block' : 'none'; ?>;">
            
            <h3><?php echo ($veiculo_editar) ? "Editar Veículo" : "Cadastrar Veículo"; ?></h3>
            
            <form method="POST">
                <input type="hidden" name="id_veiculo" value="<?php echo $veiculo_editar['id'] ?? ''; ?>">

                <div style="display:grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                    <div>
                        <label>Modelo da Van</label>
                        <input type="text" name="modelo" required value="<?php echo $veiculo_editar['modelo'] ?? ''; ?>" placeholder="Ex: Ducato">
                    </div>
                    <div>
                        <label>Ano Fab.</label>
                        <input type="number" name="ano" min="1990" max="<?php echo date('Y')+1; ?>" 
                               value="<?php echo $veiculo_editar['ano'] ?? ''; ?>" placeholder="2020">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
                    <div>
                        <label>Placa</label>
                        <input type="text" name="placa" required value="<?php echo $veiculo_editar['placa'] ?? ''; ?>">
                    </div>
                    <div>
                        <label>Capacidade (Lugares)</label>
                        <input type="number" name="capacidade" required value="<?php echo $veiculo_editar['capacidade'] ?? ''; ?>">
                    </div>
                </div>

                <div style="margin-top: 15px; background: #e2e6ea; padding: 10px; border-radius: 5px;">
                    <label style="cursor: pointer; display: flex; align-items: center; font-weight: bold; color: #333;">
                        <input type="checkbox" name="alugar" value="1" style="width: 20px; height: 20px; margin-right: 10px;"
                        <?php echo ($veiculo_editar && $veiculo_editar['alugar'] == 1) ? 'checked' : ''; ?>>
                        📢 Disponibilizar esta van para aluguel?
                    </label>
                    <small style="margin-left: 30px; color: #666;">Se marcar, outros motoristas verão seu contato.</small>
                </div>

                <br>
                <button type="submit" class="btn-principal"><?php echo ($veiculo_editar) ? "Salvar Alterações" : "Cadastrar"; ?></button>
                
                <?php if($veiculo_editar): ?>
                    <a href="meu-veiculo.php" style="display:block; text-align:center; margin-top:10px; color:#555;">Cancelar</a>
                <?php endif; ?>
            </form>
        </div>

        <h3 style="margin-top: 30px; border-bottom: 1px solid #ccc; padding-bottom: 10px;">Minha Frota</h3>

        <?php while($v = $meus_veiculos->fetch_assoc()): ?>
            <div class="auth-card" style="max-width: 100%; margin-bottom: 15px; padding: 15px; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid #ddd;">
                <div>
                    <strong style="font-size: 1.1rem;"><?php echo $v['modelo']; ?></strong> 
                    <?php if(!empty($v['ano'])): ?>
                        <span style="color: #555; font-size: 0.9rem;"> • <?php echo $v['ano']; ?></span>
                    <?php endif; ?>
                    
                    <div style="margin-top: 4px;">
                        <span style="color: #666;">Placa: <?php echo $v['placa']; ?></span>
                    </div>
                    
                    <small style="color: #888;"><?php echo $v['capacidade']; ?> lugares</small>
                    
                    <?php if($v['alugar']): ?>
                        <br><span class="tag-aluguel">Disponível para Alugar</span>
                    <?php endif; ?>
                </div>
                
                <div>
                    <a href="?editar=<?php echo $v['id']; ?>" class="btn-acao btn-editar">✏️ Editar</a>
                    <a href="?excluir=<?php echo $v['id']; ?>" onclick="return confirm('Excluir van?');" class="btn-acao btn-excluir">🗑️ Excluir</a>
                </div>
            </div>
        <?php endwhile; ?>

    </div>
</body>
</html>