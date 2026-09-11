<?php
// salvar_agendamento_vacina.php

// 1. Proteção e Conexão
require_once 'auth.php';
proteger_pagina(['paciente']); // Apenas pacientes podem aceder
require_once 'conexao.php';

// 2. Validar o POST
// Verifica se todos os campos essenciais vieram do modal
if ($_SERVER['REQUEST_METHOD'] != 'POST' || 
    empty($_POST['vacinas_para_salvar']) || 
    empty($_POST['data_agendamento']) || 
    empty($_POST['turno'])) {
    
    $erro_msg = "Dados incompletos. Por favor, selecione as vacinas, data e turno.";
    header("Location: area_paciente.php?erro=" . urlencode($erro_msg));
    exit();
}

// 3. Obter os dados
$paciente_id = (int)$_SESSION['id_referencia']; // ID do paciente logado
$vacinas_a_salvar = $_POST['vacinas_para_salvar']; // Lista de 'Nome da Vacina|Dose'
$data_agendamento = mysqli_real_escape_string($conexao, $_POST['data_agendamento']);
$turno = mysqli_real_escape_string($conexao, $_POST['turno']); // 'manha' ou 'tarde'

// 4. Iniciar Transação
mysqli_begin_transaction($conexao);

try {
    // 5. Passo 1: Inserir o agendamento principal na tabela 'agendamento_vacinacao'
    $sql_agendamento = "INSERT INTO agendamento_vacinacao (paciente_id, data_agendamento, turno, status) 
                        VALUES (?, ?, ?, 'Aguardando')";
    $stmt_agendamento = mysqli_prepare($conexao, $sql_agendamento);
    mysqli_stmt_bind_param($stmt_agendamento, "iss", $paciente_id, $data_agendamento, $turno);
    mysqli_stmt_execute($stmt_agendamento);
    
    $agendamento_id = mysqli_insert_id($conexao);
    if ($agendamento_id == 0) {
        throw new Exception("Não foi possível criar o registo de agendamento principal.");
    }

    // 6. Passo 2: Inserir os itens (vacinas) na tabela 'agendamento_vacinas_itens'
    // NOTE: Assumindo que você tem esta tabela para guardar os detalhes do agendamento
    $sql_itens = "INSERT INTO agendamento_vacinas_itens (agendamento_id, nome_vacina, dose_recomendada) VALUES (?, ?, ?)";
    $stmt_itens = mysqli_prepare($conexao, $sql_itens);
    
    foreach ($vacinas_a_salvar as $vacina_valor_combinado) {
        $partes = explode('|', $vacina_valor_combinado, 2);
        $nome_vacina = mysqli_real_escape_string($conexao, $partes[0]);
        $dose_recomendada = mysqli_real_escape_string($conexao, $partes[1] ?? 'N/D');

        mysqli_stmt_bind_param($stmt_itens, "iss", $agendamento_id, $nome_vacina, $dose_recomendada);
        mysqli_stmt_execute($stmt_itens);
    }
    
    // 7. Sucesso!
    mysqli_commit($conexao);
    
    header("Location: area_paciente.php?sucesso=agendado");
    exit();

} catch (Exception $e) {
    // 8. Erro!
    mysqli_rollback($conexao);
    $erro_msg = "Falha ao processar o agendamento: " . $e->getMessage();
    
    header("Location: area_paciente.php?erro=" . urlencode($erro_msg));
    exit();
}
?>