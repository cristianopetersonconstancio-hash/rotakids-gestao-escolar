<?php
session_start();
require 'config.php';

// Verifica se está logado
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$motorista_id = $_SESSION['id'];
$msg = "";
$edit_data = null; 
$vans_vinculadas_ids = []; // Array para guardar IDs das vans marcadas na edição

// --- 0. BUSCAR VANS DO MOTORISTA (Para preencher o formulário) ---
$sql_vans = "SELECT id, modelo, placa FROM veiculos WHERE motorista_id = $motorista_id";
$res_vans = $conn->query($sql_vans);
$todas_vans = [];
if ($res_vans->num_rows > 0) {
    while($v = $res_vans->fetch_assoc()) {
        $todas_vans[] = $v;
    }
}

// --- 1. EXCLUSÃO ---
if (isset($_GET['del_id'])) {
    $id_del = (int)$_GET['del_id'];
    
    // Pegamos o ID da escola real antes de deletar o vínculo
    $busca_escola = $conn->query("SELECT escola_id FROM motorista_escolas WHERE id=$id_del AND motorista_id=$motorista_id");
    if($busca_escola->num_rows > 0){
        $e_id = $busca_escola->fetch_assoc()['escola_id'];
        
        // 1. Remove da tabela de ligação principal
        $conn->query("DELETE FROM motorista_escolas WHERE id=$id_del AND motorista_id=$motorista_id");
        
        // 2. Remove também os vínculos de vans na tabela rota_escolas para não ficar lixo
        $conn->query("DELETE FROM rota_escolas WHERE escola_id=$e_id AND motorista_id=$motorista_id");

        // OPCIONAL: Se quiser deletar a escola da tabela 'escolas' também (já que agora é exclusiva do motorista)
        // $conn->query("DELETE FROM escolas WHERE id=$e_id AND motorista_id=$motorista_id");
    }
    
    $msg = "<div class='msg-sucesso'>Escola removida da lista!</div>";
}

// --- 2. PREPARAR EDIÇÃO ---
if (isset($_GET['edit_id'])) {
    $id_edit = (int)$_GET['edit_id'];
    // Busca os dados da escola vinculada
    $sql = "SELECT me.id, me.escola_id, e.nome, me.manha, me.tarde, me.noite 
            FROM motorista_escolas me 
            JOIN escolas e ON me.escola_id = e.id 
            WHERE me.id = $id_edit AND me.motorista_id = $motorista_id";
    $res = $conn->query($sql);
    if($res->num_rows > 0) {
        $edit_data = $res->fetch_assoc();
        
        // Busca quais vans já estão vinculadas a esta escola
        $e_id = $edit_data['escola_id'];
        $sql_vinc = "SELECT veiculo_id FROM rota_escolas WHERE escola_id = $e_id AND motorista_id = $motorista_id";
        $res_vinc = $conn->query($sql_vinc);
        while($rv = $res_vinc->fetch_assoc()){
            $vans_vinculadas_ids[] = $rv['veiculo_id'];
        }
    }
}

// --- 3. SALVAR (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome_escola = trim($_POST['nome_escola']);
    $id_item = $_POST['id_item'] ?? ''; 
    $vans_selecionadas = $_POST['vans'] ?? []; // Array com IDs das vans

    // Checkboxes de turno
    $manha = isset($_POST['manha']) ? 1 : 0;
    $tarde = isset($_POST['tarde']) ? 1 : 0;
    $noite = isset($_POST['noite']) ? 1 : 0;

    if (empty($nome_escola)) {
        $msg = "<div class='msg-erro'>Preencha o nome da escola.</div>";
    } else {
        
        // A. Verifica/Cria Escola (AGORA VINCULADA AO MOTORISTA)
        $stmt = $conn->prepare("SELECT id FROM escolas WHERE nome = ? AND motorista_id = ?");
        $stmt->bind_param("si", $nome_escola, $motorista_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $escola_id = 0;

        if ($res->num_rows > 0) {
            // Já existe na lista DESTE motorista
            $escola_id = $res->fetch_assoc()['id'];
            
            // Se estiver editando, podemos atualizar o nome da escola caso tenha mudado
            // (Mas aqui a busca é pelo nome, então se mudar o nome ele vai cair no else abaixo e criar nova. 
            // Para editar nome de escola existente, precisaríamos de outra lógica, mas para cadastro simples ok)
        } else {
            // Não existe para este motorista, cria nova
            $stmt = $conn->prepare("INSERT INTO escolas (nome, motorista_id) VALUES (?, ?)");
            $stmt->bind_param("si", $nome_escola, $motorista_id);
            if($stmt->execute()) {
                $escola_id = $conn->insert_id;
            } else {
                $msg = "<div class='msg-erro'>Erro ao criar escola: " . $conn->error . "</div>";
            }
        }

        if ($escola_id > 0) {
            // B. Vincula escola ao motorista (Tabela motorista_escolas)
            if (!empty($id_item)) { 
                // UPDATE do vínculo
                $stmt = $conn->prepare("UPDATE motorista_escolas SET escola_id=?, manha=?, tarde=?, noite=? WHERE id=? AND motorista_id=?");
                $stmt->bind_param("iiiiii", $escola_id, $manha, $tarde, $noite, $id_item, $motorista_id);
                $stmt->execute();
                $acao = "atualizada";
                $sucesso = true;
            } else { 
                // INSERT do vínculo
                $check = $conn->query("SELECT id FROM motorista_escolas WHERE motorista_id=$motorista_id AND escola_id=$escola_id");
                if ($check->num_rows == 0) {
                    $stmt = $conn->prepare("INSERT INTO motorista_escolas (motorista_id, escola_id, manha, tarde, noite) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("iiiii", $motorista_id, $escola_id, $manha, $tarde, $noite);
                    $sucesso = $stmt->execute();
                    $acao = "adicionada";
                } else {
                    $msg = "<div class='msg-erro'>Esta escola já está na sua lista.</div>";
                    $sucesso = false;
                }
            }

            // C. ATUALIZAR VÍNCULO COM AS VANS (Tabela rota_escolas)
            if ($sucesso) {
                // 1. Limpa vínculos atuais desta escola para este motorista
                $conn->query("DELETE FROM rota_escolas WHERE escola_id = $escola_id AND motorista_id = $motorista_id");
                
                // 2. Insere os novos selecionados
                if (!empty($vans_selecionadas)) {
                    foreach ($vans_selecionadas as $v_id) {
                        $v_id = (int)$v_id;
                        $conn->query("INSERT INTO rota_escolas (motorista_id, veiculo_id, escola_id) VALUES ($motorista_id, $v_id, $escola_id)");
                    }
                }
                
                $msg = "<div class='msg-sucesso'>Escola $acao com sucesso!</div>";
                $edit_data = null; 
                $vans_vinculadas_ids = [];
            }
        }
    }
}

// --- LISTAGEM DE ESCOLAS ---
// Busca apenas escolas ligadas a este motorista
$minhas_escolas = $conn->query("SELECT me.id, me.escola_id, e.nome, me.manha, me.tarde, me.noite 
                                FROM motorista_escolas me 
                                JOIN escolas e ON me.escola_id = e.id 
                                WHERE me.motorista_id = $motorista_id 
                                ORDER BY e.nome ASC");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Minhas Escolas - RotaKids</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .badge-turno { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; margin-right: 5px; }
        .turno-manha { background-color: #ffe082; color: #333; }
        .turno-tarde { background-color: #ffcc80; color: #333; }
        .turno-noite { background-color: #333; color: #fff; }
        
        .box-vans { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 10px; 
            background: #f1f3f5; 
            padding: 10px; 
            border-radius: 5px; 
            max-height: 150px; 
            overflow-y: auto; 
        }
        .item-van { font-size: 0.9rem; display: flex; align-items: center; gap: 8px; cursor: pointer; }
        .tag-van { background: #e9ecef; color: #495057; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; margin-right: 4px; border: 1px solid #ced4da; }
    </style>
    <script>
        function toggleFormulario() {
            var box = document.getElementById('box-formulario');
            box.style.display = (box.style.display === 'none') ? 'block' : 'none';
        }
        document.addEventListener("DOMContentLoaded", function() {
            var mensagem = document.querySelector('.msg-sucesso, .msg-erro');
            if (mensagem) { setTimeout(function() { mensagem.style.display = "none"; }, 3000); }
        });
    </script>
</head>
<body>

    <?php include 'menu.php'; ?>

    <div class="container" style="padding: 20px; max-width: 800px; margin: 0 auto;">
        
        <?php echo $msg; ?>

        <?php if(!$edit_data): ?>
            <button onclick="toggleFormulario()" style="margin-bottom: 20px; padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
                + Adicionar Nova Escola
            </button>
        <?php endif; ?>

        <div id="box-formulario" class="auth-card" 
             style="max-width: 100%; border-left: 5px solid #007bff; margin-bottom: 30px; 
             display: <?php echo ($edit_data) ? 'block' : 'none'; ?>;">
            
            <h3 style="border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px;">
                <?php echo ($edit_data) ? "Editar Escola" : "Cadastrar Escola"; ?>
            </h3>

            <form method="POST">
                <input type="hidden" name="id_item" value="<?php echo $edit_data['id'] ?? ''; ?>">

                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom: 5px; font-weight:bold;">Nome da Escola</label>
                    <input type="text" name="nome_escola" required value="<?php echo $edit_data['nome'] ?? ''; ?>"
                           placeholder="Ex: Colégio Objetivo"
                           style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom: 5px; font-weight:bold;">Vans que atendem esta escola:</label>
                    <div class="box-vans">
                        <?php if(count($todas_vans) > 0): ?>
                            <?php foreach($todas_vans as $van): 
                                $checked = in_array($van['id'], $vans_vinculadas_ids) ? 'checked' : '';
                            ?>
                                <label class="item-van">
                                    <input type="checkbox" name="vans[]" value="<?php echo $van['id']; ?>" <?php echo $checked; ?>> 
                                    <span><?php echo $van['modelo']; ?> <small style="color:#666">(<?php echo $van['placa']; ?>)</small></span>
                                </label>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="grid-column: span 2; color: red; font-size: 0.8rem;">
                                Nenhuma van cadastrada. Cadastre um veículo primeiro.
                            </div>
                        <?php endif; ?>
                    </div>
                    <small style="color: #666; font-size: 0.8rem;">Você pode marcar mais de uma van.</small>
                </div>

                <div style="margin-top: 15px;">
                    <label style="margin-bottom: 10px; display:block; font-weight:bold;">Turnos:</label>
                    <div style="display: flex; gap: 15px; background: #fff; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        <label style="cursor: pointer;"><input type="checkbox" name="manha" <?php echo ($edit_data && $edit_data['manha']) ? 'checked' : ''; ?>> Manhã</label>
                        <label style="cursor: pointer;"><input type="checkbox" name="tarde" <?php echo ($edit_data && $edit_data['tarde']) ? 'checked' : ''; ?>> Tarde</label>
                        <label style="cursor: pointer;"><input type="checkbox" name="noite" <?php echo ($edit_data && $edit_data['noite']) ? 'checked' : ''; ?>> Noite</label>
                    </div>
                </div>

                <br>

                <div style="display: flex; align-items: center; gap: 15px;">
                    <button type="submit" style="background-color: #007bff; color: white; border: none; padding: 12px 25px; border-radius: 5px; cursor: pointer; font-weight: bold;">
                        <?php echo ($edit_data) ? "Salvar Alterações" : "Salvar Escola"; ?>
                    </button>
                    <?php if($edit_data): ?>
                        <a href="minha-rota.php" style="color: #dc3545; text-decoration: none;">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="auth-card" style="max-width: 100%; border-left: none;">
            <h3 style="margin-bottom: 20px;">🏫 Escolas que atendo</h3>
            <ul style="list-style: none; padding: 0;">
                <?php if($minhas_escolas && $minhas_escolas->num_rows > 0): ?>
                    <?php while($e = $minhas_escolas->fetch_assoc()): 
                        $eid_list = $e['escola_id'];
                        // Monta a query para listar as vans vinculadas visualmente
                        $sql_lista_vans = "SELECT v.modelo 
                                           FROM rota_escolas re 
                                           JOIN veiculos v ON re.veiculo_id = v.id 
                                           WHERE re.escola_id = $eid_list AND re.motorista_id = $motorista_id";
                        
                        $res_vans_lista = $conn->query($sql_lista_vans);
                        $nomes_vans = [];

                        if ($res_vans_lista) {
                            while($vlist = $res_vans_lista->fetch_assoc()) { 
                                $nomes_vans[] = $vlist['modelo']; 
                            }
                        }
                    ?>
                        <li style="padding: 15px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <strong style="font-size: 1.1rem; color: #333;"><?php echo $e['nome']; ?></strong>
                                <div style="margin-top: 5px;">
                                    <?php if(!empty($nomes_vans)): ?>
                                        <?php foreach($nomes_vans as $nv): ?>
                                            <span class="tag-van">🚐 <?php echo $nv; ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <small style="color: red;">Sem van vinculada</small>
                                    <?php endif; ?>
                                </div>
                                <div style="margin-top: 5px;">
                                    <?php if($e['manha']) echo "<span class='badge-turno turno-manha'>Manhã</span>"; ?>
                                    <?php if($e['tarde']) echo "<span class='badge-turno turno-tarde'>Tarde</span>"; ?>
                                    <?php if($e['noite']) echo "<span class='badge-turno turno-noite'>Noite</span>"; ?>
                                </div>
                            </div>
                            <div style="margin-top: 5px;">
                                <a href="?edit_id=<?php echo $e['id']; ?>" style="color: #007bff; margin-right: 15px; text-decoration: none; font-weight: bold;">✏️</a>
                                <a href="?del_id=<?php echo $e['id']; ?>" onclick="return confirm('Tem certeza?');" style="color: #dc3545; text-decoration: none; font-weight: bold;">🗑️</a>
                            </div>
                        </li>
                    <?php endwhile; ?>
                <?php else: ?>
                    <li style="color: #777; text-align: center; padding: 30px;">Nenhuma escola cadastrada.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</body>
</html>