<?php
require 'config.php';

// 1. RECEBE OS DADOS DO FILTRO
$escola_nome = isset($_GET['escola_nome']) ? $_GET['escola_nome'] : '';
$bairro_nome = isset($_GET['bairro_nome']) ? $_GET['bairro_nome'] : '';
$turno_desejado = isset($_GET['turno']) ? $_GET['turno'] : 'Manhã';
$vagas_necessarias = isset($_GET['vagas']) ? (int)$_GET['vagas'] : 1;

if(empty($escola_nome) || empty($bairro_nome)) {
    header("Location: index.php");
    exit;
}

// 2. PREPARAÇÃO DA QUERY
// Ajuste: Turnos ficam na tabela 'motorista_escolas' (alias 'me')

$filtro_rota_turno = "";
// CORREÇÃO: Usando 'me.' para filtrar se o motorista trabalha no turno
if($turno_desejado == 'Manhã') $filtro_rota_turno = "AND me.manha = 1";
elseif($turno_desejado == 'Tarde') $filtro_rota_turno = "AND me.tarde = 1";
elseif($turno_desejado == 'Noite') $filtro_rota_turno = "AND me.noite = 1";

// SQL Principal: Busca as vans que atendem Escola + Bairro + Turno
$sql = "SELECT v.*, m.nome as nome_motorista, m.telefone 
        FROM veiculos v
        JOIN motoristas m ON v.motorista_id = m.id
        
        -- Vínculo Van <-> Escola
        JOIN rota_escolas re ON v.id = re.veiculo_id
        JOIN escolas e ON re.escola_id = e.id
        
        -- CORREÇÃO: Join para pegar os turnos (Tabela motorista_escolas)
        JOIN motorista_escolas me ON (re.escola_id = me.escola_id AND re.motorista_id = me.motorista_id)
        
        -- Vínculo Van <-> Bairro
        JOIN motorista_bairros mb ON v.id = mb.veiculo_id
        JOIN bairros b ON mb.bairro_id = b.id
        
        WHERE e.nome = ? 
          AND b.nome = ?
          $filtro_rota_turno
        GROUP BY v.id"; 

$stmt = $conn->prepare($sql);

if(!$stmt) {
    die("<div style='padding:20px; background:#f8d7da; color:#721c24; text-align:center;'>
            <strong>Erro no Banco de Dados:</strong><br>" . $conn->error . 
            "<br><br>Verifique se as tabelas <em>motorista_escolas</em> e <em>motorista_bairros</em> existem.
         </div>");
}

$stmt->bind_param("ss", $escola_nome, $bairro_nome);
$stmt->execute();
$resultados = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Busca de Vans - RotaKids</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; margin:0; }
        
        .header-busca { 
            background: linear-gradient(135deg, #333 0%, #000 100%); 
            color: #ffc107; padding: 30px 20px; text-align: center; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header-busca h2 { margin: 0 0 10px 0; font-size: 1.8rem; }
        .header-busca p { margin: 0 0 20px 0; font-size: 1.1rem; opacity: 0.9; }
        .btn-nova-busca {
            background: rgba(255,255,255,0.1); border: 1px solid #ffc107; color: #ffc107;
            padding: 8px 20px; border-radius: 20px; text-decoration: none; font-size: 0.9rem;
            transition: 0.3s;
        }
        .btn-nova-busca:hover { background: #ffc107; color: #000; }

        .grid { 
            display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); 
            gap: 25px; max-width: 1100px; margin: 30px auto; padding: 0 20px; 
        }
        
        .card { 
            background: white; border-radius: 12px; overflow: hidden; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.08); transition: transform 0.2s; border: 1px solid #eee;
            display: flex; flex-direction: column;
        }
        .card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.12); }
        
        .card-top { 
            background: #ffe082; padding: 15px 20px; font-weight: bold; 
            display: flex; justify-content: space-between; align-items: center;
            color: #444; border-bottom: 1px solid #eecca0;
        }
        
        .card-body { padding: 20px; flex: 1; }
        .card-body p { margin: 8px 0; color: #555; }
        
        .badge { 
            background: #28a745; color: white; padding: 6px 12px; border-radius: 20px; 
            font-size: 0.85rem; font-weight: bold; display: inline-block; margin-bottom: 15px;
        }
        
        .btn-zap { 
            display: block; width: 100%; padding: 15px; background: #25d366; 
            color: white; text-align: center; text-decoration: none; font-weight: bold; 
            border:none; cursor:pointer; font-size: 1rem; transition: 0.3s;
        }
        .btn-zap:hover { background: #1ebc57; }
        
        /* Modal */
        .modal-overlay { 
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(0,0,0,0.7); justify-content: center; align-items: center; z-index: 999;
        }
        .modal-box { 
            background: white; padding: 30px; border-radius: 15px; text-align: center; 
            width: 90%; max-width: 400px; position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>

    <div class="header-busca">
        <h2>Vans para <?php echo htmlspecialchars($escola_nome); ?></h2>
        <p>Bairro: <strong><?php echo htmlspecialchars($bairro_nome); ?></strong> | Turno: <strong><?php echo $turno_desejado; ?></strong></p>
        <a href="index.php" class="btn-nova-busca">🔍 Nova Busca</a>
    </div>

    <div class="grid">
        <?php 
        $encontrou_alguma = false;

        if($resultados && $resultados->num_rows > 0):
            while($van = $resultados->fetch_assoc()):
                
                $id_van = $van['id'];
                
                // 1. PEGAR CAPACIDADE TOTAL DA VAN
                $capacidade_total = isset($van['capacidade']) && $van['capacidade'] > 0 ? $van['capacidade'] : 15;

                // 2. CALCULAR OCUPAÇÃO (TABELA ALUNOS)
                $filtro_sql = "";
                if($turno_desejado == 'Manhã') {
                    $filtro_sql = "(turno = 'Manhã' OR turno = 'Integral')";
                } elseif($turno_desejado == 'Tarde') {
                    $filtro_sql = "(turno = 'Tarde' OR turno = 'Integral')";
                } elseif($turno_desejado == 'Noite') {
                    $filtro_sql = "(turno = 'Noite' OR turno = 'Integral')";
                } else {
                    $filtro_sql = "1=1"; 
                }

                $sql_count = "SELECT count(*) as total_ocupados FROM alunos WHERE veiculo_id = $id_van AND $filtro_sql";
                $res_count = $conn->query($sql_count);
                $ocupados = 0;
                if($res_count) {
                    $ocupados = $res_count->fetch_assoc()['total_ocupados'];
                }

                // 3. CALCULO FINAL
                $vagas_livres = $capacidade_total - $ocupados;

                // 4. VERIFICA SE ATENDE A NECESSIDADE
                if($vagas_livres >= $vagas_necessarias):
                    $encontrou_alguma = true;
                    $zap = preg_replace("/[^0-9]/", "", $van['telefone']);
        ?>
            <div class="card">
                <div class="card-top">
                    <span>🚐 <?php echo $van['modelo']; ?></span>
                    <small style="font-weight: normal; font-size: 0.85rem;">Capac. <?php echo $capacidade_total; ?></small>
                </div>
                <div class="card-body">
                    <span class="badge">✅ <?php echo $vagas_livres; ?> vagas livres</span>
                    
                    <p><strong>Motorista:</strong> <?php echo $van['nome_motorista']; ?></p>
                    <p><strong>Ano:</strong> <?php echo $van['ano'] ?? '---'; ?></p>
                    
                    <hr style="border: 0; border-top: 1px solid #eee; margin: 10px 0;">
                    
                    <p style="color: #666; font-size: 0.9rem; line-height: 1.4;">
                        Atende o bairro <strong><?php echo htmlspecialchars($bairro_nome); ?></strong><br>
                        para o <strong><?php echo htmlspecialchars($escola_nome); ?></strong>.
                    </p>
                </div>
                <button class="btn-zap" onclick="abrirVerificacao('<?php echo $zap; ?>', '<?php echo $van['modelo']; ?>')">
                    💬 Tenho Interesse
                </button>
            </div>
        <?php 
                endif; 
            endwhile; 
        endif; 
        
        if(!$encontrou_alguma):
        ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px; color: #777;">
                <div style="font-size: 3rem; margin-bottom: 10px;">😕</div>
                <h3>Nenhuma van disponível para sua busca.</h3>
                <p>No momento, não encontramos vans com <strong><?php echo $vagas_necessarias; ?> vaga(s)</strong> livres nessa rota e turno.</p>
                <br>
                <a href="index.php" style="color: #007bff; text-decoration: none; font-weight: bold;">Tentar outra busca</a>
            </div>
        <?php endif; ?>
    </div>

    <div id="modalCPF" class="modal-overlay">
        <div class="modal-box">
            <h3 style="color: #333;">Segurança RotaKids 🛡️</h3>
            <p style="color: #666; font-size: 0.95rem;">Informe o CPF do responsável para liberar o contato do motorista.</p>
            
            <input type="text" id="cpfInput" placeholder="000.000.000-00" oninput="mascaraCPF(this)"
                   style="padding:12px; width:80%; margin:15px 0; font-size:1.1rem; text-align:center; border: 2px solid #ddd; border-radius: 8px; outline: none;">
            
            <p id="msgErro" style="color: #dc3545; display: none; font-weight: bold; background: #ffe6e6; padding: 10px; border-radius: 5px; text-align: left; font-size: 0.9rem;"></p>
            
            <div style="display: flex; gap: 10px; justify-content: center; margin-top: 10px;">
                <button onclick="fecharModal()" style="padding:10px 20px; background:#eee; color:#555; border:none; border-radius: 5px; cursor:pointer;">Cancelar</button>
                <button onclick="verificarBloqueio()" style="padding:10px 20px; background:#333; color:#ffc107; border:none; border-radius: 5px; cursor:pointer; font-weight:bold;">Verificar</button>
            </div>
        </div>
    </div>

    <script>
        let zapDestino = ""; 
        let modeloDestino = "";
        
        function mascaraCPF(i) {
            var v = i.value;
            if(isNaN(v[v.length-1])){ i.value = v.substring(0, v.length-1); return; }
            i.setAttribute("maxlength", "14");
            v = v.replace(/\D/g, "");
            v = v.replace(/(\d{3})(\d)/, "$1.$2");
            v = v.replace(/(\d{3})(\d)/, "$1.$2");
            v = v.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
            i.value = v;
        }

        function abrirVerificacao(zap, modelo) { 
            zapDestino = zap; 
            modeloDestino = modelo; 
            
            // Reseta o modal
            document.getElementById('msgErro').style.display = 'none';
            document.getElementById('cpfInput').value = '';
            document.getElementById('modalCPF').style.display = 'flex'; 
            document.getElementById('cpfInput').focus();
        }
        
        function fecharModal() { 
            document.getElementById('modalCPF').style.display = 'none'; 
        }
        
        function verificarBloqueio() {
            let cpf = document.getElementById('cpfInput').value;
            
            if(cpf.length < 11) {
                alert("Por favor, digite um CPF válido.");
                return;
            }

            fetch('api_verificar_cpf.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({cpf: cpf})
            })
            .then(r => r.json())
            .then(d => {
                if(d.bloqueado) {
                    // --- CENÁRIO A: INADIMPLENTE ---
                    let msgBox = document.getElementById('msgErro');
                    
                    // Mostra o nome e telefone do credor
                    msgBox.innerHTML = `
                        🚫 <strong>Atenção:</strong> Consta uma pendência financeira neste CPF.<br><br>
                        Entre em contato com o motorista <strong>${d.motorista_nome}</strong> 
                        pelo telefone <strong>${d.motorista_tel}</strong> para regularizar.
                    `;
                    msgBox.style.display = 'block';
                    
                } else {
                    // --- CENÁRIO B: TUDO OK ---
                    let texto = encodeURIComponent(`Olá, vi sua van ${modeloDestino} no RotaKids. Tenho interesse no transporte para o *<?php echo htmlspecialchars($escola_nome); ?>* (<?php echo $turno_desejado; ?>).`);
                    window.open(`https://wa.me/55${zapDestino}?text=${texto}`, '_blank');
                    fecharModal();
                }
            })
            .catch(err => {
                console.error("Erro na verificação:", err);
                alert("Erro ao conectar com servidor.");
            });
        }
    </script>
</body>
</html>