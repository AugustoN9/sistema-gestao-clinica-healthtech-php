<?php

// === ADICIONE ISTO TEMPORARIAMENTE NO TOPO ===
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ===========================================

// 1. Proteção e Conexão
// Começamos a sessão, mas não "protegemos" a página
// pois ela pode ser usada pelo 'gerente' (em agendar_consulta.php)
// ou pelo 'paciente' (em uma futura tela de auto-agendamento).
session_start();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Acesso negado.']);
    exit();
}
require_once '../conexao.php';

// 2. Validação do Input
if (!isset($_GET['medico_id']) || !is_numeric($_GET['medico_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'erro', 'mensagem' => 'ID do médico inválido.']);
    exit();
}
$medico_id = (int)$_GET['medico_id'];

// 3. Buscar SLOTS TOTAIS que o médico ABRIU (a partir de hoje)
$sql_disponiveis = "SELECT data_disponivel, horario_disponivel, consultorio
                    FROM disponibilidade_medicos
                    WHERE medico_id = ? AND data_disponivel >= CURDATE()
                    ORDER BY data_disponivel, horario_disponivel";

$stmt_disponiveis = mysqli_prepare($conexao, $sql_disponiveis);
mysqli_stmt_bind_param($stmt_disponiveis, "i", $medico_id);
mysqli_stmt_execute($stmt_disponiveis);
$resultado_disponiveis = mysqli_stmt_get_result($stmt_disponiveis);

$slots_disponiveis = [];
while ($row = mysqli_fetch_assoc($resultado_disponiveis)) {
    // Cria uma chave única para o slot (Data + Horário)
    $chave_slot = $row['data_disponivel'] . '_' . $row['horario_disponivel'];
    $slots_disponiveis[$chave_slot] = [
        'data' => $row['data_disponivel'],
        'horario' => $row['horario_disponivel'],
        'consultorio' => $row['consultorio']
    ];
}

// 4. Buscar SLOTS OCUPADOS (consultas já marcadas) para este médico
$sql_ocupados = "SELECT data_consulta, horario_consulta
                 FROM consultas
                 WHERE medico_id = ? AND data_consulta >= CURDATE()";

$stmt_ocupados = mysqli_prepare($conexao, $sql_ocupados);
mysqli_stmt_bind_param($stmt_ocupados, "i", $medico_id);
mysqli_stmt_execute($stmt_ocupados);
$resultado_ocupados = mysqli_stmt_get_result($stmt_ocupados);

// 5. Remover os slots ocupados da lista de slots disponíveis
while ($row = mysqli_fetch_assoc($resultado_ocupados)) {
    // CRÍTICO: CORRIGIDO para usar a string TIME diretamente
    $chave_slot_ocupado = $row['data_consulta'] . '_' . $row['horario_consulta'];
    
    // Se a chave existir na nossa lista de slots disponíveis, remove ela
    if (isset($slots_disponiveis[$chave_slot_ocupado])) {
        unset($slots_disponiveis[$chave_slot_ocupado]);
    }
}

// 6. Formatar o resultado para o JavaScript (agrupado por dia)
$horarios_formatados = [];
foreach ($slots_disponiveis as $slot) {
    $data = $slot['data'];
    if (!isset($horarios_formatados[$data])) {
        $horarios_formatados[$data] = []; // Cria um array para este dia
    }
    
    // Adiciona o horário (e o consultório) àquele dia
    $horarios_formatados[$data][] = [
        'horario' => $slot['horario'], // Mantém o formato HH:MM:SS para o valor interno
        'consultorio' => $slot['consultorio']
    ];
}

// 7. Retornar o JSON
header('Content-Type: application/json');
echo json_encode([
    'status' => 'ok', // Mudei para 'ok' para seguir o padrão do frontend
    'horarios_disponiveis' => $horarios_formatados
]);

mysqli_close($conexao);
?>