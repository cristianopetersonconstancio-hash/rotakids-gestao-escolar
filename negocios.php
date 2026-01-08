<?php
session_start();
require 'config.php';

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$motorista_id = $_SESSION['id'];
$msg = "";

// --- 0. MENSAGENS DE FEEDBACK (Vinda do Redirecionamento) ---
if (isset($_GET['sucesso'])) {
    if ($_GET['sucesso'] == 'cadastro') {
        $msg = "<div class='msg-sucesso'>Anúncio publicado com sucesso!</div>";
    }
    if ($_GET['sucesso'] == 'exclusao') {
        $msg = "<div class='msg-sucesso'>Anúncio removido!</div>";
    }
}

// --- 1. EXCLUIR MEU ANÚNCIO ---
if (isset($_GET['del_id'])) {
    $id_del = (int)$_GET['del_id'];
    $conn->query("DELETE FROM classificados_vans WHERE id=$id_del AND motorista_id=$motorista_id");
    
    // REDIRECIONA PARA LIMPAR A URL
    header("Location: negocios.php?sucesso=exclusao");
    exit;
}

// --- 2. SALVAR NOVO ANÚNCIO (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['anunciar'])) {
    $modelo = trim($_POST['modelo']);
    $ano = (int)$_POST['ano'];
    $lotacao = (int)$_POST['lotacao'];
    $cor = trim($_POST['cor']);
    $contato = trim($_POST['contato']);
    $obs = trim($_POST['observacoes']);
    $adesivado = isset($_POST['adesivado']) ? 1 : 0;
    
    // TRATAMENTO DO VALOR
    $valor_br = $_POST['valor']; 
    $valor_limpo = str_replace(['R$', ' ', '.'], '', $valor_br);
    $valor_db = str_replace(',', '.', $valor_limpo);
    $valor = (float)$valor_db;

    if(empty($modelo) || empty($contato)) {
        $msg = "<div class='msg-erro'>Preencha pelo menos o modelo e o contato.</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO classificados_vans (motorista_id, modelo, ano, lotacao, cor, adesivado, observacoes, contato, valor) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("isiisissd", $motorista_id, $modelo, $ano, $lotacao, $cor, $adesivado, $obs, $contato, $valor);
        
        if ($stmt->execute()) {
            // AQUI ESTÁ A MÁGICA: Redireciona para evitar reenvio no F5
            header("Location: negocios.php?sucesso=cadastro");
            exit;
        } else {
            $msg = "<div class='msg-erro'>Erro ao anunciar: " . $conn->error . "</div>";
        }
    }
}

// --- 3. BUSCAR ANÚNCIOS ---
$sql_vendas = "SELECT c.*, m.nome as vendedor 
               FROM classificados_vans c 
               JOIN motoristas m ON c.motorista_id = m.id 
               ORDER BY c.data_anuncio DESC";
$lista_vendas = $conn->query($sql_vendas);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Comprar e Vender Vans - RotaKids</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .painel-botoes {
            display: flex; gap: 20px; margin-bottom: 30px; justify-content: center;
        }
        .btn-grande {
            padding: 20px 40px; font-size: 1.2rem; border-radius: 8px; cursor: pointer;
            border: 2px solid #ccc; background: white; font-weight: bold; color: #555;
            transition: all 0.3s; flex: 1; text-align: center; max-width: 300px;
        }
        .btn-grande.ativo-vender {
            background-color: var(--van-preto); color: var(--van-amarelo); border-color: var(--van-preto);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .btn-grande.ativo-comprar {
            background-color: #28a745; color: white; border-color: #28a745;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .card-venda {
            background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden; margin-bottom: 20px; border-top: 5px solid #28a745;
            display: flex; flex-direction: column;
        }
        .card-header {
            padding: 15px; background: #f8f9fa; border-bottom: 1px solid #eee;
            display: flex; justify-content: space-between; align-items: center;
        }
        .card-body { padding: 20px; }
        .tag-preco {
            background: #28a745; color: white; padding: 5px 10px; border-radius: 5px;
            font-weight: bold; font-size: 1.1rem;
        }
        .detalhe-item {
            display: inline-block; margin-right: 15px; color: #555; font-size: 0.95rem; margin-bottom: 10px;
        }
        .adesivo-sim { color: #28a745; font-weight: bold; }
        .adesivo-nao { color: #dc3545; font-weight: bold; }
    </style>
    <script>
        function mostrarAba(aba) {
            document.getElementById('area-vender').style.display = 'none';
            document.getElementById('area-comprar').style.display = 'none';
            document.getElementById('btn-vender').classList.remove('ativo-vender');
            document.getElementById('btn-comprar').classList.remove('ativo-comprar');

            if (aba === 'vender') {
                document.getElementById('area-vender').style.display = 'block';
                document.getElementById('btn-vender').classList.add('ativo-vender');
            } else {
                document.getElementById('area-comprar').style.display = 'block';
                document.getElementById('btn-comprar').classList.add('ativo-comprar');
            }
        }

        function formatarMoeda(i) {
            var v = i.value.replace(/\D/g,'');
            v = (v/100).toFixed(2) + '';
            v = v.replace(".", ",");
            v = v.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");
            i.value = "R$ " + v;
        }
        
        // Remove msg de sucesso da URL após alguns segundos
        document.addEventListener("DOMContentLoaded", function() {
            // Se tiver mensagem de sucesso, vamos limpar a URL para ficar bonita
            if(window.location.search.includes('sucesso=')) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
            
            var mensagem = document.querySelector('.msg-sucesso');
            if (mensagem) {
                setTimeout(function() { mensagem.style.display = 'none'; }, 4000);
            }
        });
    </script>
</head>
<body>

    <?php include 'menu.php'; ?>

    <div class="container" style="padding: 20px; max-width: 900px; margin: 0 auto;">
        
        <?php echo $msg; ?>

        <div class="painel-botoes">
            <div id="btn-comprar" class="btn-grande ativo-comprar" onclick="mostrarAba('comprar')">
                🛒 Quero Comprar
            </div>
            <div id="btn-vender" class="btn-grande" onclick="mostrarAba('vender')">
                💰 Quero Vender
            </div>
        </div>

        <div id="area-comprar">
            <h2 style="margin-bottom: 20px; color: #555;">Vans à Venda</h2>
            
            <?php if($lista_vendas->num_rows > 0): ?>
                <div class="grid-vans">
                    <?php while($item = $lista_vendas->fetch_assoc()): 
                         $zap = preg_replace("/[^0-9]/", "", $item['contato']);
                         $is_meu = ($item['motorista_id'] == $motorista_id);
                    ?>
                        <div class="card-venda" style="<?php echo $is_meu ? 'border-top-color: var(--van-amarelo);' : ''; ?>">
                            <div class="card-header">
                                <div>
                                    <h3 style="margin:0;"><?php echo $item['modelo']; ?></h3>
                                    <small style="color:#777;">Anunciado por: <?php echo $is_meu ? 'VOCÊ' : $item['vendedor']; ?></small>
                                </div>
                                <?php if($item['valor'] > 0): ?>
                                    <span class="tag-preco">R$ <?php echo number_format($item['valor'], 2, ',', '.'); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="card-body">
                                <div style="margin-bottom: 15px;">
                                    <span class="detalhe-item">📅 Ano: <strong><?php echo $item['ano']; ?></strong></span>
                                    <span class="detalhe-item">👥 Lotação: <strong><?php echo $item['lotacao']; ?> lug.</strong></span>
                                    <span class="detalhe-item">🎨 Cor: <strong><?php echo $item['cor']; ?></strong></span>
                                    <br>
                                    <span class="detalhe-item">
                                        Escolar: 
                                        <?php if($item['adesivado']): ?>
                                            <span class="adesivo-sim">✅ Sim</span>
                                        <?php else: ?>
                                            <span class="adesivo-nao">❌ Não</span>
                                        <?php endif; ?>
                                    </span>
                                </div>

                                <p style="background: #f9f9f9; padding: 10px; border-radius: 5px; font-style: italic; color: #555; border: 1px solid #eee;">
                                    "<?php echo nl2br($item['observacoes']); ?>"
                                </p>

                                <div style="margin-top: 20px; text-align: center;">
                                    <?php if($is_meu): ?>
                                        <a href="?del_id=<?php echo $item['id']; ?>" class="btn-excluir" style="padding: 10px 20px; width: 100%; display:block;" onclick="return confirm('Remover anúncio?');">
                                            🗑️ Remover meu anúncio
                                        </a>
                                    <?php else: ?>
                                        <a href="https://wa.me/55<?php echo $zap; ?>?text=Olá, vi sua van <?php echo $item['modelo']; ?> à venda." target="_blank" class="btn-novo" style="background-color:#25d366; border-color:#25d366; color:white; width: 100%; display:block;">
                                            📱 Chamar no WhatsApp
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #777;">
                    <h3>Nenhuma van anunciada ainda.</h3>
                    <p>Seja o primeiro a anunciar clicando em "Quero Vender"!</p>
                </div>
            <?php endif; ?>
        </div>

        <div id="area-vender" style="display: none;">
            <div class="auth-card" style="max-width: 600px; margin: 0 auto; border-left: 5px solid var(--van-preto);">
                <h3>Anunciar Veículo</h3>
                
                <form method="POST">
                    <input type="hidden" name="anunciar" value="1">
                    
                    <label>Modelo do Veículo</label>
                    <input type="text" name="modelo" required placeholder="Ex: Mercedes Sprinter 415">

                    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                        <div>
                            <label>Ano</label>
                            <input type="number" name="ano" required placeholder="Ex: 2019">
                        </div>
                        <div>
                            <label>Lotação</label>
                            <input type="number" name="lotacao" required placeholder="Ex: 20">
                        </div>
                        <div>
                            <label>Cor</label>
                            <input type="text" name="cor" required placeholder="Ex: Prata">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label>Valor (R$)</label>
                            <input type="text" name="valor" onkeyup="formatarMoeda(this)" placeholder="R$ 0,00">
                        </div>
                        <div>
                            <label>Seu WhatsApp</label>
                            <?php 
                                $tel_user = $conn->query("SELECT telefone FROM motoristas WHERE id=$motorista_id")->fetch_assoc()['telefone'];
                            ?>
                            <input type="text" name="contato" required value="<?php echo $tel_user; ?>">
                        </div>
                    </div>

                    <div style="margin: 15px 0; background: #e9ecef; padding: 10px; border-radius: 5px;">
                        <label style="cursor: pointer; display: flex; align-items: center;">
                            <input type="checkbox" name="adesivado" value="1" style="width: 20px; height: 20px; margin-right: 10px;">
                            Possui adesivo/faixa escolar?
                        </label>
                    </div>

                    <label>Observações / Detalhes</label>
                    <textarea name="observacoes" rows="4" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 4px; margin-bottom: 15px;" placeholder="Ex: Motor feito recentemente, pneus novos, único dono..."></textarea>

                    <button type="submit">📢 Publicar Anúncio</button>
                </form>
            </div>
        </div>

    </div>

</body>
</html>