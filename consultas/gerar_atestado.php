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

// Busca todos os dados necessários (Médico, Paciente, Consulta e Diagnóstico)
$sql = "SELECT 
            c.data_consulta, c.horario_consulta, c.observacoes, c.status, c.paciente_id,
            p.nome AS nome_paciente, p.data_nascimento AS data_nasc_paciente,
            p.cpf AS cpf_paciente, /* CORRIGIDO: CPF Adicionado */
            m.nome_completo AS nome_medico, m.crm AS crm_medico
        FROM consultas AS c
        JOIN pacientes AS p ON c.paciente_id = p.id
        JOIN medicos AS m ON c.medico_id = m.id
        WHERE c.id = ? AND c.medico_id = ?"; // Filtro de segurança para garantir que o médico é o responsável
        
$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, "ii", $consulta_id, $medico_id_logado);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$dados = mysqli_fetch_assoc($resultado);

if (!$dados) {
    die("Acesso negado ou consulta não encontrada.");
}

// Lógica para obter a idade do paciente (necessária no atestado)
$data_nascimento = new DateTime($dados['data_nasc_paciente']);
$idade = $data_nascimento->diff(new DateTime('now'))->y;

// Obtém o Diagnóstico (Observações Médicas)
$diagnostico = $dados['observacoes'];
$data_emissao = date('d/m/Y');
$hora_emissao = date('H:i');
// ================== FIM: SETUP E BUSCA DE DADOS ==================
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Atestado Médico #<?= $consulta_id ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; font-size: 14pt; }
        .container { max-width: 800px; margin: 0 auto; border: 1px solid #ccc; padding: 40px; }
        .header { text-align: center; margin-bottom: 50px; }
        .header h1 { color: #004d99; margin-bottom: 5px; font-size: 20pt; }
        .content { margin-top: 40px; line-height: 1.8; }
        .signature { margin-top: 80px; text-align: center; }
        .signature hr { width: 300px; margin: 5px auto; border-color: #000; }
        .data-rodape { font-size: 10pt; text-align: right; margin-top: 30px; }
        @media print {
            body { margin: 0; padding: 0; }
            .container { border: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container">
        <div class="header">
            <h1>ATESTADO MÉDICO</h1>
            <p>Clínica HealthTech | CNPJ: XX.XXX.XXX/XXXX-XX</p>
            <p><?= htmlspecialchars($dados['nome_medico']) ?> (CRM: <?= htmlspecialchars($dados['crm_medico']) ?>)</p>
        </div>

        <div class="content">
            <p>Atesto, para os devidos fins, que o(a) paciente:</p>
            
            <p style="font-weight: bold; font-size: 16pt; margin-left: 50px;">
                <?= htmlspecialchars($dados['nome_paciente']) ?>
            </p>

            <p>portador(a) do CPF **<?= htmlspecialchars($dados['cpf_paciente']) ?>**, com **<?= $idade ?>** anos de idade, esteve sob meus cuidados e foi por mim atendido(a) no dia de hoje, **<?= $data_emissao ?>**, com início do atendimento às **<?= date('H:i', strtotime($dados['horario_consulta'])) ?>**.</p>
            
            <textarea style="width: 100%; height: 120px; border: 1px dashed #aaa; padding: 10px; font-size: 12pt; margin-top: 20px;" placeholder="Preencha aqui o motivo do atestado/afastamento e o período. Este campo é editável antes de imprimir/salvar."><?= htmlspecialchars($diagnostico) ?></textarea>
        </div>

        <div class="signature">
            <hr>
            <p>Assinatura e Carimbo do(a) Médico(a)</p>
        </div>

        <div class="data-rodape">
            <p>Porto Alegre, <?= $data_emissao ?> às <?= $hora_emissao ?>.</p>
        </div>
    </div>
</body>
</html>