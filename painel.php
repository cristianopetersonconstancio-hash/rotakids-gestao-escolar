<?php
session_start();
require 'config.php';

// Verificação de segurança
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$motorista_id = $_SESSION['id'];

// 1. Busca TODAS as vans do motorista
$sql_vans = $conn->query("SELECT * FROM veiculos WHERE motorista_id = $motorista_id");

// --- FUNÇÃO AUXILIAR (Agora fora do loop para não dar erro) ---
function getDados($qtd, $total) {
    $porc = ($total > 0) ? ($qtd / $total) * 100 : 0;
    
    if ($porc >= 100) {
        $cor = 'bg-vermelho';
    } elseif ($porc > 80) {
        $cor = 'bg-amarelo';
    } else {
        $cor = 'bg-verde';
    }
    
    return ['p' => $porc, 'c' => $cor];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel - RotaKids</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    
    <style>
        .painel-lotacao {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            border-left: 6px solid var(--van-preto);
            margin-bottom: 30px;
        }
        
        /* Layout em Grid para os Turnos */
        .grid-turnos {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr; /* 3 Colunas */
            gap: 20px;
            margin-top: 15px;
        }
        
        @media (max-width: 768px) {
            .grid-turnos { grid-template-columns: 1fr; } 
        }

        .box-turno {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #eee;
        }

        .titulo-turno {
            font-size: 0.9rem;
            font-weight: bold;
            margin-bottom: 8px;
            display: block;
            text-align: center;
            text-transform: uppercase;
        }
        .txt-azul { color: #007bff; }
        .txt-laranja { color: #fd7e14; }
        .txt-roxo { color: #6f42c1; }

        .barra-container {
            background-color: #e9ecef;
            border-radius: 4px;
            height: 18px; 
            width: 100%;
            margin-bottom: 8px;
            overflow: hidden;
            border: 1px solid #ccc;
        }
        .barra-progresso {
            height: 100%;
            text-align: center;
            font-size: 11px;
            color: white;
            line-height: 18px;
            font-weight: bold;
            transition: width 0.8s ease-in-out;
        }
        
        .label-pequeno {
            font-size: 0.75rem;
            color: #555;
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }

        .bg-verde { background-color: #28a745; }
        .bg-amarelo { background-color: #ffc107; color: #333; }
        .bg-vermelho { background-color: #dc3545; }
    </style>
</head>
<body>

    <?php include 'menu.php'; ?>

    <div style="padding: 20px; max-width: 1000px; margin: 0 auto;">
        
        <?php if ($sql_vans->num_rows > 0): ?>
            
            <h2 style="margin-bottom: 20px; color: #555;">Ocupação da Frota</h2>

            <?php while($van = $sql_vans->fetch_assoc()): 
                $vid = $van['id'];
                $cap = $van['capacidade'];

                // --- CÁLCULOS POR TURNO ---

                // 1. MANHÃ
                // Ida: Quem é Manhã ou Integral
                $m_ida = $conn->query("SELECT COUNT(*) as c FROM alunos WHERE veiculo_id=$vid AND seg_ida=1 AND (turno='Manhã' OR turno='Integral')")->fetch_assoc()['c'];
                // Volta: Quem é Manhã (Integral volta de tarde)
                $m_volta = $conn->query("SELECT COUNT(*) as c FROM alunos WHERE veiculo_id=$vid AND seg_volta=1 AND turno='Manhã'")->fetch_assoc()['c'];

                // 2. TARDE
                // Ida: Quem é Tarde (Integral foi de manhã)
                $t_ida = $conn->query("SELECT COUNT(*) as c FROM alunos WHERE veiculo_id=$vid AND seg_ida=1 AND turno='Tarde'")->fetch_assoc()['c'];
                // Volta: Quem é Tarde ou Integral
                $t_volta = $conn->query("SELECT COUNT(*) as c FROM alunos WHERE veiculo_id=$vid AND seg_volta=1 AND (turno='Tarde' OR turno='Integral')")->fetch_assoc()['c'];

                // 3. NOITE
                $n_ida = $conn->query("SELECT COUNT(*) as c FROM alunos WHERE veiculo_id=$vid AND seg_ida=1 AND turno='Noite'")->fetch_assoc()['c'];
                $n_volta = $conn->query("SELECT COUNT(*) as c FROM alunos WHERE veiculo_id=$vid AND seg_volta=1 AND turno='Noite'")->fetch_assoc()['c'];

                // Usando a função que agora está lá fora do loop
                $d_mi = getDados($m_ida, $cap); $d_mv = getDados($m_volta, $cap);
                $d_ti = getDados($t_ida, $cap); $d_tv = getDados($t_volta, $cap);
                $d_ni = getDados($n_ida, $cap); $d_nv = getDados($n_volta, $cap);
            ?>
            
                <div class="painel-lotacao">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 10px;">
                        <h3 style="margin: 0;">🚐 <?php echo $van['modelo']; ?> <small style="color:#777;">(<?php echo $van['placa']; ?>)</small></h3>
                        <span style="background:#eee; padding:2px 8px; border-radius:4px; font-size:0.8rem;">Capacidade: <?php echo $cap; ?></span>
                    </div>

                    <div class="grid-turnos">
                        
                        <div class="box-turno">
                            <span class="titulo-turno txt-azul">🌞 Manhã</span>
                            
                            <div class="label-pequeno"><span>Ida</span> <span><?php echo $m_ida; ?>/<?php echo $cap; ?></span></div>
                            <div class="barra-container">
                                <div class="barra-progresso <?php echo $d_mi['c']; ?>" style="width: <?php echo $d_mi['p']; ?>%;"></div>
                            </div>

                            <div class="label-pequeno"><span>Volta</span> <span><?php echo $m_volta; ?>/<?php echo $cap; ?></span></div>
                            <div class="barra-container">
                                <div class="barra-progresso <?php echo $d_mv['c']; ?>" style="width: <?php echo $d_mv['p']; ?>%;"></div>
                            </div>
                        </div>

                        <div class="box-turno">
                            <span class="titulo-turno txt-laranja">🌤️ Tarde</span>
                            
                            <div class="label-pequeno"><span>Ida</span> <span><?php echo $t_ida; ?>/<?php echo $cap; ?></span></div>
                            <div class="barra-container">
                                <div class="barra-progresso <?php echo $d_ti['c']; ?>" style="width: <?php echo $d_ti['p']; ?>%;"></div>
                            </div>

                            <div class="label-pequeno"><span>Volta</span> <span><?php echo $t_volta; ?>/<?php echo $cap; ?></span></div>
                            <div class="barra-container">
                                <div class="barra-progresso <?php echo $d_tv['c']; ?>" style="width: <?php echo $d_tv['p']; ?>%;"></div>
                            </div>
                        </div>

                        <div class="box-turno">
                            <span class="titulo-turno txt-roxo">🌙 Noite</span>
                            
                            <div class="label-pequeno"><span>Ida</span> <span><?php echo $n_ida; ?>/<?php echo $cap; ?></span></div>
                            <div class="barra-container">
                                <div class="barra-progresso <?php echo $d_ni['c']; ?>" style="width: <?php echo $d_ni['p']; ?>%;"></div>
                            </div>

                            <div class="label-pequeno"><span>Volta</span> <span><?php echo $n_volta; ?>/<?php echo $cap; ?></span></div>
                            <div class="barra-container">
                                <div class="barra-progresso <?php echo $d_nv['c']; ?>" style="width: <?php echo $d_nv['p']; ?>%;"></div>
                            </div>
                        </div>

                    </div>
                </div>

            <?php endwhile; ?>
            
        <?php else: ?>
            <div style="text-align: center; margin-top: 50px; color: #777;">
                <h2>Bem-vindo ao Sistema!</h2>
                <a href="meu-veiculo.php" class="btn-novo">Cadastrar Veículo</a>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>