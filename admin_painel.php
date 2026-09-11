<?php
// 1. Proteção, Conexão e Lógica dos Dashboards
require_once 'header.php';
require_once 'auth.php';
proteger_pagina(['admin']); 
require_once 'conexao.php';

// ================== INCLUSÕES ==================
require_once 'estatisticas.php'; 
require_once 'estatisticas_consultas.php'; 
// ================== FIM DA ADIÇÃO ==================


$sucesso_msg = null;
$erro_msg = null;

// ==========================================================
// 2. LÓGICA DE ATUALIZAÇÃO (SALVAR AS METAS)
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Pega os valores do formulário
        $meta_vacinacao = (int)$_POST['meta_vacinacao'];
        $meta_tempo_fluxo = (int)$_POST['meta_tempo_fluxo']; 
        $meta_taxa_ocupacao = (int)$_POST['meta_taxa_ocupacao'];
        $meta_sla_exames = (int)$_POST['meta_sla_exames']; 
        
        // Inicia a transação
        mysqli_begin_transaction($conexao);
        
        // 1. Meta Vacinação
        $sql_vac = "UPDATE configuracoes SET valor = ? WHERE chave = 'meta_vacinacao'";
        $stmt_vac = mysqli_prepare($conexao, $sql_vac);
        mysqli_stmt_bind_param($stmt_vac, "i", $meta_vacinacao);
        mysqli_stmt_execute($stmt_vac);
        
        // 2. Meta Tempo de Fluxo
        $sql_fluxo = "UPDATE configuracoes SET valor = ? WHERE chave = 'meta_tempo_fluxo'";
        $stmt_fluxo = mysqli_prepare($conexao, $sql_fluxo);
        mysqli_stmt_bind_param($stmt_fluxo, "i", $meta_tempo_fluxo);
        mysqli_stmt_execute($stmt_fluxo);
        
        // 3. Meta Taxa de Ocupação
        $sql_ocupacao = "UPDATE configuracoes SET valor = ? WHERE chave = 'meta_taxa_ocupacao'";
        $stmt_ocupacao = mysqli_prepare($conexao, $sql_ocupacao);
        mysqli_stmt_bind_param($stmt_ocupacao, "i", $meta_taxa_ocupacao);
        mysqli_stmt_execute($stmt_ocupacao);
        
        // 4. Meta SLA de Exames (NOVO)
        $sql_sla_exames = "UPDATE configuracoes SET valor = ? WHERE chave = 'meta_sla_exames'";
        $stmt_sla_exames = mysqli_prepare($conexao, $sql_sla_exames);
        mysqli_stmt_bind_param($stmt_sla_exames, "i", $meta_sla_exames);
        mysqli_stmt_execute($stmt_sla_exames);
        
        // Confirma a transação
        mysqli_commit($conexao);
        
        $sucesso_msg = "Metas atualizadas com sucesso!";
        
    } catch (Exception $e) {
        mysqli_rollback($conexao);
        $erro_msg = "Erro ao salvar metas: " . $e->getMessage();
    }
}

// ==========================================================
// 3. LÓGICA DE LEITURA (BUSCAR DADOS PARA EXIBIR)
// ==========================================================

// 3a. Buscar as Metas salvas no banco
$sql_metas = "SELECT chave, valor FROM configuracoes WHERE chave IN ('meta_vacinacao', 'meta_tempo_fluxo', 'meta_taxa_ocupacao', 'meta_sla_exames')";
$resultado_metas = mysqli_query($conexao, $sql_metas);
$metas = [];
if ($resultado_metas) {
    while($row = mysqli_fetch_assoc($resultado_metas)) {
        $metas[$row['chave']] = $row['valor'];
    }
}

// ==========================================================
// 4. BUSCAR NOVOS DADOS ESTATÍSTICOS OPERACIONAIS
// ==========================================================

// A. Contagem de Pacientes em Risco (Diabético ou Cardíaco)
$sql_pacientes_risco = "SELECT COUNT(*) AS total_risco FROM pacientes WHERE is_diabetico = 1 OR is_cardiaco = 1";
$resultado_pacientes_risco = mysqli_query($conexao, $sql_pacientes_risco);
$total_pacientes_risco = mysqli_fetch_assoc($resultado_pacientes_risco)['total_risco'] ?? 0;

// B. Contagem de Exames Pendentes de Realização
$sql_exames_pendentes = "SELECT COUNT(*) AS total_pendente FROM pedidos_exames WHERE status = 'Solicitado'";
$resultado_exames_pendentes = mysqli_query($conexao, $sql_exames_pendentes);
$total_exames_pendentes = mysqli_fetch_assoc($resultado_exames_pendentes)['total_pendente'] ?? 0;

// C. Cálculo do Tempo Médio (Consulta Agendada até Fim da Triagem) em minutos
$sql_tempo_medio_triagem = "SELECT 
    AVG(TIMESTAMPDIFF(MINUTE, 
        CONCAT(c.data_consulta, ' ', c.horario_consulta), 
        t.data_triagem)) AS media_minutos
FROM consultas c
JOIN triagem t ON c.id = t.consulta_id
WHERE t.data_triagem IS NOT NULL"; 

$resultado_tempo_medio = mysqli_query($conexao, $sql_tempo_medio_triagem);
$media_triagem_minutos = mysqli_fetch_assoc($resultado_tempo_medio)['media_minutos'] ?? 0;

// CONVERTER PARA DIAS (DURAÇÃO TOTAL)
$media_triagem_dias = $media_triagem_minutos / 1440; // 1440 minutos em um dia
$media_triagem_formatada_dias = round($media_triagem_dias, 1);


// ==========================================================
// 5. CHAMAR FUNÇÕES DE ESTATÍSTICA (PARA GARANTIR DADOS)
// ==========================================================
$stats_vacina = calcularEstatisticas($conexao); 
$stats_consultas = calcularEstatisticasConsultas($conexao);


// ==========================================================
// 6. PREPARAÇÃO DAS VARIÁVEIS DE RESUMO EXECUTIVO
// ==========================================================

// Variáveis de vacinação
$total_pacientes = $stats_vacina['total_pacientes'] ?? 1; // Evita divisão por zero
$calendario_completo = $stats_vacina['calendario_completo'] ?? 0;
$calendario_incompleto = $stats_vacina['calendario_incompleto'] ?? 0;
$nao_vacinados = $stats_vacina['nao_vacinados'] ?? 0;
$meta_vacinacao_valor = $metas['meta_vacinacao'] ?? 90;

$porc_completo_geral = round(($calendario_completo / $total_pacientes) * 100, 1);
$pacientes_nao_em_dia = $calendario_incompleto + $nao_vacinados;

$texto_vacina_orientacao = "";
if ($total_pacientes == 0) {
    $texto_vacina_orientacao = 'Não há pacientes para calcular a cobertura.';
} elseif ($porc_completo_geral < $meta_vacinacao_valor) {
    $texto_vacina_orientacao = "A cobertura de <strong>{$porc_completo_geral}%</strong> está abaixo da meta de <strong>{$meta_vacinacao_valor}%</strong>. Há <strong>{$pacientes_nao_em_dia}</strong> pacientes (incompletos e não vacinados) precisando de agendamento ou conscientização.";
} else {
    $texto_vacina_orientacao = "A cobertura de <strong>{$porc_completo_geral}%</strong> superou a meta de <strong>{$meta_vacinacao_valor}%</strong>. Mantenha o esforço de agendamento ativo e proativo.";
}

// Variáveis de Consulta
$analise_tendencia_consulta = $stats_consultas['kpis']['tendencia_analise'] ?? 'N/D';
$tempo_espera_consulta = $stats_consultas['kpis']['tempo_medio_espera'] ?? '15 dias'; 
$meta_tempo_fluxo_valor = $metas['meta_tempo_fluxo'] ?? 30; // Usando a nova meta

$texto_consulta_orientacao = "";
if ($media_triagem_minutos > $meta_tempo_fluxo_valor) {
    $texto_consulta_orientacao = "O <strong>Tempo Médio de Fluxo</strong> (<strong>{$media_triagem_formatada_dias} dia(s)</strong>) está acima da meta de {$meta_tempo_fluxo_valor} minutos. Priorize o ajuste de escala da equipe de Enfermagem ou melhore a organização da fila de triagem.";
} else {
    $texto_consulta_orientacao = "O <strong>Tempo Médio de Fluxo</strong> (<strong>{$media_triagem_formatada_dias} dia(s)</strong>) está dentro da meta. Mantenha a eficiência na Triagem.";
}


// ==========================================================
// 7. INÍCIO DO HTML
// ==========================================================
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Painel de Controlo (Admin)</h1>
    </div>
    <p class="lead">Planeamento estratégico e definição de metas da clínica.</p>
    <hr>

    <?php if ($sucesso_msg): ?>
        <div class="alert alert-success"><?= $sucesso_msg ?></div>
    <?php endif; ?>
    <?php if ($erro_msg): ?>
        <div class="alert alert-danger"><?= $erro_msg ?></div>
    <?php endif; ?>

    <h3>Resumo Executivo</h3>
    <div class="row mb-4">
        
        <div class="col-md-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-secondary text-white">
                    <i class="fas fa-syringe me-2"></i> Análise de Vacinação
                </div>
                <div class="card-body">
                    <p class="mb-1">Meta de Cobertura: <?= $meta_vacinacao_valor ?>%</p>
                    <h5 class="card-title text-success"><?= $porc_completo_geral ?>% de Calendário Completo</h5>
                    <p class="mb-3 text-danger">Público Incompleto: <?= $pacientes_nao_em_dia ?> paciente(s).</p>
                    <p class="small text-muted">Orientação: <?= $texto_vacina_orientacao ?></p>
                    <a href="infovacinacao.php" class="btn btn-primary btn-sm mt-2">Acessar Dashboard de Vacinação</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-secondary text-white">
                    <i class="fas fa-stethoscope me-2"></i> Análise de Consultas e Fluxo
                </div>
                <div class="card-body">
                    <p class="mb-1">Tendência: <?= $analise_tendencia_consulta ?></p> 
                    <h5 class="card-title text-primary">Tempo Médio de Fluxo: <?= $media_triagem_formatada_dias ?> dia(s)</h5>
                    <p class="mb-3 text-muted">Tempo Máximo de Espera Agendado: <?= htmlspecialchars($tempo_espera_consulta) ?></p>
                    <p class="small text-muted">Orientação: <?= $texto_consulta_orientacao ?></p>
                    <a href="dashboard_consultas.php" class="btn btn-primary btn-sm mt-2">Acessar Dashboard de Consultas</a>
                </div>
            </div>
        </div>
    </div>
    
    <h3 class="mt-2">Métricas de Risco e Logística</h3>
    <div class="row mb-4">
        <div class="col-md-4 mb-4">
            <div class="card bg-danger text-white shadow h-100">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <h6 class="text-uppercase mb-2">Alerta: Pacientes de Alto Risco</h6>
                        <h3 class="display-4 fw-bold mb-0"><?= $total_pacientes_risco ?></h3>
                    </div>
                    <small class="mt-3">Pacientes com histórico de Diabetes ou Doença Cardíaca. Exigem comunicação proativa (vacinas/exames).</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card bg-warning text-dark shadow h-100">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <h6 class="text-uppercase mb-2">Exames Solicitados Pendentes</h6>
                        <h3 class="display-4 fw-bold mb-0"><?= $total_exames_pendentes ?></h3>
                    </div>
                    <small class="mt-3">Pedidos aguardando agendamento/realização. Monitorar a taxa de conclusão.</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card shadow h-100 border-light">
                 <div class="card-body d-flex flex-column justify-content-center align-items-center text-muted">
                    <i class="fas fa-plus-circle fa-2x mb-2"></i>
                    <p class="mb-0">Métrica Futura</p>
                </div>
            </div>
        </div>
    </div>
    <hr class="my-4">

    <h3>Definição de Metas Estratégicas</h3>
    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="admin_painel.php" method="POST">
                <div class="row">
                    
                    <div class="col-md-3 mb-3">
                        <label for="meta_vacinacao" class="form-label" style="min-height: 48px; font-size: 0.9em;">
                            <i class="fas fa-syringe me-2"></i>Meta de Cobertura Vacinal (%)
                        </label>
                        <div class="input-group" style="max-width: 160px;">
                            <input type="number" class="form-control" id="meta_vacinacao" name="meta_vacinacao" 
                                   value="<?= htmlspecialchars($metas['meta_vacinacao'] ?? '90') ?>" min="0" max="100">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="meta_tempo_fluxo" class="form-label" style="min-height: 48px; font-size: 0.9em;">
                            <i class="fas fa-stopwatch me-2"></i>Meta de Tempo Máximo de Fluxo (min)
                        </label>
                        <div class="input-group" style="max-width: 160px;">
                            <input type="number" class="form-control" id="meta_tempo_fluxo" name="meta_tempo_fluxo" 
                                   value="<?= htmlspecialchars($metas['meta_tempo_fluxo'] ?? '30') ?>" min="1">
                            <span class="input-group-text">minutos</span>
                        </div>
                        <small class="form-text text-muted">Tempo (Agendamento → Triagem).</small>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="meta_taxa_ocupacao" class="form-label" style="min-height: 48px; font-size: 0.9em;">
                            <i class="fas fa-chart-pie me-2"></i>Meta de Taxa de Ocupação (%)
                        </label>
                        <div class="input-group" style="max-width: 160px;">
                            <input type="number" class="form-control" id="meta_taxa_ocupacao" name="meta_taxa_ocupacao" 
                                   value="<?= htmlspecialchars($metas['meta_taxa_ocupacao'] ?? '75') ?>" min="0" max="100">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="meta_sla_exames" class="form-label" style="min-height: 48px; font-size: 0.9em;">
                            <i class="fas fa-flask me-2"></i>Meta SLA Agendamento Exames (dias)
                        </label>
                        <div class="input-group" style="max-width: 160px;">
                            <input type="number" class="form-control" id="meta_sla_exames" name="meta_sla_exames" 
                                   value="<?= htmlspecialchars($metas['meta_sla_exames'] ?? '5') ?>" min="1">
                            <span class="input-group-text">dias</span>
                        </div>
                        <small class="form-text text-muted">Tempo (Solicitação → Agendamento).</small>
                    </div>
                </div>
                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Salvar Metas
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <hr class="my-4">
    
    <h3>Logística e Próximos Passos (Exames)</h3>
    <div class="card shadow mb-4 border-primary">
        <div class="card-body">
            <p class="mb-2"><strong>Fluxo Agendamento de Exames (Próximo Passo CRÍTICO):</strong> Sua proposta de criar um botão **"Agendar Exame"** na área do paciente é vital para coletar a métrica de **"Tempo de Realização"** (Solicitação até Agendamento).</p>
            <p>Iremos criar o formulário de agendamento na área do paciente e o processador **`salvar_agendamento_exame.php`** que atualizará a tabela `pedidos_exames` com a data e o status 'Agendado'.</p>
        </div>
    </div>
    <div class="alert alert-light">
        <p class="mb-0"><strong>Informações sobre Estoque de Vacinas:</strong> Esta é uma funcionalidade futura (módulo "Estoque") para gerir lotes, vencimentos e quantidades.</p>
    </div>

</div>

<?php require_once 'footer.php'; ?>