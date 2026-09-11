<?php
// 1. Inclusões e Proteção
require_once '../auth.php';
// Apenas o médico responsável pela consulta pode finalizar
proteger_pagina(['medico']); 
require_once '../conexao.php';

// Inicia a sessão para garantir que o ID do médico logado está disponível
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$medico_id_logado = $_SESSION['id_referencia'] ?? 0;

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ../area_medico.php");
    exit();
}

// 2. Coletar e Sanitizar os Dados
$consulta_id = $_POST['consulta_id'] ?? null;
$observacoes_medicas = trim($_POST['observacoes_medicas'] ?? '');
$status_final = 'Finalizada'; // Status que será aplicado

if (!$consulta_id || !is_numeric($consulta_id)) {
    header("Location: ../area_medico.php?erro=" . urlencode("ID da consulta inválido."));
    exit();
}

if (empty($observacoes_medicas)) {
    header("Location: detalhes_consulta.php?id=" . $consulta_id . "&erro=" . urlencode("O campo Diagnóstico e Observações é obrigatório."));
    exit();
}

// Inicia a Transação
mysqli_begin_transaction($conexao);

try {
    // 3. Verifica a Permissão Final (Garante que o médico é o responsável pela consulta)
    $sql_check = "SELECT medico_id, status FROM consultas WHERE id = ?";
    $stmt_check = mysqli_prepare($conexao, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "i", $consulta_id);
    mysqli_stmt_execute($stmt_check);
    $resultado_check = mysqli_stmt_get_result($stmt_check);
    $consulta_info = mysqli_fetch_assoc($resultado_check);

    if (!$consulta_info || $consulta_info['medico_id'] != $medico_id_logado) {
        throw new Exception("Você não tem permissão para finalizar esta consulta.");
    }
    
    if ($consulta_info['status'] == 'Finalizada') {
        throw new Exception("Esta consulta já está finalizada. Você pode apenas editar os dados.");
    }

    // 4. UPDATE na Tabela 'consultas'
    $sql_update = "UPDATE consultas 
                   SET observacoes = ?, status = ? 
                   WHERE id = ? AND medico_id = ?";
                   
    $stmt_update = mysqli_prepare($conexao, $sql_update);
    // Tipos: s (observacoes), s (status), i (id), i (medico_id)
    mysqli_stmt_bind_param($stmt_update, "ssii", 
                           $observacoes_medicas, 
                           $status_final, 
                           $consulta_id, 
                           $medico_id_logado);

    if (!mysqli_stmt_execute($stmt_update)) {
        throw new Exception("Falha ao atualizar o diagnóstico.");
    }

    // 5. Commit da Transação
    mysqli_commit($conexao);
    
    header("Location: detalhes_consulta.php?id=" . $consulta_id . "&sucesso=" . urlencode("Consulta finalizada e diagnóstico salvo com sucesso!"));
    exit();

} catch (Exception $e) {
    // 6. Rollback em caso de falha
    mysqli_rollback($conexao);
    
    $erro_msg = "Erro na Finalização: " . $e->getMessage();
    header("Location: detalhes_consulta.php?id=" . $consulta_id . "&erro=" . urlencode($erro_msg));
    exit();
}
?>