<?php
// 1. Proteção e Conexão
require_once '../auth.php';
proteger_pagina(['medico']);
require_once '../conexao.php';

$medico_id_logado = $_SESSION['id_referencia'];

// 2. Obter os dados enviados pelo JavaScript (em formato JSON)
$dados = json_decode(file_get_contents('php://input'), true);

if (!$dados || !isset($dados['data']) || !isset($dados['slots'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'erro', 'mensagem' => 'Dados inválidos.']);
    exit();
}

$data_selecionada = $dados['data'];
$slots_selecionados = $dados['slots']; // Array de [{horario: '...', consultorio: '...'}, ...]

// 3. Validação do Limite (Regra de Negócio: 9 slots)
if (count($slots_selecionados) > 9) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'erro', 'mensagem' => 'Limite de 9 horários por dia excedido.']);
    exit();
}

// 4. Salvar no Banco (Usando Transação)
mysqli_begin_transaction($conexao);

try {
    // 5. PRIMEIRO: Limpa TODOS os slots antigos DESTE médico para ESTE dia.
    // Isso sincroniza a agenda, removendo horários que ele desmarcou.
    $sql_delete = "DELETE FROM disponibilidade_medicos WHERE medico_id = ? AND data_disponivel = ?";
    $stmt_delete = mysqli_prepare($conexao, $sql_delete);
    mysqli_stmt_bind_param($stmt_delete, "is", $medico_id_logado, $data_selecionada);
    mysqli_stmt_execute($stmt_delete);

    // 6. SEGUNDO: Insere os novos slots selecionados
    if (!empty($slots_selecionados)) {
        $sql_insert = "INSERT INTO disponibilidade_medicos (medico_id, data_disponivel, horario_disponivel, consultorio) VALUES (?, ?, ?, ?)";
        $stmt_insert = mysqli_prepare($conexao, $sql_insert);
        
        foreach ($slots_selecionados as $slot) {
            $horario = $slot['horario'];
            $consultorio = (int)$slot['consultorio'];
            mysqli_stmt_bind_param($stmt_insert, "issi", $medico_id_logado, $data_selecionada, $horario, $consultorio);
            mysqli_stmt_execute($stmt_insert);
        }
    }

    // 7. Sucesso!
    mysqli_commit($conexao);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'sucesso', 'mensagem' => 'Agenda atualizada com sucesso!']);

} catch (Exception $e) {
    // 8. Erro (Provavelmente conflito com outro médico)
    mysqli_rollback($conexao);
    header('Content-Type: application/json');
    
    // Verifica se foi um erro de chave duplicada (idx_slot_unico)
    if (mysqli_errno($conexao) == 1062) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Erro: Um ou mais horários selecionados já foram reservados por outro médico. A página será recarregada.']);
    } else {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao salvar: ' . $e->getMessage()]);
    }
}

mysqli_close($conexao);
?>