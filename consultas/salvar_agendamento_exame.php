<?php
// Arquivo: salvar_agendamento_exame.php
// Processa o agendamento de um exame solicitado pelo paciente.

// 1. Proteção e Conexão
require_once '../auth.php';
proteger_pagina(['paciente']); // Apenas pacientes podem aceder
require_once '../conexao.php';

// Inicia a sessão para garantir o ID do paciente
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Validar o POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: ../area_paciente.php");
    exit();
}

// 3. Obter e Validar os Dados
$pedido_id = (int)($_POST['pedido_id'] ?? 0);
$data_agendamento = trim($_POST['data_agendamento'] ?? '');
$turno = trim($_POST['turno'] ?? '');
$paciente_id_logado = (int)($_SESSION['id_referencia'] ?? 0);

if ($pedido_id === 0 || empty($data_agendamento) || empty($turno)) {
    $erro_msg = "Dados incompletos. Por favor, selecione o exame, a data e o turno.";
    header("Location: ../area_paciente.php?erro=" . urlencode($erro_msg));
    exit();
}

// 4. Iniciar Transação
mysqli_begin_transaction($conexao);

try {
    // 5. Passo 1: Verificar se o pedido pertence ao paciente e se o status é 'Solicitado'
    $sql_check = "SELECT paciente_id, status FROM pedidos_exames WHERE id = ?";
    $stmt_check = mysqli_prepare($conexao, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "i", $pedido_id);
    mysqli_stmt_execute($stmt_check);
    $resultado_check = mysqli_stmt_get_result($stmt_check);
    $pedido_info = mysqli_fetch_assoc($resultado_check);

    if (!$pedido_info || $pedido_info['paciente_id'] !== $paciente_id_logado) {
        throw new Exception("Acesso negado ou pedido inválido.");
    }
    
    if ($pedido_info['status'] !== 'Solicitado') {
        throw new Exception("Este exame já foi agendado ou concluído.");
    }

    // 6. Passo 2: Atualizar o status do pedido para 'Agendado' e registrar data/turno
    $sql_update = "UPDATE pedidos_exames 
                   SET status = 'Agendado', 
                       data_agendamento_paciente = ?, 
                       turno_agendamento_paciente = ?
                   WHERE id = ?";
                   
    $stmt_update = mysqli_prepare($conexao, $sql_update);
    // Tipos: s (data), s (turno), i (id do pedido)
    mysqli_stmt_bind_param($stmt_update, "ssi", 
                           $data_agendamento, 
                           $turno, 
                           $pedido_id);

    if (!mysqli_stmt_execute($stmt_update)) {
        throw new Exception("Falha ao registrar o agendamento do exame.");
    }

    // 7. Commit da Transação
    mysqli_commit($conexao);
    
    $sucesso_msg = "Exame agendado com sucesso! Aguarde a confirmação do laboratório.";
    header("Location: ../area_paciente.php?sucesso=" . urlencode($sucesso_msg));
    exit();

} catch (Exception $e) {
    // 8. Rollback em caso de falha
    mysqli_rollback($conexao);
    
    $erro_msg = "Erro ao agendar exame: " . $e->getMessage();
    header("Location: ../area_paciente.php?erro=" . urlencode($erro_msg));
    exit();
}
?>