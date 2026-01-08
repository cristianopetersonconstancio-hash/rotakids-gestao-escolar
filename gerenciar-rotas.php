<?php
session_start();
require 'config.php';

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$motorista_id = $_SESSION['id'];
$msg = "";

// 1. SELECIONAR A VAN (Mesma lógica das outras páginas)
$van_selecionada = isset($_GET['vid']) ? (int)$_GET['vid'] : 0;
$sql_vans = $conn->query("SELECT * FROM veiculos WHERE motorista_id = $motorista_id");
$minhas_vans = [];
while($v = $sql_vans->fetch_assoc()) {
    $minhas_vans[] = $v;
    if ($van_selecionada == 0) $van_selecionada = $v['id'];
}

// 2. SALVAR AS ROTAS (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['salvar_rota'])) {
    $vid_save = (int)$_POST['van_id_save'];
    
    // Arrays de IDs marcados
    $escolas_marcadas = isset($_POST['escolas']) ? $_POST['escolas'] : [];
    $bairros_marcados = isset($_POST['bairros']) ? $_POST['bairros'] : [];

    if ($vid_save > 0) {
        // A. Limpa rotas antigas
        $conn->query("DELETE FROM rota_escolas WHERE veiculo_id = $vid_save");
        $conn->query("DELETE FROM rota_bairros WHERE veiculo_id = $vid_save");

        // B. Salva Escolas
        if (count($escolas_marcadas) > 0) {
            $stmt_e = $conn->prepare("INSERT INTO rota_escolas (veiculo_id, escola_id) VALUES (?, ?)");
            foreach ($escolas_marcadas as $e_id) {
                $e_id = (int)$e_id;
                $stmt_e->bind_param("ii", $vid_save, $e_id);
                $stmt_e->execute();
            }
        }

        // C. Salva Bairros
        if (count($bairros_marcados) > 0) {
            $stmt_b = $conn->prepare("INSERT INTO rota_bairros (veiculo_id, bairro_id) VALUES (?, ?)");
            foreach ($bairros_marcados as $b_id) {
                $b_id = (int)$b_id;
                $stmt_b->bind_param("ii", $vid_save, $b_id);
                $stmt_b->execute();
            }
        }
        $msg = "<div class='msg-sucesso'>Rota atualizada com sucesso!</div>";
        $van_selecionada = $vid_save; // Mantém a van
    }
}

// 3. BUSCAR DADOS PARA EXIBIÇÃO

// A. Escolas (Busca todas as escolas vinculadas ao motorista na tabela motorista_escolas)
// (Assumindo que você tem uma tabela ligando motorista a escolas gerais, ou pegando todas as escolas cadastradas)
// Para simplificar, vou pegar TODAS as escolas cadastradas no sistema ou as que o motorista já usou.
// Ajuste conforme sua tabela de escolas. Aqui pego TODAS escolas disponíveis.
$lista_escolas = $conn->query("SELECT * FROM escolas ORDER BY nome ASC");

// B. Bairros (Aqui estava o problema! Vamos pegar ONDE cidade_id = 1)
$lista_bairros = $conn->query("SELECT * FROM bairros WHERE cidade_id = 1 ORDER BY nome ASC");

// C. O que já está marcado nessa Van?
$rota_escolas_ids = [];
$rota_bairros_ids = [];

if ($van_selecionada > 0) {
    // Escolas da van
    $res_re = $conn->query("SELECT escola_id FROM rota_escolas WHERE veiculo_id = $van_selecionada");
    while($r = $res_re->fetch_assoc()) $rota_escolas_ids[] = $r['escola_id'];

    // Bairros da van
    $res_rb = $conn->query("SELECT bairro_id FROM rota_bairros WHERE veiculo_id = $van_selecionada");
    while($r = $res_rb->fetch_assoc()) $rota_bairros_ids[] = $r['bairro_id'];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Rotas - RotaKids</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .container-duplo {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        @media (max-width: 768px) {
            .container-duplo { grid-template-columns: 1fr; }
        }

        .box-lista {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            max-height: 500px;
            overflow-y: auto;
        }
        
        .titulo-box {
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
            display: flex;
            align-items: center;
        }

        .item-check {
            display: flex;
            align-items: center;
            padding: 8px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
        }
        .item-check:hover { background: #f9f9f9; }
        .item-check input { width: 18px; height: 18px; margin-right: 10px; cursor: pointer; }
        .item-check label { cursor: pointer; flex: 1; font-size: 0.95rem; }

        /* Cores dos Títulos */
        .tit-escola { color: #007bff; border-bottom-color: #007bff; }
        .tit-bairro { color: #b78900; border-bottom-color: #b78900; }

        /* Botão Salvar Gigante */
        .btn-salvar-rota {
            background-color: var(--van-preto);
            color: var(--van-amarelo);
            width: 100%;
            padding: 15px;
            font-size: 1.1rem;
            font-weight: bold;
            text-transform: uppercase;
            border: none;
            border-radius: 5px;
            margin-top: 20px;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-salvar-rota:hover {
            background-color: black;
            transform: scale(1.01);
        }
    </style>
    <script>
        function mudarVan() {
            var id = document.getElementById('select_van').value;
            window.location.href = 'gerenciar-rotas.php?vid=' + id;
        }
    </script>
</head>
<body>

    <?php include 'menu.php'; ?>

    <div class="container" style="padding: 20px; max-width: 1000px; margin: 0 auto;">
        
        <?php echo $msg; ?>

        <div style="background: #e9ecef; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 5px solid var(--van-amarelo); display: flex; align-items: center; justify-content: space-between;">
            <label style="font-weight: bold; font-size: 1.1rem;">🛠️ Configurar Rota da Van:</label>
            <select id="select_van" onchange="mudarVan()" style="padding: 10px; width: 100%; max-width: 400px; border: 2px solid #ccc; border-radius: 4px;">
                <?php foreach($minhas_vans as $mv): 
                    $selected = ($mv['id'] == $van_selecionada) ? 'selected' : '';
                ?>
                    <option value="<?php echo $mv['id']; ?>" <?php echo $selected; ?>>
                        🚐 <?php echo $mv['modelo']; ?> (<?php echo $mv['placa']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <form method="POST">
		
            <input type="hidden" name="salvar_rota" value="1">
            <input type="hidden" name="van_id_save" value="<?php echo $van_selecionada; ?>">
            <div style="margin-bottom: 15px; padding: 10px; background: #fff3cd; color: #856404; border-radius: 4px; border: 1px solid #ffeeba; font-size: 0.9rem;">
        <strong>Dica:</strong> Se o bairro não estiver na lista, ele aparecerá automaticamente assim que você cadastrar um aluno naquele endereço.
    </div>
            <div class="container-duplo">
                
                <div class="box-lista">
                    <div class="titulo-box tit-escola">🎓 Escolas Atendidas</div>
                    <?php 
                    if ($lista_escolas->num_rows > 0):
                        while($esc = $lista_escolas->fetch_assoc()):
                            $chk = in_array($esc['id'], $rota_escolas_ids) ? 'checked' : '';
                    ?>
                        <div class="item-check">
                            <input type="checkbox" name="escolas[]" value="<?php echo $esc['id']; ?>" id="e_<?php echo $esc['id']; ?>" <?php echo $chk; ?>>
                            <label for="e_<?php echo $esc['id']; ?>">
                                <?php echo $esc['nome']; ?>
                            </label>
                        </div>
                    <?php 
                        endwhile;
                    else: 
                        echo "<p style='color:#777'>Nenhuma escola cadastrada.</p>";
                    endif; 
                    ?>
                </div>

                <div class="box-lista">
                    <div class="titulo-box tit-bairro">🏘️ Bairros Atendidos</div>
                    <?php 
                    if ($lista_bairros->num_rows > 0):
                        while($bai = $lista_bairros->fetch_assoc()):
                            $chk = in_array($bai['id'], $rota_bairros_ids) ? 'checked' : '';
                    ?>
                        <div class="item-check">
                            <input type="checkbox" name="bairros[]" value="<?php echo $bai['id']; ?>" id="b_<?php echo $bai['id']; ?>" <?php echo $chk; ?>>
                            <label for="b_<?php echo $bai['id']; ?>">
                                <?php echo $bai['nome']; ?>
                            </label>
                        </div>
                    <?php 
                        endwhile;
                    else: 
                        echo "<p style='color:#777; padding: 20px; text-align:center;'>Nenhum bairro encontrado.<br><small>Cadastre um aluno para adicionar bairros automaticamente.</small></p>";
                    endif; 
                    ?>
                </div>

            </div>

            <button type="submit" class="btn-salvar-rota">💾 Salvar Rota da Van</button>

        </form>

    </div>

</body>
</html>