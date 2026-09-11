<?php
// ================== CORREÇÃO CRÍTICA AQUI ==================
// 1. Inclui conexão e autenticação antes de tudo
require_once 'auth.php';
proteger_pagina(['enfermagem']);
require_once 'conexao.php';
// ================== FIM DA CORREÇÃO ==================


// =================================================================
// LÓGICA DO CALENDÁRIO (Baseado em consultas.php)
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
$mes_anterior = date('m', mktime(0, 0, 0, $mes - 1, 1, $ano));
$ano_anterior = date('Y', mktime(0, 0, 0, $mes - 1, 1, $ano));
$mes_proximo = date('m', mktime(0, 0, 0, $mes + 1, 1, $ano));
$ano_proximo = date('Y', mktime(0, 0, 0, $mes + 1, 1, $ano));

// --- Busca os dias com agendamentos (Agora a variável $conexao existe) ---
$sql_dias_com_agenda = "SELECT DISTINCT DATE(data_agendamento) as dia 
                        FROM agendamento_vacinacao 
                        WHERE MONTH(data_agendamento) = ? AND YEAR(data_agendamento) = ? AND status = 'Aguardando'";
$stmt_dias = mysqli_prepare($conexao, $sql_dias_com_agenda);
mysqli_stmt_bind_param($stmt_dias, "ii", $mes_atual_num, $ano_atual);
mysqli_stmt_execute($stmt_dias);
$resultado_dias = mysqli_stmt_get_result($stmt_dias);
$dias_com_agenda = [];
if ($resultado_dias) {
    while ($row = mysqli_fetch_assoc($resultado_dias)) {
        $dias_com_agenda[] = $row['dia'];
    }
}
// =================================================================
// FIM DA LÓGICA DO CALENDÁRIO
// =================================================================

$data_selecionada = $_GET['data'] ?? date('Y-m-d');
$data_selecionada_formatada = date('d/m/Y', strtotime($data_selecionada));

// 2. Buscar TODOS os agendamentos da DATA SELECIONADA
$sql_agendamentos = "SELECT a.id, a.paciente_id, a.turno, p.nome as nome_paciente, p.data_nascimento
                     FROM agendamento_vacinacao a
                     JOIN pacientes p ON a.paciente_id = p.id
                     WHERE a.data_agendamento = ? AND a.status = 'Aguardando'
                     ORDER BY a.turno, a.data_criacao ASC"; 
$stmt = mysqli_prepare($conexao, $sql_agendamentos);
mysqli_stmt_bind_param($stmt, "s", $data_selecionada);
mysqli_stmt_execute($stmt);
$resultado_agendamentos = mysqli_stmt_get_result($stmt);

if (!$resultado_agendamentos) {
    die("Erro ao buscar agendamentos: " . mysqli_error($conexao));
}

// 3. Buscar os ITENS (vacinas) da DATA SELECIONADA
$sql_itens = "SELECT i.id, i.agendamento_id, i.nome_vacina, i.dose_recomendada
              FROM agendamento_vacinas_itens i
              JOIN agendamento_vacinacao a ON i.agendamento_id = a.id
              WHERE a.data_agendamento = ? AND a.status = 'Aguardando'"; 
$stmt_itens = mysqli_prepare($conexao, $sql_itens);
mysqli_stmt_bind_param($stmt_itens, "s", $data_selecionada);
mysqli_stmt_execute($stmt_itens);
$resultado_itens = mysqli_stmt_get_result($stmt_itens);

// Mapeia os itens para seus agendamentos
$vacinas_por_agendamento = [];
while ($item = mysqli_fetch_assoc($resultado_itens)) {
    $vacinas_por_agendamento[$item['agendamento_id']][] = [
        'id' => $item['id'], 
        'nome' => $item['nome_vacina'],
        'dose' => $item['dose_recomendada'] 
    ]; 
}

// 4. Separa os agendamentos por turno (Lógica existente)
$lista_manha = [];
$lista_tarde = [];
while ($agendamento = mysqli_fetch_assoc($resultado_agendamentos)) {
    $agendamento['vacinas'] = $vacinas_por_agendamento[$agendamento['id']] ?? [];
    
    $idade = 'N/D';
    if (!empty($agendamento['data_nascimento'])) {
        $data_nasc = new DateTime($agendamento['data_nascimento']);
        $hoje_dt = new DateTime('now');
        if ($data_nasc->format('Y') > 0) {
            $idade = $data_nasc->diff($hoje_dt)->y;
        }
    }
    $agendamento['idade'] = $idade;

    if ($agendamento['turno'] == 'manha') {
        $lista_manha[] = $agendamento;
    } else {
        $lista_tarde[] = $agendamento;
    }
}

require_once 'header.php';
?>

<style>
.calendar .dia-com-agenda a { font-weight: bold; background-color: #e9ecef; border-radius: 0.25rem; display: block; }
.calendar .hoje a { background-color: #0d6efd; color: white !important; border-radius: 0.25rem; }
.calendar .selecionado a { background-color: #198754; color: white !important; border-radius: 0.25rem;}
.calendar td { vertical-align: middle; padding: 2px; }
.calendar a { text-decoration: none; color: inherit; }
</style>

<div class="container mt-4">
    <h1>Painel de Enfermagem</h1>
    
    <ul class="nav nav-tabs mt-3">
        <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="area_enfermagem.php">
                <i class="fas fa-syringe me-1"></i> Agendamentos de Vacinação
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-dark" href="enfermagem_fila_triagem.php">
                <i class="fas fa-clipboard-user me-1"></i> Fila de Triagem (Consultas)
            </a>
        </li>
    </ul>
    <p class="lead mt-3">Agendamentos de vacinação</p>
    <hr>

    <?php if(isset($_GET['sucesso'])): ?><div class="alert alert-success">Paciente <strong><?= htmlspecialchars($_GET['sucesso']) ?></strong> vacinado e registado com sucesso!</div><?php endif; ?>
    <?php if(isset($_GET['erro'])): ?><div class="alert alert-danger"><?= htmlspecialchars($_GET['erro']) ?></div><?php endif; ?>
    <?php if(isset($_GET['info'])): ?><div class="alert alert-info"><?= htmlspecialchars($_GET['info']) ?></div><?php endif; ?>

    <div class="row">

        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <a href="?mes=<?= $mes_anterior ?>&ano=<?= $ano_anterior ?>" class="btn btn-sm btn-outline-secondary">&lt;</a>
                    <h5 class="mb-0"><?= $nomes_meses[$mes_atual_num] . ' ' . $ano_atual ?></h5>
                    <a href="?mes=<?= $mes_proximo ?>&ano=<?= $ano_proximo ?>" class="btn btn-sm btn-outline-secondary">&gt;</a>
                </div>
                <div class="card-body">
                    <table class="table table-bordered text-center calendar">
                        <thead><tr><?php foreach ($dias_semana_abrev as $dia) { echo "<th>$dia</th>"; } ?></tr></thead>
                        <tbody>
                            <tr>
                            <?php
                            $dia_atual = 1;
                            for ($i = 0; $i < $primeiro_dia_mes; $i++) { echo '<td></td>'; }
                            while ($dia_atual <= $total_dias_mes) {
                                $data_completa = $ano_atual . '-' . $mes_atual_num . '-' . str_pad($dia_atual, 2, '0', STR_PAD_LEFT);
                                $classe_dia = '';
                                if (in_array($data_completa, $dias_com_agenda)) { $classe_dia = 'dia-com-agenda'; }
                                if (date('Y-m-d') == $data_completa) { $classe_dia .= ' hoje'; }
                                if ($data_selecionada == $data_completa) { $classe_dia .= ' selecionado'; }
                                
                                echo "<td class='$classe_dia'><a href='?data=$data_completa'>$dia_atual</a></td>";
                                
                                if ((($dia_atual + $primeiro_dia_mes) % 7) == 0) { echo '</tr><tr>'; }
                                $dia_atual++;
                            }
                            while ((($dia_atual + $primeiro_dia_mes - 1) % 7) != 0) { echo '<td></td>'; $dia_atual++; }
                            ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <h4>Pacientes de: <?= $data_selecionada_formatada ?></h4>
            <div class="row">
                <div class="col-md-6">
                    <h3><i class="fas fa-sun me-2"></i>Manhã</h3>
                    <div class="accordion" id="accordionManha">
                        <?php if (empty($lista_manha)): ?>
                            <p class="text-muted">Nenhum paciente agendado.</p>
                        <?php else: ?>
                            <?php foreach ($lista_manha as $ag): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading-<?= $ag['id'] ?>">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= $ag['id'] ?>">
                                            <strong><?= htmlspecialchars($ag['nome_paciente']) ?></strong> (<?= $ag['idade'] ?> anos)
                                        </button>
                                    </h2>
                                    <div id="collapse-<?= $ag['id'] ?>" class="accordion-collapse collapse" data-bs-parent="#accordionManha">
                                        <div class="accordion-body">
                                            <form action="aplicar_vacina.php" method="POST">
                                                <input type="hidden" name="agendamento_id" value="<?= $ag['id'] ?>">
                                                <input type="hidden" name="paciente_id" value="<?= $ag['paciente_id'] ?>">
                                                <input type="hidden" name="nome_paciente" value="<?= htmlspecialchars($ag['nome_paciente']) ?>">
                                                
                                                <h5>Vacinas a Aplicar:</h5>
                                                <?php foreach ($ag['vacinas'] as $vacina): ?>
                                                    <?php $vacina_nome_safe = htmlspecialchars($vacina['nome'] ?? 'Erro'); ?>
                                                    
                                                    <?php $vacina_dose_safe = htmlspecialchars($vacina['dose'] ?? ''); ?>
                                                    
                                                    <div class="card mb-3">
                                                        <div class="card-body">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <h6 class="card-title mb-0"><?= $vacina_nome_safe ?></h6>
                                                                
                                                                <a href="remover_item_agendamento.php?item_id=<?= $vacina['id'] ?>&data=<?= $data_selecionada ?>" 
                                                                   class="btn btn-outline-danger btn-sm" 
                                                                   title="Remover (Sem Estoque)"
                                                                   onclick="return confirm('Tem certeza que deseja remover esta vacina do agendamento por falta de estoque? O paciente continuará com ela pendente.');">
                                                                    <i class="fas fa-times"></i>
                                                                </a>
                                                            </div>
                                                            <hr>
                                                            <input type="hidden" name="vacinas_aplicadas[<?= $vacina_nome_safe ?>][nome]" value="<?= $vacina_nome_safe ?>">
                                                            <div class="row">
                                                                <div class="col-sm-4 mb-2">
                                                                    <label class="form-label">Dose:</label>
                                                                    <input type="text" class="form-control form-control-sm" 
                                                                           name="vacinas_aplicadas[<?= $vacina_nome_safe ?>][dose]" 
                                                                           value="<?= $vacina_dose_safe ?>" required>
                                                                    </div>
                                                                <div class="col-sm-4 mb-2">
                                                                    <label class="form-label">Marca:</label>
                                                                    <input type="text" class="form-control form-control-sm" name="vacinas_aplicadas[<?= $vacina_nome_safe ?>][marca]" placeholder="Ex: Pfizer" required>
                                                                </div>
                                                                <div class="col-sm-4 mb-2">
                                                                    <label class="form-label">Lote:</label>
                                                                    <input type="text" class="form-control form-control-sm" name="vacinas_aplicadas[<?= $vacina_nome_safe ?>][lote]" placeholder="Ex: AB1234" required>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                                
                                                <?php if (!empty($ag['vacinas'])): ?>
                                                    <div class="d-grid">
                                                        <button type="submit" class="btn btn-success" onclick="return confirm('Confirmar aplicação e registo destas vacinas?');">
                                                            <i class="fas fa-check-circle me-2"></i>Registrar Vacinas Aplicadas
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <p class="text-center text-muted">Todas as vacinas deste agendamento foram removidas.</p>
                                                <?php endif; ?>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-6">
                    <h3><i class="fas fa-moon me-2"></i>Tarde</h3>
                    <div class="accordion" id="accordionTarde">
                        <?php if (empty($lista_tarde)): ?>
                            <p class="text-muted">Nenhum paciente agendado.</p>
                        <?php else: ?>
                            <?php foreach ($lista_tarde as $ag): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading-<?= $ag['id'] ?>">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= $ag['id'] ?>">
                                            <strong><?= htmlspecialchars($ag['nome_paciente']) ?></strong> (<?= $ag['idade'] ?> anos)
                                        </button>
                                    </h2>
                                    <div id="collapse-<?= $ag['id'] ?>" class="accordion-collapse collapse" data-bs-parent="#accordionTarde">
                                        <div class="accordion-body">
                                            <form action="aplicar_vacina.php" method="POST">
                                                <input type="hidden" name="agendamento_id" value="<?= $ag['id'] ?>">
                                                <input type="hidden" name="paciente_id" value="<?= $ag['paciente_id'] ?>">
                                                <input type="hidden" name="nome_paciente" value="<?= htmlspecialchars($ag['nome_paciente']) ?>">
                                                
                                                <h5>Vacinas a Aplicar:</h5>
                                                <?php foreach ($ag['vacinas'] as $vacina): ?>
                                                    <?php $vacina_nome_safe = htmlspecialchars($vacina['nome'] ?? 'Erro'); ?>
                                                    
                                                    <?php $vacina_dose_safe = htmlspecialchars($vacina['dose'] ?? ''); ?>
                                                    <div class="card mb-3">
                                                        <div class="card-body">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <h6 class="card-title mb-0"><?= $vacina_nome_safe ?></h6>
                                                                <a href="remover_item_agendamento.php?item_id=<?= $vacina['id'] ?>&data=<?= $data_selecionada ?>" 
                                                                   class="btn btn-outline-danger btn-sm" 
                                                                   title="Remover (Sem Estoque)"
                                                                   onclick="return confirm('Tem certeza que deseja remover esta vacina do agendamento por falta de estoque? O paciente continuará com ela pendente.');">
                                                                    <i class="fas fa-times"></i>
                                                                </a>
                                                            </div>
                                                            <hr>
                                                            <input type="hidden" name="vacinas_aplicadas[<?= $vacina_nome_safe ?>][nome]" value="<?= $vacina_nome_safe ?>">
                                                            <div class="row">
                                                                <div class="col-sm-4 mb-2">
                                                                    <label class="form-label">Dose:</label>
                                                                    <input type="text" class="form-control form-control-sm" 
                                                                           name="vacinas_aplicadas[<?= $vacina_nome_safe ?>][dose]" 
                                                                           value="<?= $vacina_dose_safe ?>" required>
                                                                    </div>
                                                                <div class="col-sm-4 mb-2">
                                                                    <label class="form-label">Marca:</label>
                                                                    <input type="text" class="form-control form-control-sm" name="vacinas_aplicadas[<?= $vacina_nome_safe ?>][marca]" placeholder="Ex: Pfizer" required>
                                                                </div>
                                                                <div class="col-sm-4 mb-2">
                                                                    <label class="form-label">Lote:</label>
                                                                    <input type="text" class="form-control form-control-sm" name="vacinas_aplicadas[<?= $vacina_nome_safe ?>][lote]" placeholder="Ex: AB1234" required>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                                
                                                <?php if (!empty($ag['vacinas'])): ?>
                                                    <div class="d-grid">
                                                        <button type="submit" class="btn btn-success" onclick="return confirm('Confirmar aplicação e registo destas vacinas?');">
                                                            <i class="fas fa-check-circle me-2"></i>Registrar Vacinas Aplicadas
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <p class="text-center text-muted">Todas as vacinas deste agendamento foram removidas.</p>
                                                <?php endif; ?>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>