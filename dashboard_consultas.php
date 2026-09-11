<?php
// 1. Proteção, Conexão e Lógica
require_once 'header.php';
require_once 'auth.php';
// ================== CORREÇÃO AQUI ==================
proteger_pagina(['admin']); // Permite Admin 
// ================== FIM DA CORREÇÃO ==================
require_once 'conexao.php';
require_once 'estatisticas_consultas.php';

// 2. Calcula todas as estatísticas
$stats = calcularEstatisticasConsultas($conexao);

// 3. Prepara dados
$dados_graficos_json = json_encode($stats['graficos_dinamicos'] ?? []);
$kpis = $stats['kpis'] ?? []; 

?>

<div class="d-flex justify-content-between align-items-center">
    <h1>Dashboard de Consultas</h1>
</div>
<hr>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-white bg-primary text-center h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-calendar-day"></i> Consultas Hoje</h5>
                <p class="card-text fs-1 fw-bold"><?= $kpis['total_hoje'] ?? 0 ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-info text-center h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-calendar-alt"></i> Consultas no Mês</h5>
                <p class="card-text fs-1 fw-bold"><?= $kpis['total_mes'] ?? 0 ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-secondary text-center h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-calendar-week"></i> Consultas no Ano</h5>
                <p class="card-text fs-1 fw-bold"><?= $kpis['total_ano'] ?? 0 ?></p>
            </div>
        </div>
    </div>
</div>
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card text-white bg-success text-center h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-chart-pie"></i> Taxa de Ocupação</h5>
                <p class="card-text fs-1 fw-bold"><?= $kpis['taxa_ocupacao'] ?? 0 ?>%</p>
                <small>(Próximos agendamentos vs. horários abertos)</small>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card text-dark bg-light text-center h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-clock"></i> Tempo Médio de Espera</h5>
                <p class="card-text fs-1 fw-bold"><?= $kpis['tempo_medio_espera'] ?? 0 ?> dias</p>
                <small>(Média dos últimos 90 dias)</small>
            </div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <label for="filtroPerfil" class="form-label">Analisar Perfil Específico:</label>
        <select id="filtroPerfil" class="form-select">
            <option value="Geral">Visão Geral (Todos)</option>
            <option value="Gestante">Gestantes</option>
            <option value="Criança">Crianças (0-9 anos)</option>
            <option value="Adolescente">Adolescentes (10-19 anos)</option>
            <option value="Jovem">Jovens (20-24 anos)</option>
            <option value="Adulto">Adultos (25-59 anos)</option>
            <option value="Idoso">Idosos (60+)</option>
        </select>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header" id="especialidadeTitulo">Top 5 Especialidades (Geral)</div>
            <div class="card-body">
                <div style="position: relative; height:35vh; width:100%; margin: auto;">
                    <canvas id="graficoEspecialidade"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header" id="medicoTitulo">Top 5 Médicos (Geral)</div>
            <div class="card-body">
                <div style="position: relative; height:35vh; width:100%; margin: auto;">
                    <canvas id="graficoTopMedicos"></canvas> </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-header" id="faixaEtariaTitulo">Consultas por Faixa Etária (Geral)</div>
            <div class="card-body">
                <div style="position: relative; height:40vh; width:100%; margin: auto;">
                    <canvas id="graficoFaixaEtaria"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header">Tendência de Consultas (Últimos 30 dias)</div>
            <div class="card-body">
                <div style="position: relative; height:40vh; width:100%; margin: auto;">
                    <canvas id="graficoTendencia"></canvas>
                </div>
                <hr>
                <div class="mt-3">
                    <?= $kpis['tendencia_analise'] ?? '<p class="text-muted">Dados insuficientes para análise de tendência.</p>' ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    
    const todosOsDadosGraficos = <?= $dados_graficos_json ?>;

    // --- Definição das Cores ---
    const coresGrafico1 = [ 'rgba(13, 110, 253, 0.7)', 'rgba(25, 135, 84, 0.7)', 'rgba(255, 193, 7, 0.7)', 'rgba(220, 53, 69, 0.7)', 'rgba(111, 66, 193, 0.7)' ];
    const coresGrafico2 = [ 'rgba(25, 135, 84, 0.7)', 'rgba(13, 110, 253, 0.7)', 'rgba(108, 117, 125, 0.7)', 'rgba(255, 193, 7, 0.7)', 'rgba(220, 53, 69, 0.7)' ];


    // Gráfico 1: Especialidade (Barra Horizontal)
    const graficoEspecialidade = new Chart(document.getElementById('graficoEspecialidade'), {
        type: 'bar',
        data: {
            labels: [], 
            datasets: [{
                label: 'Nº de Consultas',
                data: [], 
                backgroundColor: coresGrafico1,
            }]
        },
        options: { 
            indexAxis: 'y', 
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });

    // ================== NOVO GRÁFICO (TOP 5 MÉDICOS) ==================
    const graficoTopMedicos = new Chart(document.getElementById('graficoTopMedicos'), {
        type: 'bar',
        data: {
            labels: [], // Será preenchido por JS
            datasets: [{
                label: 'Nº de Consultas',
                data: [], // Será preenchido por JS
                backgroundColor: coresGrafico2,
            }]
        },
        options: { 
            indexAxis: 'y', // <-- Gráfico horizontal
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
    // ================== FIM DO NOVO GRÁFICO ==================

    // Gráfico 3: Faixa Etária (Barra Vertical)
    const graficoFaixaEtaria = new Chart(document.getElementById('graficoFaixaEtaria'), {
        type: 'bar',
        data: {
            labels: [], 
            datasets: [{
                label: 'Nº de Consultas',
                data: [], 
                backgroundColor: 'rgba(25, 135, 84, 0.7)',
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });

    // Gráfico 4: Tendência (Linha)
    new Chart(document.getElementById('graficoTendencia'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($kpis['ultimos_30_dias'] ?? [], 'data_consulta')) ?>,
            datasets: [{
                label: 'Consultas por Dia',
                data: <?= json_encode(array_column($kpis['ultimos_30_dias'] ?? [], 'total')) ?>,
                fill: true,
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderColor: 'rgba(13, 110, 253, 1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });


    // --- Função Principal de Atualização (ATUALIZADA) ---
    // (Agora atualiza 3 gráficos)
    function atualizarGraficos(perfil) {
        const dadosDoPerfil = todosOsDadosGraficos[perfil];
        
        if (!dadosDoPerfil) {
            console.error('Dados não encontrados para o perfil:', perfil);
            return;
        }

        // Atualiza Gráfico de Especialidade
        graficoEspecialidade.data.labels = dadosDoPerfil.especialidade.labels;
        graficoEspecialidade.data.datasets[0].data = dadosDoPerfil.especialidade.data;
        graficoEspecialidade.update();
        document.getElementById('especialidadeTitulo').innerText = `Top 5 Especialidades (${perfil})`;

        // Atualiza Gráfico de Faixa Etária
        graficoFaixaEtaria.data.labels = dadosDoPerfil.faixa_etaria.labels;
        graficoFaixaEtaria.data.datasets[0].data = dadosDoPerfil.faixa_etaria.data;
        graficoFaixaEtaria.update();
        document.getElementById('faixaEtariaTitulo').innerText = `Consultas por Faixa Etária (${perfil})`;
        
        // ================== NOVA ATUALIZAÇÃO ==================
        // Atualiza Gráfico de Top Médicos
        graficoTopMedicos.data.labels = dadosDoPerfil.medicos.labels;
        graficoTopMedicos.data.datasets[0].data = dadosDoPerfil.medicos.data;
        graficoTopMedicos.update();
        document.getElementById('medicoTitulo').innerText = `Top 5 Médicos (${perfil})`;
        // ================== FIM DA ATUALIZAÇÃO ==================
    }

    // --- Event Listener para o Dropdown (Sem alteração) ---
    document.getElementById('filtroPerfil').addEventListener('change', function() {
        atualizarGraficos(this.value);
    });

    // --- Carga Inicial (Sem alteração) ---
    atualizarGraficos('Geral');

});
</script>
<?php
require_once 'footer.php'; 
?>