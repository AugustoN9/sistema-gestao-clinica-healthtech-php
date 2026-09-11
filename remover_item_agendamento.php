v<?php
// 1. Proteção e Conexão
require_once 'auth.php';
proteger_pagina(['enfermagem']); // Apenas enfermagem pode aceder
require_once 'conexao.php';

// 2. Validar os dados da URL
if (!isset($_GET['item_id']) || !is_numeric($_GET['item_id'])) {
    die('ID do item inválido.');
}
$item_id = (int)$_GET['item_id'];

// Pega a data de retorno para o filtro do calendário
$data_retorno = $_GET['data'] ?? date('Y-m-d');

// 3. Executar a exclusão
// Apaga a vacina específica da lista de tarefas da enfermagem
$sql = "DELETE FROM agendamento_vacinas_itens WHERE id = ?";
$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, "i", $item_id);

if (mysqli_stmt_execute($stmt)) {
    // Sucesso! Volta para o painel com a data correta
    header("Location: area_enfermagem.php?data=$data_retorno&info=" . urlencode("Item de vacina removido do agendamento."));
} else {
    // Erro!
    header("Location: area_enfermagem.php?data=$data_retorno&erro=" . urlencode("Erro ao remover o item."));
}
exit();
?>