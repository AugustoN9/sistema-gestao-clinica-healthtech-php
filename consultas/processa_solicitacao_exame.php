<?php
// 1. Inclusões e Proteção
session_start();
require_once '../auth.php';
proteger_pagina(['medico']); 
require_once '../conexao.php';

// IDs dos usuários logados
$medico_id_logado = $_SESSION['id_referencia'] ?? 0;

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ../area_medico.php");
    exit();
}

// 2. Coletar Dados do Formulário
$consulta_id = (int)($_POST['consulta_id'] ?? 0);
$paciente_id = (int)($_POST['paciente_id'] ?? 0);
$instrucoes_adicionais = trim($_POST['instrucoes_adicionais'] ?? '');
$exames_marcados = $_POST['exames'] ?? []; // Array: [exame_id => '1'] (valor do checkbox)

// 3. Validação Crítica
if ($consulta_id === 0 || $paciente_id === 0 || $medico_id_logado === 0) {
    $erro_msg = "Dados de referência (Consulta, Médico ou Paciente) ausentes ou inválidos.";
    header("Location: detalhes_consulta.php?id=$consulta_id&erro=" . urlencode($erro_msg));
    exit();
}

if (empty($exames_marcados)) {
    $erro_msg = "Nenhum exame foi selecionado para solicitação.";
    header("Location: detalhes_consulta.php?id=$consulta_id&erro=" . urlencode($erro_msg));
    exit();
}

// Inicia a Transação
mysqli_begin_transaction($conexao);

try {
    $pedidos_inseridos = 0;

    // 4. PREPARAÇÃO DA CONSULTA SQL
    $sql_insert = "INSERT INTO pedidos_exames 
                   (consulta_id, medico_id, paciente_id, exame_id, laboratorio_direcionado_id, observacoes_medicas_pedido) 
                   VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_insert = mysqli_prepare($conexao, $sql_insert);

    // 5. Itera sobre os exames marcados
    foreach ($exames_marcados as $exame_id_str => $valor) {
        $exame_id = (int)$exame_id_str;
        
        // 5a. Obtém o ID do Laboratório Selecionado para este Exame
        // O select box do laboratório é nomeado: lab_select_[exame_id]
        $nome_campo_lab = "lab_select_{$exame_id}";
        $laboratorio_id = (int)($_POST[$nome_campo_lab] ?? 0);

        if ($laboratorio_id === 0) {
            // Este erro não deve ocorrer se o JS do frontend funcionar (obrigatório se checado)
            throw new Exception("O laboratório de destino para o Exame ID $exame_id não foi selecionado.");
        }
        
        // 5b. Executa o INSERT
        // Tipos: i (consulta_id), i (medico_id), i (paciente_id), i (exame_id), i (lab_id), s (instrucoes)
        mysqli_stmt_bind_param($stmt_insert, "iiiiis", 
                               $consulta_id, 
                               $medico_id_logado, 
                               $paciente_id, 
                               $exame_id, 
                               $laboratorio_id, 
                               $instrucoes_adicionais);
        
        if (!mysqli_stmt_execute($stmt_insert)) {
            // Se houver falha, registra o erro SQL exato
            throw new Exception("Falha ao inserir pedido para o Exame ID $exame_id. Erro SQL: " . mysqli_error($conexao));
        }
        $pedidos_inseridos++;
    }

    // 6. Sucesso: Confirma a Transação
    mysqli_commit($conexao);
    
    // Redireciona com mensagem de sucesso
    header("Location: detalhes_consulta.php?id=$consulta_id&sucesso=" . urlencode("$pedidos_inseridos pedidos de exame foram salvos com sucesso!"));
    exit();

} catch (Exception $e) {
    // 7. Falha: Desfaz a Transação
    mysqli_rollback($conexao);
    
    $erro_msg = "Falha na Solicitação de Exames: " . $e->getMessage();
    header("Location: detalhes_consulta.php?id=$consulta_id&erro=" . urlencode($erro_msg));
    exit();
}
?>