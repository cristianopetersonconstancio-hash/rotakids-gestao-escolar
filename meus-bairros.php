<?php
session_start();
require 'config.php';

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$motorista_id = $_SESSION['id'];
$msg = "";

// 1. IDENTIFICAR QUAL VAN ESTAMOS GERENCIANDO
$van_selecionada = isset($_GET['vid']) ? (int)$_GET['vid'] : 0;

// Busca lista de vans do motorista logado
$sql_vans = $conn->query("SELECT * FROM veiculos WHERE motorista_id = $motorista_id");
$minhas_vans = [];
while($v = $sql_vans->fetch_assoc()) {
    $minhas_vans[] = $v;
    if ($van_selecionada == 0) {
        $van_selecionada = $v['id'];
    }
}

// --- 2. NOVAS AÇÕES (PROCESSAMENTO) ---

// A. CADASTRO RÁPIDO VIA CEP
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btn_add_bairro_cep'])) {
    $vid_target = (int)$_POST['van_id_target'];
    $nome_bairro_novo = trim($_POST['novo_bairro_nome']);

    if (!empty($nome_bairro_novo) && $vid_target > 0) {
        
        // Verifica se o bairro já existe PARA ESTE MOTORISTA
        $stmt_check = $conn->prepare("SELECT id FROM bairros WHERE nome = ? AND motorista_id = ?");
        $stmt_check->bind_param("si", $nome_bairro_novo, $motorista_id);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();

        $id_bairro_final = 0;

        if ($res_check->num_rows > 0) {
            $id_bairro_final = $res_check->fetch_assoc()['id'];
            
            // Se já existia mas estava inativo (0), vamos reativar (1) agora que foi adicionado via CEP
            $conn->query("UPDATE bairros SET ativo = 1 WHERE id = $id_bairro_final");
        } else {
            // Cria novo bairro (já nasce ativo=1 pelo default do banco, mas garantimos aqui)
            $stmt_new = $conn->prepare("INSERT INTO bairros (nome, motorista_id, ativo) VALUES (?, ?, 1)");
            $stmt_new->bind_param("si", $nome_bairro_novo, $motorista_id);
            if ($stmt_new->execute()) {
                $id_bairro_final = $stmt_new->insert_id;
            } else {
                $msg = "<div class='msg-erro'>Erro ao criar bairro: " . $conn->error . "</div>";
            }
        }

        // Vincula à van
        if ($id_bairro_final > 0) {
            $check_rota = $conn->query("SELECT * FROM motorista_bairros WHERE veiculo_id = $vid_target AND bairro_id = $id_bairro_final");
            
            if ($check_rota->num_rows == 0) {
                $stmt_link = $conn->prepare("INSERT INTO motorista_bairros (motorista_id, veiculo_id, bairro_id) VALUES (?, ?, ?)");
                $stmt_link->bind_param("iii", $motorista_id, $vid_target, $id_bairro_final);
                
                if($stmt_link->execute()){
                    $msg = "<div class='msg-sucesso'>Bairro <strong>$nome_bairro_novo</strong> adicionado e ativado!</div>";
                } else {
                     $msg = "<div class='msg-erro'>Erro ao vincular: " . $conn->error . "</div>";
                }
            } else {
                $msg = "<div class='msg-erro'>Este bairro já faz parte da rota desta van.</div>";
            }
        }
        $van_selecionada = $vid_target;
    }
}

// B. SALVAR CHECKBOXES EM MASSA (LÓGICA ATIVO/INATIVO)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['salvar_rotas'])) {
    $vid_save = (int)$_POST['van_id_save'];
    // Array com IDs dos bairros que estão MARCADOS
    $bairros_marcados = isset($_POST['bairros']) ? $_POST['bairros'] : [];

    if ($vid_save > 0) {
        // 1. Removemos todos os vínculos dessa van primeiro (limpeza da rota)
        $conn->query("DELETE FROM motorista_bairros WHERE veiculo_id = $vid_save");

        // 2. Preparações para atualização
        $stmt_ativo = $conn->prepare("UPDATE bairros SET ativo = 1 WHERE id = ? AND motorista_id = ?");
        $stmt_inativo = $conn->prepare("UPDATE bairros SET ativo = 0 WHERE id = ? AND motorista_id = ?");
        $stmt_vincular = $conn->prepare("INSERT INTO motorista_bairros (motorista_id, veiculo_id, bairro_id) VALUES (?, ?, ?)");

        // 3. Buscamos TODOS os bairros deste motorista para verificar um por um
        $sql_todos = $conn->query("SELECT id FROM bairros WHERE motorista_id = $motorista_id");

        while($row = $sql_todos->fetch_assoc()) {
            $b_id = $row['id'];
            
            // Verifica se este ID está na lista de marcados enviada pelo formulário
            if (in_array($b_id, $bairros_marcados)) {
                // --- CASO MARCADO (CHECKED) ---
                
                // 1. Define como ATIVO (1) na tabela bairros
                $stmt_ativo->bind_param("ii", $b_id, $motorista_id);
                $stmt_ativo->execute();

                // 2. Cria o vínculo com a van
                $stmt_vincular->bind_param("iii", $motorista_id, $vid_save, $b_id);
                $stmt_vincular->execute();

            } else {
                // --- CASO DESMARCADO (UNCHECKED) ---
                
                // 1. Define como INATIVO (0) na tabela bairros
                // Obs: Como já deletamos do motorista_bairros lá em cima, não precisa deletar de novo.
                $stmt_inativo->bind_param("ii", $b_id, $motorista_id);
                $stmt_inativo->execute();
            }
        }

        $msg = "<div class='msg-sucesso'>Status dos bairros atualizado com sucesso!</div>";
        $van_selecionada = $vid_save;
    }
}

// 3. CARREGAR DADOS
// Mostra todos os bairros do motorista (Ativos e Inativos) para ele poder gerenciar
$sql_todos_bairros = "SELECT * FROM bairros WHERE motorista_id = $motorista_id ORDER BY nome ASC";
$res_bairros = $conn->query($sql_todos_bairros);

$bairros_ativos_van = [];
if ($van_selecionada > 0) {
    $res_rota = $conn->query("SELECT bairro_id FROM motorista_bairros WHERE veiculo_id = $van_selecionada");
    while($r = $res_rota->fetch_assoc()) {
        $bairros_ativos_van[] = $r['bairro_id'];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Meus Bairros - RotaKids</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .painel-bairros { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .grid-checkbox {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;
            max-height: 400px; overflow-y: auto; padding: 10px; border: 1px solid #eee; border-radius: 4px;
        }
        .item-bairro {
            background: #f8f9fa; padding: 10px; border-radius: 5px; border: 1px solid #ddd;
            display: flex; align-items: center; transition: 0.2s;
        }
        .item-bairro:hover { background: #e2e6ea; border-color: #adb5bd; }
        .item-bairro input { width: 20px; height: 20px; margin-right: 10px; cursor: pointer; margin-bottom: 0; }
        .item-bairro label { cursor: pointer; margin-bottom: 0; font-size: 0.95rem; width: 100%; }
        
        /* Estilo para item marcado/ativo */
        .item-bairro.ativo { background-color: #d4edda; border-color: #c3e6cb; color: #155724; font-weight: bold; }
        /* Estilo para item desmarcado/inativo (opcional, para diferenciar visualmente) */
        .item-bairro.inativo { opacity: 0.7; }

        .box-add-cep {
            background: #e7f1ff; padding: 20px; border-radius: 8px; border: 1px solid #b6d4fe; 
            margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;
        }
        .grupo-input { display: flex; flex-direction: column; flex: 1; min-width: 150px; }
        .grupo-input label { font-weight: bold; margin-bottom: 5px; color: #0d6efd; }
        .grupo-input input { padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .btn-add { background-color: #0d6efd; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; height: 42px; }
        
        .msg-sucesso { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .msg-erro { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
    </style>
    <script>
        function mudarVan() {
            var id = document.getElementById('select_van').value;
            window.location.href = 'meus-bairros.php?vid=' + id;
        }
        function toggleCor(checkbox) {
            var div = checkbox.parentNode;
            if(checkbox.checked) { 
                div.classList.add('ativo'); 
                div.classList.remove('inativo');
            } else { 
                div.classList.remove('ativo'); 
                div.classList.add('inativo');
            }
        }
        // CEP
        function limpa_formulário_cep() { document.getElementById('novo_bairro').value = ""; }
        function meu_callback(conteudo) {
            if (!("erro" in conteudo)) { document.getElementById('novo_bairro').value = conteudo.bairro; } 
            else { limpa_formulário_cep(); alert("CEP não encontrado."); }
        }
        function pesquisacep(valor) {
            var cep = valor.replace(/\D/g, '');
            if (cep != "") {
                var validacep = /^[0-9]{8}$/;
                if(validacep.test(cep)) {
                    document.getElementById('novo_bairro').value = "...";
                    var script = document.createElement('script');
                    script.src = 'https://viacep.com.br/ws/'+ cep + '/json/?callback=meu_callback';
                    document.body.appendChild(script);
                } else { limpa_formulário_cep(); alert("Formato inválido."); }
            } else { limpa_formulário_cep(); }
        };
        document.addEventListener("DOMContentLoaded", function() {
            var mensagem = document.querySelector('.msg-sucesso, .msg-erro');
            if (mensagem) { setTimeout(function() { mensagem.style.display = "none"; }, 4000); }
        });
    </script>
</head>
<body>
    <?php include 'menu.php'; ?>
    <div class="container" style="padding: 20px; max-width: 900px; margin: 0 auto;">
        
        <?php echo $msg; ?>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="color: #333; margin: 0;">Gerenciar Bairros Atendidos</h2>
        </div>

        <?php if(count($minhas_vans) > 0): ?>
            <div class="painel-bairros">
                
                <div style="background: #e9ecef; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 5px solid #ffc107;">
                    <label style="font-weight: bold;">Selecione a Van para editar a rota:</label>
                    <select id="select_van" onchange="mudarVan()" style="padding: 10px; width: 100%; max-width: 300px; border: 2px solid #ccc; border-radius: 4px;">
                        <?php foreach($minhas_vans as $mv): 
                            $selected = ($mv['id'] == $van_selecionada) ? 'selected' : ''; ?>
                            <option value="<?php echo $mv['id']; ?>" <?php echo $selected; ?>>
                                🚐 <?php echo $mv['modelo']; ?> (<?php echo $mv['placa']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <form method="POST">
                    <input type="hidden" name="btn_add_bairro_cep" value="1">
                    <input type="hidden" name="van_id_target" value="<?php echo $van_selecionada; ?>">
                    <div class="box-add-cep">
                        <div class="grupo-input">
                            <label>1. Digite o CEP:</label>
                            <input type="text" name="cep" placeholder="00000-000" maxlength="9" onblur="pesquisacep(this.value)">
                        </div>
                        <div class="grupo-input" style="flex: 2;">
                            <label>2. Bairro:</label>
                            <input type="text" name="novo_bairro_nome" id="novo_bairro" placeholder="Preenchimento automático..." required>
                        </div>
                        <div>
                            <button type="submit" class="btn-add">+ Adicionar</button>
                        </div>
                    </div>
                </form>

                <hr style="margin: 20px 0; border: 0; border-top: 1px solid #ddd;">

                <form method="POST">
                    <input type="hidden" name="salvar_rotas" value="1">
                    <input type="hidden" name="van_id_save" value="<?php echo $van_selecionada; ?>">
                    
                    <h4 style="margin-bottom: 10px;">
                        Lista de Bairros 
                        <small style="font-weight: normal; color:#777; font-size: 0.8rem;">(Marcar = Ativo / Desmarcar = Inativo)</small>
                    </h4>
                    
                    <div class="grid-checkbox">
                        <?php 
                        if ($res_bairros->num_rows > 0):
                            while($b = $res_bairros->fetch_assoc()): 
                                // Verifica se está vinculado à van ATUALMENTE
                                $checked = in_array($b['id'], $bairros_ativos_van) ? 'checked' : '';
                                
                                // Visual: Se estiver checked é "ativo", senão "inativo"
                                $classe_css = ($checked) ? 'ativo' : 'inativo';
                        ?>
                            <div class="item-bairro <?php echo $classe_css; ?>">
                                <input type="checkbox" name="bairros[]" value="<?php echo $b['id']; ?>" 
                                       id="b_<?php echo $b['id']; ?>" <?php echo $checked; ?>
                                       onchange="toggleCor(this)">
                                <label for="b_<?php echo $b['id']; ?>"><?php echo $b['nome']; ?></label>
                            </div>
                        <?php endwhile; else: ?>
                            <p style="grid-column: 1/-1; text-align: center; color: #777;">
                                Nenhum bairro cadastrado. Use o CEP acima.
                            </p>
                        <?php endif; ?>
                    </div>
                    <div style="margin-top: 20px; text-align: right;">
                        <button type="submit" style="width: auto; padding: 12px 30px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                            Salvar Alterações
                        </button>
                    </div>
                </form>

            </div>
        <?php else: ?>
            <div style="text-align: center; margin-top: 50px; color: #777;">
                <h3>Cadastre uma van primeiro.</h3>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>