<?php
// 1. Proteção e Conexão
require_once 'auth.php';
proteger_pagina(['enfermagem']);
require_once 'conexao.php';

// 2. Validar o POST
if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($_POST['vacinas_aplicadas']) || empty($_POST['agendamento_id']) || empty($_POST['paciente_id'])) {
    header("Location: area_enfermagem.php?erro=" . urlencode("Dados insuficientes para registar a vacina."));
    exit();
}

// 3. Obter os dados
$agendamento_id = (int)$_POST['agendamento_id'];
$paciente_id = (int)$_POST['paciente_id'];
$nome_paciente = $_POST['nome_paciente'];
$vacinas_a_registar = $_POST['vacinas_aplicadas'];
$data_aplicacao = date('Y-m-d'); 
$usuario_id_aplicou = (int)$_SESSION['usuario_id'];

// 4. Iniciar Transação
mysqli_begin_transaction($conexao);

try {
    // 5. Passo 1: Inserir na tabela 'vacinas'
    // ======================= QUERY ATUALIZADA =======================
    $sql_insert_vacina = "INSERT INTO vacinas (paciente_id, data_aplicacao, nome_vacina, marca, dose, lote, responsavel_imunizacao_enfermagem) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)"; // Nome da coluna alterado
    // ================================================================
    $stmt_insert = mysqli_prepare($conexao, $sql_insert_vacina);

    foreach ($vacinas_a_registar as $vacina) {
        $nome = $vacina['nome'];
        $dose = $vacina['dose'];
        $marca = $vacina['marca'];
        $lote = $vacina['lote'];
        
        if (empty($nome) || empty($dose) || empty($marca) || empty($lote)) {
            throw new Exception("Todos os campos (Dose, Marca, Lote) são obrigatórios.");
        }
        
        mysqli_stmt_bind_param($stmt_insert, "isssssi", $paciente_id, $data_aplicacao, $nome, $marca, $dose, $lote, $usuario_id_aplicou);
        mysqli_stmt_execute($stmt_insert);
    }

    // 6. Passo 2: Atualizar o status do agendamento
    $sql_update_ag = "UPDATE agendamento_vacinacao SET status = 'Concluido' WHERE id = ?";
    $stmt_update = mysqli_prepare($conexao, $sql_update_ag);
    mysqli_stmt_bind_param($stmt_update, "i", $agendamento_id);
    mysqli_stmt_execute($stmt_update);

    // 7. Sucesso!
    mysqli_commit($conexao);
    
    header("Location: area_enfermagem.php?sucesso=" . urlencode($nome_paciente));
    exit();

} catch (Exception $e) {
    // 8. Erro!
    mysqli_rollback($conexao);
    
    header("Location: area_enfermagem.php?erro=" . urlencode("Erro ao registar vacina: " . $e->getMessage()));
    exit();
}
?>