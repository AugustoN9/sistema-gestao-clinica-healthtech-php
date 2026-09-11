<?php
// ================== INÍCIO: SETUP E BUSCA DE DADOS ==================
require_once '../auth.php';
proteger_pagina(['medico']); 
require_once '../conexao.php';

$consulta_id = $_GET['id'] ?? null;
if (!$consulta_id || !is_numeric($consulta_id)) {
    die("ID da consulta inválido.");
}

$medico_id_logado = $_SESSION['id_referencia'] ?? 0;

// Busca dados da consulta, paciente, e médico
$sql = "SELECT 
            c.data_consulta, c.horario_consulta,
            p.nome AS nome_paciente, p.data_nascimento AS data_nasc_paciente,
            m.nome_completo AS nome_medico, m.crm AS crm_medico
        FROM consultas AS c
        JOIN pacientes AS p ON c.paciente_id = p.id
        JOIN medicos AS m ON c.medico_id = m.id
        WHERE c.id = ? AND c.medico_id = ?"; // Filtro de segurança
        
$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, "ii", $consulta_id, $medico_id_logado);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$dados = mysqli_fetch_assoc($resultado);

if (!$dados) {
    die("Acesso negado ou consulta não encontrada.");
}

$data_emissao = date('d/m/Y');
// ================== FIM: SETUP E BUSCA DE DADOS ==================
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Receituário #<?= $consulta_id ?></title>
    <style>
        body { font-family: 'Times New Roman', serif; margin: 0; padding: 20px; font-size: 12pt; }
        .container { max-width: 800px; margin: 0 auto; border: 1px solid #ccc; padding: 40px; }
        .doc-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 30px; }
        .doc-header h1 { font-size: 18pt; margin: 0; }
        .doc-body { min-height: 400px; margin-top: 20px; line-height: 1.5; }
        .doc-info { margin-bottom: 20px; border-bottom: 1px dashed #ccc; padding-bottom: 10px; }
        .doc-info p { margin: 5px 0; }
        .signature { margin-top: 80px; text-align: center; }
        .signature hr { width: 300px; margin: 5px auto; border-color: #000; }
        @media print {
            body { margin: 0; padding: 0; }
            .container { border: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container">
        <div class="doc-header">
            <h1>RECEITUÁRIO MÉDICO</h1>
            <p>Clínica HealthTech | <?= htmlspecialchars($dados['nome_medico']) ?> (CRM: <?= htmlspecialchars($dados['crm_medico']) ?>)</p>
        </div>

        <div class="doc-info">
            <p><strong>Paciente:</strong> <?= htmlspecialchars($dados['nome_paciente']) ?></p>
            <p><strong>Data:</strong> <?= $data_emissao ?></p>
        </div>

        <div class="doc-body">
            <textarea style="width: 100%; height: 350px; border: 1px solid #ddd; padding: 15px; font-size: 14pt; line-height: 1.5; font-family: monospace;" placeholder="Digite aqui a Prescrição (Medicamento, Dose, Via, Frequência e Duração)..."></textarea>
        </div>

        <div class="signature">
            <hr>
            <p><?= htmlspecialchars($dados['nome_medico']) ?> | CRM: <?= htmlspecialchars($dados['crm_medico']) ?></p>
        </div>
    </div>
</body>
</html>