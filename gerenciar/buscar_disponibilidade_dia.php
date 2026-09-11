<?php
// ATENÇÃO: Se estiver a ter erros 500, descomente as linhas abaixo TEMPORARIAMENTE.
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// 1. Iniciar a Sessão e Proteção (CRÍTICO para chamadas AJAX)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../auth.php';
proteger_pagina(['gerente', 'admin']); 
require_once '../conexao.php';

$data_selecionada = $_GET['data'] ?? date('Y-m-d');

// 2. Definir os horários (Regra de Negócio)
$horarios_manha = [];
$horarios_tarde = [];
// Manhã: 08:00 (início) até 12:00 (início da última) - 9 slots de 30 minutos
for ($i = 0; $i < 9; $i++) {
    $horarios_manha[] = date('H:i:s', strtotime("08:00 + " . ($i * 30) . " minutes"));
}
// Tarde: 13:00 (início) até 17:00 (início da última) - 9 slots de 30 minutos
for ($i = 0; $i < 9; $i++) {
    $horarios_tarde[] = date('H:i:s', strtotime("13:00 + " . ($i * 30) . " minutes"));
}
$todos_horarios = array_merge($horarios_manha, $horarios_tarde);

// 3. Buscar DISPONIBILIDADE e DADOS DO MÉDICO
$sql = "SELECT 
            dm.horario_disponivel, 
            dm.consultorio, 
            m.nome_completo,
            m.especialidade
        FROM 
            disponibilidade_medicos dm
        JOIN 
            medicos m ON dm.medico_id = m.id
        WHERE 
            dm.data_disponivel = ?";
            
$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, "s", $data_selecionada);

if (!mysqli_stmt_execute($stmt)) {
    echo '<div class="alert alert-danger">Erro SQL ao buscar a disponibilidade: ' . mysqli_error($conexao) . '</div>';
    exit();
}

$resultado = mysqli_stmt_get_result($stmt);

// 4. Mapear os slots ocupados
$slots_ocupados = [];
while ($row = mysqli_fetch_assoc($resultado)) {
    $chave = $row['consultorio'] . '-' . $row['horario_disponivel'];
    $slots_ocupados[$chave] = [
        'nome' => $row['nome_completo'],
        'especialidade' => $row['especialidade']
    ];
}

// 5. Gerar o HTML da grade para o Gestor
?>

<style>
/* 1. ESTILOS BASE PARA GRID */
.grid-container {
    display: grid;
    grid-template-columns: 80px repeat(6, 1fr); /* 80px fixo para a coluna de horário */
    border: 1px solid #dee2e6;
    font-size: 0.8rem; /* Fonte menor para caber mais informação */
}
.grid-header {
    background-color: #f8f9fa;
    font-weight: bold;
    padding: 6px; /* Padding reduzido */
    border-bottom: 1px solid #dee2e6;
    border-right: 1px solid #dee2e6;
    text-align: center;
}
.grid-cell {
    padding: 4px; /* Padding reduzido */
    border-bottom: 1px solid #dee2e6;
    border-right: 1px solid #dee2e6;
    min-height: 40px; /* Altura mínima reduzida */
    text-align: center;
    vertical-align: middle;
}
.grid-cell:nth-child(7n) { border-right: none; } 

/* 2. ESTILOS ESPECÍFICOS */
.grid-cell.horario-label {
    background-color: #e9ecef;
    font-weight: bold;
    font-size: 0.9rem;
    padding: 6px;
}
.cell-vazia {
    background-color: #fff3f3; /* Vermelho mais suave */
    color: #dc3545;
    font-weight: 500;
}
.cell-ocupada {
    background-color: #d1e7dd; /* Verde claro */
    font-size: 0.75rem; /* Fonte muito pequena para nome/especialidade */
    line-height: 1.1;
}
</style>

<div class="grid-container">
    <div class="grid-header">Horário</div>
    <?php for ($c = 1; $c <= 6; $c++): ?>
        <div class="grid-header">Consultório <?= $c ?></div>
    <?php endfor; ?>
    
    <?php foreach ($todos_horarios as $horario): ?>
        <div class="grid-cell horario-label">
            <?= date('H:i', strtotime($horario)) ?>
        </div>
        
        <?php for ($c = 1; $c <= 6; $c++): 
            $chave = $c . '-' . $horario;
            $info = $slots_ocupados[$chave] ?? null;
        ?>
            <div class="grid-cell <?= $info ? 'cell-ocupada' : 'cell-vazia' ?>">
                <?php if ($info): ?>
                    <strong><?= htmlspecialchars($info['nome']) ?></strong><br>
                    <span class="text-muted"><?= htmlspecialchars($info['especialidade']) ?></span>
                <?php else: ?>
                    -
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    <?php endforeach; ?>
</div>