<?php
// === ADICIONE ISTO TEMPORARIAMENTE NO TOPO DE painel_disponibilidade_medica.php ===
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ============================================================================    

// 1. Proteção e Conexão
require_once 'auth.php';
proteger_pagina(['gerente', 'admin']); // Acesso para Gerente e Admin
require_once 'conexao.php';
require_once 'header.php';

// =================================================================
// LÓGICA DE CALENDÁRIO PARA VISUALIZAÇÃO SEMANAL (Adaptada do area_medico.php)
// =================================================================
$nomes_meses = [
    '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril',
    '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto',
    '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
];
$dias_semana_abrev = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('m');
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : date('Y');
$timestamp = mktime(0, 0, 0, $mes, 1, $ano);
$mes_atual_num = date('m', $timestamp);
$ano_atual = date('Y', $timestamp);
$primeiro_dia_mes = (int)date('w', $timestamp);
$total_dias_mes = (int)date('t', $timestamp);

// Lógica de navegação
$mes_anterior = date('m', mktime(0, 0, 0, $mes - 1, 1, $ano));
$ano_anterior = date('Y', mktime(0, 0, 0, $mes - 1, 1, $ano));
$mes_proximo = date('m', mktime(0, 0, 0, $mes + 1, 1, $ano));
$ano_proximo = date('Y', mktime(0, 0, 0, $mes + 1, 1, $ano));

// ------------------------------------------------------------------
// LÓGICA DE BUSCA DE COBERTURA
// ------------------------------------------------------------------

// 1. Busca a contagem de médicos por dia do mês
$sql_cobertura = "SELECT 
                    DAY(data_disponivel) AS dia, 
                    COUNT(DISTINCT medico_id) AS total_medicos 
                  FROM 
                    disponibilidade_medicos 
                  WHERE 
                    MONTH(data_disponivel) = ? AND YEAR(data_disponivel) = ?
                  GROUP BY 
                    DAY(data_disponivel)";

$stmt_cobertura = mysqli_prepare($conexao, $sql_cobertura);
mysqli_stmt_bind_param($stmt_cobertura, "ii", $mes, $ano);
mysqli_stmt_execute($stmt_cobertura);
$resultado_cobertura = mysqli_stmt_get_result($stmt_cobertura);

$disponibilidade_mes = [];
while ($row = mysqli_fetch_assoc($resultado_cobertura)) {
    $disponibilidade_mes[$row['dia']] = $row['total_medicos'];
}

// ------------------------------------------------------------------
?>

<style>
/* Estilos para a responsividade do calendário */
.calendar-gestor .dia-clicavel-gestor {
    cursor: pointer;
    border: 1px solid #dee2e6;
    padding: 5px; /* Reduz padding para telas menores */
    margin: 0;
    text-align: center;
    vertical-align: middle;
    height: 80px; /* Mantém uma altura razoável */
    font-size: 0.85rem; /* Fonte um pouco menor */
}
/* Estilo padrão (tela grande) */
.calendar-gestor .dia-clicavel-gestor .badge {
    font-size: 0.9rem;
}
/* Estilo para Hover */
.calendar-gestor .dia-clicavel-gestor:hover {
    background-color: #e9ecef;
}

/* NOVO CSS: Aumenta a fonte e coloca em negrito o número do dia em telas > 576px */
@media (min-width: 576px) {
    .calendar-gestor .dia-numero {
        font-size: 1.2rem; /* Aumenta o tamanho da fonte */
        font-weight: bold; /* Aplica negrito */
    }
}

/* CSS para otimizar o cabeçalho do calendário em telas pequenas */
@media (max-width: 576px) {
    .calendar-gestor .dia-semana {
        font-size: 0.8rem;
    }
}
</style>

<div class="row">
    <div class="col-md-12">
        <h2>Painel de Disponibilidade Médica</h2>
        <p class="text-muted">Acompanhe a cobertura de médicos por dia e por consultório.</p>
        <hr>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center">
            <a href="?mes=<?= $mes_anterior ?>&ano=<?= $ano_anterior ?>" class="btn btn-sm btn-light">&laquo; Anterior</a>
            <h4 class="mb-0 text-white"><?= $nomes_meses[$mes_atual_num] ?> de <?= $ano_atual ?></h4>
            <a href="?mes=<?= $mes_proximo ?>&ano=<?= $ano_proximo ?>" class="btn btn-sm btn-light">Próximo &raquo;</a>
        </div>
    </div>
    <div class="card-body">
        <div class="calendar-gestor">
            <div class="row g-0 mb-2 text-center">
                <?php foreach ($dias_semana_abrev as $dia): ?>
                    <div class="col dia-semana text-muted"><?= $dia ?></div>
                <?php endforeach; ?>
            </div>

            <div class="row g-0">
                <?php
                // Preenche os dias vazios do início
                for ($i = 0; $i < $primeiro_dia_mes; $i++) {
                    echo "<div class='col dia-clicavel-gestor bg-light border'>&nbsp;</div>";
                }

                for ($dia_atual = 1; $dia_atual <= $total_dias_mes; $dia_atual++) {
                    $data_completa = $ano_atual . '-' . $mes_atual_num . '-' . str_pad($dia_atual, 2, '0', STR_PAD_LEFT);
                    $total_medicos_dia = $disponibilidade_mes[$dia_atual] ?? 0;
                    
                    // 1. Define a classe de status com base na contagem
                    if ($total_medicos_dia == 0) {
                        $classe_status = 'bg-danger';
                    } elseif ($total_medicos_dia < 3) {
                        $classe_status = 'bg-warning';
                    } else {
                        $classe_status = 'bg-success';
                    }

                    // 2. Define o ícone de status (para telas pequenas)
                    $icone = ($total_medicos_dia == 0) ? 'fas fa-times-circle' : (($total_medicos_dia < 3) ? 'fas fa-exclamation-triangle' : 'fas fa-check-circle');


                    echo "<div class='col dia-clicavel-gestor border' data-data='{$data_completa}'>";
                    
                    // Número do dia: Adicionada a classe 'dia-numero'
                    echo "<span class='dia-numero'>{$dia_atual}</span><br>"; 
                    
                    // Exibe o texto em telas maiores que 'xs' (small)
                    echo "<span class='badge {$classe_status} d-none d-sm-inline'>{$total_medicos_dia} Médicos</span>"; 
                    
                    // Exibe o ícone em telas 'xs' (small)
                    echo "<span class='badge {$classe_status} d-inline d-sm-none'><i class='{$icone}'></i></span>";
                    
                    echo "</div>";

                    // Próxima coluna (próximo dia)
                    $primeiro_dia_mes++;

                    // Quebra a linha na tela se for Sábado (dia 6)
                    if ($primeiro_dia_mes % 7 == 0) {
                        echo "</div><div class='row g-0'>";
                    }
                }

                // Preenche os dias vazios do final
                while ($primeiro_dia_mes % 7 != 0) {
                    echo "<div class='col dia-clicavel-gestor bg-light border'>&nbsp;</div>";
                    $primeiro_dia_mes++;
                }
                ?>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-body">
        <div id="gradeCoberturaDetalhada" style="min-height: 200px;">
            <div class="table-responsive"> 
                <p class="text-center text-muted mt-5">Nenhuma data selecionada. Clique em um dia do calendário acima para ver a grade de cobertura.</p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.dia-clicavel-gestor').forEach(function(diaElemento) {
        diaElemento.addEventListener('click', function() {
            const dataSelecionada = diaElemento.getAttribute('data-data');
            const dataFormatada = dataSelecionada.split('-').reverse().join('/');
            const gradeContainer = document.getElementById('gradeCoberturaDetalhada');
            
            // Mensagem de carregamento dentro do wrapper responsivo
            gradeContainer.innerHTML = `<div class="table-responsive"><p class="text-center text-info mt-5">Carregando grade para ${dataFormatada}...</p></div>`;
            
            // Chama o script PHP que gera a grade detalhada para o Gestor
            // Nota: Mantenho o caminho 'gerenciar/buscar_cobertura_dia.php' como no seu snippet original
            fetch(`gerenciar/buscar_disponibilidade_dia.php?data=${dataSelecionada}`)
                .then(response => response.text())
                .then(html => {
                    // O HTML injetado DEVE CONTER O WRAPPER table-responsive
                    // Ou, para funcionar AGORA, injetamos a grade dentro do wrapper:
                    gradeContainer.innerHTML = `
                        <h4>Grade de Cobertura em ${dataFormatada}</h4><hr>
                        <div class="table-responsive">
                            ${html}
                        </div>
                    `;
                    // Opcional: Rola a página para a grade
                    gradeContainer.scrollIntoView({ behavior: 'smooth', block: 'start' }); 
                })
                .catch(err => {
                    gradeContainer.innerHTML = '<div class="alert alert-danger">Erro ao carregar a grade de cobertura.</div>';
                });
        });
    });
});
</script>

<?php require_once 'footer.php'; ?>