<?php

// Inclui os arquivos necessários
require_once 'header.php';
require_once 'conexao.php';
require_once 'estatisticas.php';

require_once 'auth.php';
// ================== CORREÇÃO AQUI ==================
proteger_pagina(['admin']); // Permite Admin 
// ================== FIM DA CORREÇÃO ==================

// --- BUSCAR META DE VACINAÇÃO ---
$sql_meta = "SELECT valor FROM configuracoes WHERE chave = 'meta_vacinacao'";
$resultado_meta = mysqli_query($conexao, $sql_meta);
$meta_vacinacao_valor = mysqli_fetch_assoc($resultado_meta)['valor'] ?? 90; // Valor padrão 90%
// --------------------------------

// Chama a função principal para obter todos os dados estatísticos
$stats = calcularEstatisticas($conexao);

// Prepara os dados para os cards
$total_pacientes = $stats['total_pacientes'];
if ($total_pacientes > 0) {
    $porc_completo = round(($stats['calendario_completo'] / $total_pacientes) * 100, 1);
    $porc_incompleto = round(($stats['calendario_incompleto'] / $total_pacientes) * 100, 1);
    $porc_nao_vacinados = round(($stats['nao_vacinados'] / $total_pacientes) * 100, 1);
    
    // --- CÁLCULO CRÍTICO DA META ---
    $progresso_meta = round(($porc_completo / $meta_vacinacao_valor) * 100);
    $progresso_visual = min(100, $progresso_meta); // Limita a barra em 100%
} else {
    $porc_completo = $porc_incompleto = $porc_nao_vacinados = 0;
    $progresso_meta = 0;
    $progresso_visual = 0;
}
?>

<div class="d-flex justify-content-between align-items-center">
    <h1>Dashboard de Vacinação</h1>
    <span class="text-muted">Total de Pacientes: <?= $total_pacientes ?></span>
</div>
<hr>

<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <h5 class="card-title mb-3"><i class="fas fa-bullseye me-2"></i>Progresso em Relação à Meta de Cobertura (<?= $meta_vacinacao_valor ?>%)</h5>
        
        <div class="progress" style="height: 30px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated 
                        <?= ($progresso_meta >= 100) ? 'bg-success' : 'bg-primary' ?>" 
                 role="progressbar" 
                 style="width: <?= $progresso_visual ?>%;" 
                 aria-valuenow="<?= $progresso_visual ?>" 
                 aria-valuemin="0" 
                 aria-valuemax="100">
                 
                <span class="fw-bold fs-6">
                    <?= $porc_completo ?>% Coberto (<?= $progresso_meta ?>% da Meta)
                </span>
            </div>
        </div>
        
        <p class="mt-2 text-muted small">Taxa de Cobertura Atual (Calendário Completo): **<?= $porc_completo ?>%**</p>
    </div>
</div>
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-white bg-success text-center h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-check-circle"></i> Calendário Completo</h5>
                <p class="card-text fs-1 fw-bold"><?= $porc_completo ?>%</p>
                <small><?= $stats['calendario_completo'] ?> paciente(s)</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-warning text-center h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-exclamation-triangle"></i> Calendário Incompleto</h5>
                <p class="card-text fs-1 fw-bold"><?= $porc_incompleto ?>%</p>
                <small><?= $stats['calendario_incompleto'] ?> paciente(s)</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-danger text-center h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-times-circle"></i> Não Vacinados</h5>
                <p class="card-text fs-1 fw-bold"><?= $porc_nao_vacinados ?>%</p>
                <small><?= $stats['nao_vacinados'] ?> paciente(s)</small>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Status de Vacinação</span>
                <select id="filtroPerfil" class="form-select form-select-sm" style="width: auto;">
                    <option value="todos">Todos os Pacientes</option>
                    <option value="Criança">Crianças</option>
                    <option value="Adolescente">Adolescentes</option>
                    <option value="Jovem">Jovens</option>
                    <option value="Adulto">Adultos</option>
                    <option value="Idoso">Idosos</option>
                </select>
            </div>
            <div class="card-body">
                <div style="position: relative; height:35vh; width:100%; margin: auto;">
                    <canvas id="graficoStatusGeral"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-header">
                Top 3 Vacinas Aplicadas (por perfil selecionado)
            </div>
            <div class="card-body">
                <div style="position: relative; height:35vh; width:100%; margin: auto;">
                    <canvas id="graficoTopVacinas"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Contagem de Pacientes com Calendário (<span id="titulo_status_barra">Completo</span>) por Perfil</span>
                
                <select id="filtroStatusBarra" class="form-select form-select-sm" style="width: auto;">
                    <option value="completo" selected>Completo</option>
                    <option value="incompleto">Incompleto</option>
                    <option value="nao_vacinado">Não Vacinado</option>
                </select>
                </div>
            <div class="card-body">
                <div style="position: relative; height:40vh; width:100%; margin: auto;">
                    <canvas id="graficoCompletosPorPerfil"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const allStats = <?php echo json_encode($stats); ?>;

    // ===== PLUGIN CORRIGIDO E MAIS SEGURO (MANTIDO) =====
    const centerTextPlugin = {
        id: 'centerText',
        afterDraw: (chart) => {
            if (chart.config.type === 'doughnut' && chart.options.plugins && chart.options.plugins.centerText && chart.options.plugins.centerText.text !== undefined) {
                const ctx = chart.ctx;
                const centerX = (chart.chartArea.left + chart.chartArea.right) / 2;
                const centerY = (chart.chartArea.top + chart.chartArea.bottom) / 2;
                const text = chart.options.plugins.centerText.text;
                const label = chart.options.plugins.centerText.label;
                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.font = 'bold 40px Arial';
                ctx.fillStyle = '#495057';
                ctx.fillText(text, centerX, centerY - 10);
                ctx.font = '16px Arial';
                ctx.fillStyle = '#6c757d';
                ctx.fillText(label, centerX, centerY + 20);
                ctx.restore();
            }
        }
    };
    Chart.register(centerTextPlugin);
    // ===== FIM DO PLUGIN =====

    // --- GRÁFICO 1: PIZZA/DOUGHNUT ---
    const graficoPizza = new Chart(document.getElementById('graficoStatusGeral'), {
        type: 'doughnut',
        data: {
            labels: ['Completo', 'Incompleto', 'Não Vacinado'],
            datasets: [{
                label: 'Pacientes',
                data: [allStats.calendario_completo, allStats.calendario_incompleto, allStats.nao_vacinados],
                backgroundColor: ['#198754', '#ffc107', '#dc3545'],
                borderWidth: 1
            }]
        },
        options: { 
            responsive: true, maintainAspectRatio: false,
            plugins: { 
                legend: { position: 'right' },
                title: { display: true, text: 'Distribuição Geral de Pacientes' },
                centerText: { text: allStats.total_pacientes, label: 'Paciente(s)' }
            } 
        }
    });

    // --- GRÁFICO 2: BARRAS HORIZONTAIS (TOP 3) ---
    const graficoTopVacinas = new Chart(document.getElementById('graficoTopVacinas'), {
        type: 'bar',
        data: { labels: [], datasets: [{ label: 'Nº de Doses Aplicadas', data: [], backgroundColor: 'rgba(13, 110, 253, 0.7)' }] },
        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });
    
    // --- GRÁFICO 3: BARRAS VERTICAIS (COMPLETOS POR PERFIL) ---
    const perfisLabels = Object.keys(allStats.status_por_perfil).filter(p => p !== 'N/D');
    const completosPorPerfilData = perfisLabels.map(p => allStats.status_por_perfil[p].completo);
    const graficoCompletosPorPerfil = new Chart(document.getElementById('graficoCompletosPorPerfil'), {
        type: 'bar',
        data: {
            labels: perfisLabels,
            datasets: [{
                label: 'Nº de Pacientes com Calendário Completo',
                data: completosPorPerfilData,
                backgroundColor: 'rgba(25, 135, 84, 0.7)',
                borderColor: 'rgba(25, 135, 84, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });

    // --- FUNÇÃO DE ATUALIZAÇÃO PARA O GRÁFICO DE BARRAS (NOVA) ---
    const tituloStatusSpan = document.getElementById('titulo_status_barra');

    function atualizarGraficoBarra(statusSelecionado = 'completo') {
        let novosDados;
        let novoTituloLabel;
        let corBarra;

        // Mapeia o status selecionado para a chave de dados e cor
        switch (statusSelecionado) {
            case 'incompleto':
                novosDados = perfisLabels.map(p => allStats.status_por_perfil[p].incompleto);
                novoTituloLabel = 'Incompleto';
                corBarra = 'rgba(255, 193, 7, 0.7)'; // Amarelo (Warning)
                break;
            case 'nao_vacinado':
                novosDados = perfisLabels.map(p => allStats.status_por_perfil[p].nao_vacinado);
                novoTituloLabel = 'Não Vacinado';
                corBarra = 'rgba(220, 53, 69, 0.7)'; // Vermelho (Danger)
                break;
            case 'completo':
            default:
                novosDados = perfisLabels.map(p => allStats.status_por_perfil[p].completo);
                novoTituloLabel = 'Completo';
                corBarra = 'rgba(25, 135, 84, 0.7)'; // Verde (Success)
                break;
        }

        // 1. Atualiza o título do gráfico no cabeçalho
        tituloStatusSpan.textContent = novoTituloLabel; 

        // 2. Atualiza os dados e a cor do Chart.js
        graficoCompletosPorPerfil.data.datasets[0].label = `Nº de Pacientes com Calendário ${novoTituloLabel}`;
        graficoCompletosPorPerfil.data.datasets[0].data = novosDados;
        graficoCompletosPorPerfil.data.datasets[0].backgroundColor = corBarra;
        graficoCompletosPorPerfil.update();
    }
    
    // --- FUNÇÃO DE ATUALIZAÇÃO PARA OS GRÁFICOS PIZZA/TOP3 (EXISTENTE) ---
    function atualizarGraficosPrincipais(perfilSelecionado = 'todos') {
        let dadosPizza, tituloPizza, totalPizza, dadosVacinas;
        if (perfilSelecionado === 'todos') {
            dadosPizza = [allStats.calendario_completo, allStats.calendario_incompleto, allStats.nao_vacinados];
            tituloPizza = 'Distribuição Geral de Pacientes';
            totalPizza = allStats.total_pacientes;
            dadosVacinas = allStats.total_vacinas_aplicadas;
        } else {
            const dadosDoPerfil = allStats.status_por_perfil[perfilSelecionado];
            dadosPizza = [dadosDoPerfil.completo, dadosDoPerfil.incompleto, dadosDoPerfil.nao_vacinado];
            tituloPizza = 'Distribuição para o Perfil: ' + perfilSelecionado;
            totalPizza = dadosDoPerfil.total;
            dadosVacinas = allStats.vacinas_por_perfil[perfilSelecionado];
        }

        graficoPizza.data.datasets[0].data = dadosPizza;
        graficoPizza.options.plugins.title.text = tituloPizza;
        graficoPizza.options.plugins.centerText.text = totalPizza;
        graficoPizza.update();

        const top3Vacinas = Object.entries(dadosVacinas).sort(([, a], [, b]) => b - a).slice(0, 3);
        graficoTopVacinas.data.labels = top3Vacinas.map(item => item[0].charAt(0).toUpperCase() + item[0].slice(1));
        graficoTopVacinas.data.datasets[0].data = top3Vacinas.map(item => item[1]);
        graficoTopVacinas.update();
    }
    
    // --- EVENTOS ---
    document.getElementById('filtroPerfil').addEventListener('change', function() {
        atualizarGraficosPrincipais(this.value);
    });
    
    // NOVO LISTENER para o filtro de status da barra
    document.getElementById('filtroStatusBarra').addEventListener('change', function() {
        atualizarGraficoBarra(this.value);
    });

    // --- CARREGAMENTO INICIAL ---
    atualizarGraficosPrincipais(); 
    atualizarGraficoBarra('completo'); // Inicializa o gráfico de barras com status Completo
});
</script>

<?php
require_once 'footer.php'; 
?>