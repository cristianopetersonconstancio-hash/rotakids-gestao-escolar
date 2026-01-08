<?php
session_start();
require 'config.php';

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$meu_id = $_SESSION['id'];

// Busca vans que estão para alugar, MAS EXCLUI as minhas próprias vans da lista
$sql = "SELECT v.modelo, v.capacidade, m.nome, m.telefone 
        FROM veiculos v 
        JOIN motoristas m ON v.motorista_id = m.id 
        WHERE v.alugar = 1 AND v.motorista_id != $meu_id 
        ORDER BY v.modelo";

$resultado = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Aluguel - RotaKids</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .card-aluguel {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-left: 5px solid #28a745;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .btn-whats {
            background-color: #25d366;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
        }
        .btn-whats:hover { background-color: #128c7e; }
        
        @media (max-width: 600px) {
            .card-aluguel { flex-direction: column; text-align: center; gap: 15px; }
        }
    </style>
</head>
<body>

    <?php include 'menu.php'; ?>

    <div class="container" style="padding: 20px; max-width: 800px; margin: 0 auto;">
        
        <h2 style="color: #333; margin-bottom: 20px; text-align: center;">🚐 Vans Disponíveis para Aluguel</h2>
        
        <?php if($resultado->num_rows > 0): ?>
            
            <?php while($item = $resultado->fetch_assoc()): 
                // Formata telefone para link do WhatsApp (remove espaços e traços)
                $zap = preg_replace("/[^0-9]/", "", $item['telefone']);
            ?>
                <div class="card-aluguel">
                    <div>
                        <h3 style="margin: 0; color: #333;"><?php echo $item['modelo']; ?></h3>
                        <span style="background: #eee; padding: 2px 8px; border-radius: 4px; font-size: 0.9rem;">
                            <?php echo $item['capacidade']; ?> Lugares
                        </span>
                        <p style="color: #666; margin-top: 5px; margin-bottom: 0;">
                            Proprietário: <strong><?php echo $item['nome']; ?></strong>
                        </p>
                    </div>
                    
                    <div>
                        <a href="https://wa.me/55<?php echo $zap; ?>?text=Olá, vi sua van <?php echo $item['modelo']; ?> disponível para aluguel no app." 
                           target="_blank" class="btn-whats">
                           📱 Contatar WhatsApp
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>

        <?php else: ?>
            <div style="text-align: center; margin-top: 50px; color: #777;">
                <p>Nenhuma van disponível para aluguel no momento.</p>
                <small>Se você tem uma van parada, coloque-a para alugar no menu "Frota".</small>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>