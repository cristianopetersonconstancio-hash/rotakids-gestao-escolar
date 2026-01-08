<?php
session_start();
require 'config.php';

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$motorista_id = $_SESSION['id'];
$msg = "";
$aluno_editar = null; 

// --- 0. MENSAGENS DE SUCESSO ---
if (isset($_GET['sucesso'])) {
    $msg = "<div class='msg-sucesso'>Operação realizada com sucesso!</div>";
}

// --- 1. EXCLUSÃO ---
if (isset($_GET['excluir'])) {
    $id_del = (int)$_GET['excluir'];
    $conn->query("DELETE FROM alunos WHERE id = $id_del AND motorista_id = $motorista_id");
    header("Location: cadastrar-aluno.php?sucesso=1");
    exit;
}

// --- 2. PREPARAR EDIÇÃO ---
if (isset($_GET['editar'])) {
    $id_edit = (int)$_GET['editar'];
    $stmt = $conn->prepare("SELECT * FROM alunos WHERE id = ? AND motorista_id = ?");
    $stmt->bind_param("ii", $id_edit, $motorista_id);
    $stmt->execute();
    $aluno_editar = $stmt->get_result()->fetch_assoc();
}

// --- 3. PROCESSAR FORMULÁRIO ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_atual = $_POST['id_aluno']; 
    
    $nome = trim($_POST['nome']);
    $veiculo_id = $_POST['veiculo_id']; 
    $nome_escola = $_POST['escola_selecionada'];
    $turno = $_POST['turno'];
    $responsavel = trim($_POST['responsavel']);
    $cpf = trim($_POST['cpf']);
    $cep = trim($_POST['cep']);
    $endereco = trim($_POST['endereco']);
    $numero = trim($_POST['numero']);
    $telefone = trim($_POST['telefone']);
    $valor_mensalidade = !empty($_POST['valor_mensalidade']) ? str_replace(',', '.', $_POST['valor_mensalidade']) : 0.00;
    $status_financeiro = $_POST['status_financeiro'];
    
    $bairro_nome = isset($_POST['bairro']) ? trim($_POST['bairro']) : '';

    $seg_ida = isset($_POST['seg_ida']) ? 1 : 0;
    $seg_volta = isset($_POST['seg_volta']) ? 1 : 0;
    $ter_ida = isset($_POST['ter_ida']) ? 1 : 0;
    $ter_volta = isset($_POST['ter_volta']) ? 1 : 0;
    $qua_ida = isset($_POST['qua_ida']) ? 1 : 0;
    $qua_volta = isset($_POST['qua_volta']) ? 1 : 0;
    $qui_ida = isset($_POST['qui_ida']) ? 1 : 0;
    $qui_volta = isset($_POST['qui_volta']) ? 1 : 0;
    $sex_ida = isset($_POST['sex_ida']) ? 1 : 0;
    $sex_volta = isset($_POST['sex_volta']) ? 1 : 0;

    $sucesso_operacao = false;

    if (!empty($id_atual)) {
        // UPDATE
        $sql = "UPDATE alunos SET veiculo_id=?, nome_aluno=?, escola=?, turno=?, nome_responsavel=?, cpf_responsavel=?, cep=?, endereco=?, numero_endereco=?, telefone_responsavel=?, valor_mensalidade=?, status_financeiro=?,
                seg_ida=?, seg_volta=?, ter_ida=?, ter_volta=?, qua_ida=?, qua_volta=?, qui_ida=?, qui_volta=?, sex_ida=?, sex_volta=?
                WHERE id=? AND motorista_id=?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssssssssdsiiiiiiiiiiii", 
            $veiculo_id, $nome, $nome_escola, $turno, $responsavel, $cpf, $cep, $endereco, $numero, $telefone, 
            $valor_mensalidade, $status_financeiro,
            $seg_ida, $seg_volta, $ter_ida, $ter_volta, $qua_ida, $qua_volta, $qui_ida, $qui_volta, $sex_ida, $sex_volta,
            $id_atual, $motorista_id
        );
        if ($stmt->execute()) { $sucesso_operacao = true; }
    } else {
        // INSERT
        $sql = "INSERT INTO alunos (motorista_id, veiculo_id, nome_aluno, escola, turno, nome_responsavel, cpf_responsavel, cep, endereco, numero_endereco, telefone_responsavel, valor_mensalidade, status_financeiro,
                seg_ida, seg_volta, ter_ida, ter_volta, qua_ida, qua_volta, qui_ida, qui_volta, sex_ida, sex_volta) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisssssssssdsiiiiiiiiii", $motorista_id, $veiculo_id, $nome, $nome_escola, $turno, $responsavel, $cpf, $cep, $endereco, $numero, $telefone, $valor_mensalidade, $status_financeiro,
                          $seg_ida, $seg_volta, $ter_ida, $ter_volta, $qua_ida, $qua_volta, $qui_ida, $qui_volta, $sex_ida, $sex_volta);

        if ($stmt->execute()) { $sucesso_operacao = true; }
    }

    if ($sucesso_operacao && !empty($bairro_nome) && !empty($veiculo_id)) {
        $stmt_b = $conn->prepare("SELECT id FROM bairros WHERE nome = ?");
        $stmt_b->bind_param("s", $bairro_nome);
        $stmt_b->execute();
        $res_b = $stmt_b->get_result();
        
        $id_bairro_db = 0;
        if ($res_b->num_rows > 0) {
            $id_bairro_db = $res_b->fetch_assoc()['id'];
        } else {
            $stmt_new = $conn->prepare("INSERT INTO bairros (nome) VALUES (?)");
            $stmt_new->bind_param("s", $bairro_nome);
            if ($stmt_new->execute()) { $id_bairro_db = $stmt_new->insert_id; }
        }

        if ($id_bairro_db > 0) {
            $check_rota = $conn->query("SELECT * FROM motorista_bairros WHERE veiculo_id = $veiculo_id AND bairro_id = $id_bairro_db AND motorista_id = $motorista_id");
            if ($check_rota->num_rows == 0) {
                $stmt_link = $conn->prepare("INSERT INTO motorista_bairros (motorista_id, veiculo_id, bairro_id) VALUES (?, ?, ?)");
                $stmt_link->bind_param("iii", $motorista_id, $veiculo_id, $id_bairro_db);
                $stmt_link->execute();
            }
        }
    }

    if ($sucesso_operacao) {
        header("Location: cadastrar-aluno.php?sucesso=1");
        exit;
    } else {
        $msg = "<div class='msg-erro'>Erro: " . $conn->error . "</div>";
    }
}

// --- DADOS AUXILIARES ---
$sql_escolas = "SELECT nome FROM escolas e JOIN motorista_escolas me ON e.id = me.escola_id WHERE me.motorista_id = $motorista_id GROUP BY e.nome ORDER BY e.nome ASC";
$lista_escolas_select = $conn->query($sql_escolas);

$all_vans = [];
$res_vans = $conn->query("SELECT id, modelo, placa FROM veiculos WHERE motorista_id = $motorista_id");
while($v = $res_vans->fetch_assoc()) { $all_vans[] = $v; }

$mapa_escola_van = [];
$check_table = $conn->query("SHOW TABLES LIKE 'rota_escolas'");
if ($check_table && $check_table->num_rows > 0) {
    $sql_relacao = "SELECT e.nome as nome_escola, v.id as van_id, v.modelo, v.placa,
                    me.manha, me.tarde, me.noite
                    FROM rota_escolas re 
                    JOIN escolas e ON re.escola_id = e.id 
                    JOIN veiculos v ON re.veiculo_id = v.id
                    JOIN motorista_escolas me ON (me.escola_id = e.id AND me.motorista_id = $motorista_id)
                    WHERE v.motorista_id = $motorista_id"; 
    $res_rel = $conn->query($sql_relacao);
    if ($res_rel) {
        while($row = $res_rel->fetch_assoc()) {
            $nome = $row['nome_escola'];
            if (!isset($mapa_escola_van[$nome])) {
                $mapa_escola_van[$nome] = [
                    'vans' => [],
                    'turnos' => ['manha' => $row['manha'], 'tarde' => $row['tarde'], 'noite' => $row['noite']]
                ];
            }
            $ja_tem = false;
            foreach($mapa_escola_van[$nome]['vans'] as $v) {
                if($v['id'] == $row['van_id']) $ja_tem = true;
            }
            if(!$ja_tem) {
                $mapa_escola_van[$nome]['vans'][] = ['id' => $row['van_id'], 'texto' => $row['modelo'] . " (" . $row['placa'] . ")"];
            }
        }
    }
}

$mapa_bairro_van = [];
$sql_bv = "SELECT mb.veiculo_id, b.nome AS nome_bairro FROM motorista_bairros mb JOIN bairros b ON mb.bairro_id = b.id WHERE mb.motorista_id = $motorista_id";
$res_bv = $conn->query($sql_bv);
while($row = $res_bv->fetch_assoc()) {
    $b_nome = mb_strtolower($row['nome_bairro'], 'UTF-8');
    if (!isset($mapa_bairro_van[$b_nome])) $mapa_bairro_van[$b_nome] = [];
    if (!in_array($row['veiculo_id'], $mapa_bairro_van[$b_nome])) $mapa_bairro_van[$b_nome][] = $row['veiculo_id'];
}

$json_mapa = json_encode($mapa_escola_van);
$json_mapa_bairros = json_encode($mapa_bairro_van); 
$json_todas_vans = json_encode($all_vans);

$sql_alunos = "SELECT a.id, a.nome_aluno, a.escola, a.turno, a.nome_responsavel, a.cpf_responsavel, a.cep, a.telefone_responsavel, a.valor_mensalidade, a.status_financeiro, a.endereco, a.numero_endereco, v.modelo as nome_van FROM alunos a JOIN veiculos v ON a.veiculo_id = v.id WHERE a.motorista_id = $motorista_id ORDER BY a.nome_aluno ASC";
$lista_alunos = $conn->query($sql_alunos);
$faturamento_total = 0;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Alunos - RotaKids</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .grade-dias { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .grade-dias th, .grade-dias td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        .grade-dias th { background: #eee; font-size: 0.9rem; }
        .chk-dia { transform: scale(1.5); cursor: pointer; }
        
        .tabela-alunos { width: 100%; border-collapse: collapse; margin-top: 10px; background: white; }
        .tabela-alunos th, .tabela-alunos td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        .tabela-alunos th { background: var(--van-preto, #333); color: var(--van-amarelo, #ffc107); }

        .status-em-dia { background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 0.8rem; }
        .status-atrasado { background: #f8d7da; color: #721c24; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 0.8rem; }
        
        .badge-turno { font-size: 0.75rem; padding: 3px 6px; border-radius: 4px; color: white; display: inline-block; margin-top: 3px; font-weight: bold;}
        .turno-manha { background-color: #007bff; }
        .turno-tarde { background-color: #fd7e14; }
        .turno-noite { background-color: #6f42c1; }
        .turno-integral { background-color: #28a745; }

        .btn-editar { background-color: #007bff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; margin-right: 5px; }

        .alerta-financeiro {
            display: none;
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
            padding: 10px;
            margin-top: 5px;
            border-radius: 4px;
            font-size: 0.9rem;
            animation: fadeIn 0.5s;
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
<script>
    const mapaEscolas = <?php echo $json_mapa; ?>;
    const mapaBairros = <?php echo $json_mapa_bairros; ?>; 
    const todasVans = <?php echo $json_todas_vans; ?>;
    const vanSelecionadaEdicao = "<?php echo $aluno_editar['veiculo_id'] ?? ''; ?>";

    function removerAcentos(str) {
        if (!str) return "";
        return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase().trim();
    }

    function atualizarListaVans() {
        const selectEscola = document.getElementById('escola_select');
        const selectVan = document.getElementById('van_select');
        const selectTurno = document.getElementById('turno_select');
        const inputBairro = document.getElementById('bairro');
        
        const escolaNome = selectEscola.value;
        const turnoSelecionado = selectTurno.value;
        const bairroDigitado = removerAcentos(inputBairro.value);

        selectVan.innerHTML = '<option value="" disabled selected>Selecione a van...</option>';
        
        // Se a escola ainda não foi selecionada, não faz nada (fluxo natural)
        if(escolaNome === "") return;

        let dadosEscola = mapaEscolas[escolaNome]; 
        
        if (!dadosEscola || dadosEscola.vans.length === 0) {
            adicionarAviso(selectVan, "⚠️ Nenhuma van nesta escola");
            return;
        }

        let vansDoTurno = [];
        let atendeTurno = false;

        if (turnoSelecionado === 'Manhã' && dadosEscola.turnos.manha == 1) atendeTurno = true;
        else if (turnoSelecionado === 'Tarde' && dadosEscola.turnos.tarde == 1) atendeTurno = true;
        else if (turnoSelecionado === 'Noite' && dadosEscola.turnos.noite == 1) atendeTurno = true;
        else if (turnoSelecionado === 'Integral') atendeTurno = true;

        if (atendeTurno) {
            vansDoTurno = [...dadosEscola.vans];
        } else {
            adicionarAviso(selectVan, "⚠️ Rota não faz turno da " + turnoSelecionado);
            return; 
        }

        let vansFinais = [];

        if (bairroDigitado.length > 0) {
            let bairroEncontrado = false;
            let idsVansDoBairro = new Set(); 

            for (let nomeBairroDb in mapaBairros) {
                let nomeDbNormalizado = removerAcentos(nomeBairroDb);
                if (nomeDbNormalizado.includes(bairroDigitado)) {
                    bairroEncontrado = true;
                    let vansDeste = mapaBairros[nomeBairroDb];
                    vansDeste.forEach(id => idsVansDoBairro.add(String(id)));
                }
            }

            if (bairroEncontrado) {
                vansFinais = vansDoTurno.filter(vanEscola => {
                    return idsVansDoBairro.has(String(vanEscola.id));
                });
            } else {
                vansFinais = []; 
            }
        } else {
            vansFinais = vansDoTurno;
        }

        if (vansFinais.length > 0) {
            vansFinais.forEach(van => {
                let option = document.createElement('option');
                option.value = van.id;
                option.text = van.texto;
                if (String(van.id) === String(vanSelecionadaEdicao)) { option.selected = true; }
                selectVan.add(option);
            });
            if (vansFinais.length === 1 && bairroDigitado.length > 0 && vanSelecionadaEdicao === "") {
                selectVan.selectedIndex = 1;
            }
        } else {
            if (bairroDigitado.length > 0) {
                adicionarAviso(selectVan, "⚠️ Sem Van para esta rota");
            } else {
                adicionarAviso(selectVan, "⚠️ Nenhuma van disponível");
            }
        }
    }

    function adicionarAviso(select, texto) {
        let option = document.createElement('option');
        option.text = texto;
        option.disabled = true;
        option.selected = true;
        select.add(option);
    }

    function verificarPendenciaCPF(campo) {
        let cpf = campo.value;
        let avisoBox = document.getElementById('aviso-cpf-bloqueado');
        let cpfLimpo = cpf.replace(/\D/g, '');

        if (cpfLimpo.length < 11) {
            avisoBox.style.display = 'none';
            campo.style.borderColor = '#ccc';
            return;
        }

        fetch('api_verificar_cpf.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({cpf: cpf})
        })
        .then(r => r.json())
        .then(d => {
            if (d.bloqueado) {
                avisoBox.style.display = 'block';
                avisoBox.innerHTML = '🚫 <strong>CUIDADO:</strong> O sistema identificou pendências financeiras ativas neste CPF com outro motorista escolar cadastrado na plataforma.';
                campo.style.borderColor = '#dc3545';
                campo.style.backgroundColor = '#fff8f8';
            } else {
                avisoBox.style.display = 'none';
                campo.style.borderColor = '#28a745';
                campo.style.backgroundColor = 'white';
            }
        })
        .catch(err => { console.error(err); });
    }

    function toggleFormulario() { document.getElementById('box-formulario').style.display = (document.getElementById('box-formulario').style.display === 'none') ? 'block' : 'none'; }
    function mascaraCPF(i) { var v = i.value; if(isNaN(v[v.length-1])){ i.value = v.substring(0, v.length-1); return; } i.setAttribute("maxlength", "14"); v = v.replace(/\D/g, ""); v = v.replace(/(\d{3})(\d)/, "$1.$2"); v = v.replace(/(\d{3})(\d)/, "$1.$2"); v = v.replace(/(\d{3})(\d{1,2})$/, "$1-$2"); i.value = v; }
    function mascaraCEP(t) { var v = t.value.replace(/\D/g,""); v = v.replace(/^(\d{5})(\d)/,"$1-$2"); t.value = v; }
    function mascaraTelefone(v) { v.value = v.value.replace(/\D/g, ""); v.value = v.value.replace(/^(\d{2})(\d)/g, "($1) $2"); v.value = v.value.replace(/(\d)(\d{4})$/, "$1-$2"); }
    function limpa_formulário_cep() { document.getElementById('endereco').value = ""; document.getElementById('numero').value = ""; document.getElementById('bairro').value = ""; atualizarListaVans(); }
    
    function meu_callback(conteudo) {
        if (!("erro" in conteudo)) {
            document.getElementById('endereco').value = conteudo.logradouro + " - " + conteudo.localidade + "/" + conteudo.uf;
            document.getElementById('bairro').value = conteudo.bairro;
            atualizarListaVans(); // Chama filtro ao preencher bairro via CEP
            document.getElementById('numero').focus();
        } else {
            limpa_formulário_cep();
            alert("CEP não encontrado.");
        }
    }
    
    function pesquisacep(valor) {
        var cep = valor.replace(/\D/g, '');
        if (cep != "") {
            if(/^[0-9]{8}$/.test(cep)) {
                document.getElementById('endereco').value = "...";
                document.getElementById('bairro').value = "...";
                var script = document.createElement('script');
                script.src = 'https://viacep.com.br/ws/'+ cep + '/json/?callback=meu_callback';
                document.body.appendChild(script);
            } else { limpa_formulário_cep(); alert("Formato de CEP inválido."); }
        } else { limpa_formulário_cep(); }
    };

    document.addEventListener("DOMContentLoaded", function() {
        if(window.location.search.includes('sucesso=')) { window.history.replaceState({}, document.title, window.location.pathname); }
        // Se estiver editando, pode ser necessário rodar a lista
        if (document.getElementById('escola_select').value !== "") { atualizarListaVans(); }
        var mensagem = document.querySelector('.msg-sucesso, .msg-erro');
        if (mensagem) { setTimeout(function() { mensagem.style.display = "none"; }, 5000); }
    });
</script>
</head>
<body>

    <?php include 'menu.php'; ?>

    <div class="container" style="padding: 20px; max-width: 900px; margin: 0 auto;">
        
        <?php echo $msg; ?>

        <?php if(!$aluno_editar): ?>
            <button onclick="toggleFormulario()" style="margin-bottom: 20px; width: auto; background-color: #007bff; color: white; border: none;">
                + Novo Aluno
            </button>
        <?php endif; ?>

        <div id="box-formulario" class="auth-card" 
             style="max-width: 100%; border-left: 5px solid var(--van-amarelo, #ffc107); margin-bottom: 30px; 
             display: <?php echo ($aluno_editar) ? 'block' : 'none'; ?>;">
            
            <h2><?php echo ($aluno_editar) ? "Editar Aluno" : "Novo Aluno"; ?></h2>
            
            <form method="POST">
                <input type="hidden" name="id_aluno" value="<?php echo $aluno_editar['id'] ?? ''; ?>">

                <div style="display:grid; grid-template-columns: 1fr 2fr 2fr 1fr; gap: 10px;">
                    <div>
                        <label>CEP</label>
                        <input type="text" name="cep" id="cep" placeholder="00000-000" maxlength="9"
                               onkeypress="mascaraCEP(this)" onblur="pesquisacep(this.value);" 
                               value="<?php echo $aluno_editar['cep'] ?? ''; ?>">
                    </div>
                    <div>
                        <label>Endereço</label>
                        <input type="text" name="endereco" id="endereco" placeholder="Auto..." required 
                               value="<?php echo $aluno_editar['endereco'] ?? ''; ?>">
                    </div>
                    <div>
                        <label>Bairro (Digitar Filtra a Van)</label>
                        <input type="text" name="bairro" id="bairro" placeholder="Bairro..." required
                               onkeyup="atualizarListaVans()"
                               value="<?php echo ($aluno_editar) ? '' : ''; ?>">
                    </div>
                    <div>
                        <label>Número</label>
                        <input type="text" name="numero" id="numero" placeholder="Nº" required 
                               value="<?php echo $aluno_editar['numero_endereco'] ?? ''; ?>">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 15px;">
                    <div>
                        <label>Nome do Aluno</label>
                        <input type="text" name="nome" required value="<?php echo $aluno_editar['nome_aluno'] ?? ''; ?>">
                    </div>
                    <div>
                        <label>Turno</label>
                        <select name="turno" id="turno_select" required onchange="atualizarListaVans()" 
                                style="width:100%; padding:12px; border: 2px solid #ddd; border-radius: 4px;">
                            
                            <option value="" disabled <?php echo (!$aluno_editar) ? 'selected' : ''; ?>>Selecione...</option>
                            
                            <?php 
                                // Se estiver editando, recupera o valor. Se for novo, começa vazio.
                                $t_atual = $aluno_editar['turno'] ?? ''; 
                                
                                $opcoes = ['Manhã', 'Tarde', 'Noite', 'Integral'];
                                foreach($opcoes as $op) {
                                    // Só marca selected se o valor do banco for igual à opção
                                    $sel = ($t_atual == $op) ? 'selected' : '';
                                    echo "<option value='$op' $sel>$op</option>";
                                }
                            ?>
                        </select>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
                    <div>
                        <label>Nome do Responsável</label>
                        <input type="text" name="responsavel" required value="<?php echo $aluno_editar['nome_responsavel'] ?? ''; ?>">
                    </div>
                    <div>
                        <label>CPF do Responsável</label>
                        <input type="text" name="cpf" oninput="mascaraCPF(this)" onblur="verificarPendenciaCPF(this)" 
                               placeholder="000.000.000-00" required value="<?php echo $aluno_editar['cpf_responsavel'] ?? ''; ?>">
                        <div id="aviso-cpf-bloqueado" class="alerta-financeiro"></div>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px; background: #f1f3f5; padding: 15px; border-radius: 5px;">
                    <div>
                        <label>1. Selecione a Escola</label>
                        <select name="escola_selecionada" id="escola_select" required onchange="atualizarListaVans()" 
                                style="width:100%; padding:12px; border: 2px solid #007bff; border-radius: 4px;">
                            <option value="" disabled <?php echo (!$aluno_editar) ? 'selected' : ''; ?>>Selecione...</option>
                            <?php 
                            if ($lista_escolas_select->num_rows > 0) {
                                $lista_escolas_select->data_seek(0);
                                while($esc = $lista_escolas_select->fetch_assoc()): 
                                    $sel_esc = ($aluno_editar && $aluno_editar['escola'] == $esc['nome']) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $esc['nome']; ?>" <?php echo $sel_esc; ?>><?php echo $esc['nome']; ?></option>
                                <?php endwhile; 
                            } ?>
                        </select>
                    </div>
                    <div>
                        <label>2. Van Disponível (Filtro Automático)</label>
                        <select name="veiculo_id" id="van_select" required 
                                style="width:100%; padding:12px; border: 2px solid var(--van-preto, #333); border-radius: 4px; background: white;">
                            <option value="" disabled selected>Preencha os dados acima...</option>
                        </select>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 2fr; gap: 20px; margin-top: 15px;">
                     <div>
                        <label>WhatsApp</label>
                        <input type="text" name="telefone" maxlength="15" onkeyup="mascaraTelefone(this)" placeholder="(00) 00000-0000" required value="<?php echo $aluno_editar['telefone_responsavel'] ?? ''; ?>">
                    </div>
                    <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; border: 1px solid #e9ecef; display:flex; gap:10px;">
                        <div style="flex:1">
                            <label>Valor Mensal</label>
                            <input type="number" step="0.01" name="valor_mensalidade" placeholder="0.00" value="<?php echo $aluno_editar['valor_mensalidade'] ?? ''; ?>">
                        </div>
                        <div style="flex:1">
                            <label>Pagamento</label>
                            <select name="status_financeiro" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 4px;">
                                <option value="em_dia" <?php echo ($aluno_editar && $aluno_editar['status_financeiro'] == 'em_dia') ? 'selected' : ''; ?>>🟢 Em dia</option>
                                <option value="inadimplente" <?php echo ($aluno_editar && $aluno_editar['status_financeiro'] == 'inadimplente') ? 'selected' : ''; ?>>🔴 Atrasado</option>
                            </select>
                        </div>
                    </div>
                </div>

                <hr style="margin: 20px 0;">

                <label>Dias de Transporte</label>
                <?php 
                function chk($aluno, $campo) {
                    if (!$aluno) return 'checked';
                    return ($aluno[$campo] == 1) ? 'checked' : '';
                }
                ?>
                <table class="grade-dias">
                    <thead>
                        <tr>
                            <th>Sentido</th><th>Seg</th><th>Ter</th><th>Qua</th><th>Qui</th><th>Sex</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>IDA</strong></td>
                            <td><input type="checkbox" name="seg_ida" class="chk-dia" <?php echo chk($aluno_editar, 'seg_ida'); ?>></td>
                            <td><input type="checkbox" name="ter_ida" class="chk-dia" <?php echo chk($aluno_editar, 'ter_ida'); ?>></td>
                            <td><input type="checkbox" name="qua_ida" class="chk-dia" <?php echo chk($aluno_editar, 'qua_ida'); ?>></td>
                            <td><input type="checkbox" name="qui_ida" class="chk-dia" <?php echo chk($aluno_editar, 'qui_ida'); ?>></td>
                            <td><input type="checkbox" name="sex_ida" class="chk-dia" <?php echo chk($aluno_editar, 'sex_ida'); ?>></td>
                        </tr>
                        <tr>
                            <td><strong>VOLTA</strong></td>
                            <td><input type="checkbox" name="seg_volta" class="chk-dia" <?php echo chk($aluno_editar, 'seg_volta'); ?>></td>
                            <td><input type="checkbox" name="ter_volta" class="chk-dia" <?php echo chk($aluno_editar, 'ter_volta'); ?>></td>
                            <td><input type="checkbox" name="qua_volta" class="chk-dia" <?php echo chk($aluno_editar, 'qua_volta'); ?>></td>
                            <td><input type="checkbox" name="qui_volta" class="chk-dia" <?php echo chk($aluno_editar, 'qui_volta'); ?>></td>
                            <td><input type="checkbox" name="sex_volta" class="chk-dia" <?php echo chk($aluno_editar, 'sex_volta'); ?>></td>
                        </tr>
                    </tbody>
                </table>

                <br>
                <button type="submit"><?php echo ($aluno_editar) ? "Salvar Alterações" : "Cadastrar Aluno"; ?></button>
                
                <?php if($aluno_editar): ?>
                    <a href="cadastrar-aluno.php" style="display:block; text-align:center; margin-top:10px; color: #666;">Cancelar Edição</a>
                <?php else: ?>
                    <span onclick="toggleFormulario()" style="display:block; text-align:center; margin-top:10px; color: #666; cursor:pointer;">Cancelar</span>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($lista_alunos->num_rows > 0): ?>
            <div style="overflow-x:auto;">
<table class="tabela-alunos">
            <thead>
                <tr>
                    <th>Aluno</th>
                    <th>Info</th>
                    <th>Financeiro</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while($aluno = $lista_alunos->fetch_assoc()): 
                    $faturamento_total += $aluno['valor_mensalidade'];
                    $classe_turno = "turno-manha";
                    if($aluno['turno'] == 'Tarde') $classe_turno = "turno-tarde";
                    if($aluno['turno'] == 'Noite') $classe_turno = "turno-noite";
                    if($aluno['turno'] == 'Integral') $classe_turno = "turno-integral";

                    $tel_formatado = $aluno['telefone_responsavel'];
                    $apenas_numeros = preg_replace("/[^0-9]/", "", $tel_formatado);
                    if(strlen($apenas_numeros) == 11) {
                        $tel_formatado = "(".substr($apenas_numeros,0,2).") ".substr($apenas_numeros,2,5)."-".substr($apenas_numeros,7);
                    }
                ?>
                    <tr>
                        <td>
                            <strong><?php echo $aluno['nome_aluno']; ?></strong><br>
                            <span class="badge-turno <?php echo $classe_turno; ?>"><?php echo $aluno['turno']; ?></span><br>
                            <small><?php echo $aluno['escola']; ?></small><br>
                            <small style="color: #555;">📍 <?php echo $aluno['endereco']; ?>, nº <?php echo $aluno['numero_endereco']; ?></small><br>
                            <small style="color: #888;">CEP: <?php echo $aluno['cep']; ?></small>
                        </td>

                        <td>
                            <?php echo $aluno['nome_van']; ?><br>
                            <?php echo $aluno['nome_responsavel']; ?><br>
                            <small style="color:#777;">CPF: <?php echo $aluno['cpf_responsavel'] ? $aluno['cpf_responsavel'] : '-'; ?></small><br>
                            <small>📱 <?php echo $tel_formatado; ?></small>
                        </td>

                        <td>
                            <?php if($aluno['valor_mensalidade'] > 0): ?>
                                R$ <?php echo number_format($aluno['valor_mensalidade'], 2, ',', '.'); ?><br>
                            <?php endif; ?>

                            <?php 
                            $st = trim($aluno['status_financeiro']);
                            if($st == 'em_dia' || $st == '1'): 
                            ?>
                                <span class="status-em-dia">Em dia</span>
                            <?php else: ?>
                                <span class="status-atrasado">Pendente</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <a href="?editar=<?php echo $aluno['id']; ?>" class="btn-editar">Editar</a>
                            <a href="?excluir=<?php echo $aluno['id']; ?>" 
                               onclick="return confirm('Tem certeza?');"
                               style="color: white; background: #dc3545; padding: 5px 10px; text-decoration: none; border-radius: 4px;">
                               &times;
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
            </div>
            
            <div style="margin-top: 20px; background: #fff; padding: 15px; border-radius: 5px; text-align: right; border: 1px solid #ddd;">
                <span style="font-size: 1.1rem; color: #666;">Faturamento Mensal Estimado:</span>
                <strong style="font-size: 1.4rem; color: #28a745; margin-left: 10px;">
                    R$ <?php echo number_format($faturamento_total, 2, ',', '.'); ?>
                </strong>
            </div>

        <?php else: ?>
            <p style="text-align: center; color: #777; margin-top: 20px;">Nenhum aluno cadastrado.</p>
        <?php endif; ?>

    </div>
</body>
</html>