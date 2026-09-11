<?php

require_once '../header.php';
require_once '../conexao.php';

// --- ARRAYS EM PORTUGUÊS ---
$nomes_meses = [
    '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril',
    '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto',
    '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
];
$dias_semana_abrev = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

// =================================================================
// LÓGICA DO CALENDÁRIO
// =================================================================
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

$sql_dias_com_agenda = "SELECT DISTINCT DATE(data_consulta) as dia FROM consultas WHERE MONTH(data_consulta) = ? AND YEAR(data_consulta) = ?";
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
// LÓGICA DA LISTA DE CONSULTAS E PAGINAÇÃO
// =================================================================
$data_selecionada = $_GET['data_selecionada'] ?? '';
$parametros_url = [];
$params = [];
$param_types = '';
$where_conditions = [];
$titulo_agenda = '';

if (!empty($data_selecionada)) {
    $where_conditions[] = "consultas.data_consulta = ?";
    $params[] = $data_selecionada;
    $param_types .= "s";
    $parametros_url['data_selecionada'] = $data_selecionada;
    $dia_num = date('d', strtotime($data_selecionada));
    $mes_nome = $nomes_meses[date('m', strtotime($data_selecionada))];
    $titulo_agenda = "Agenda de $dia_num de $mes_nome";
} else {
    $hoje = date('Y-m-d');
    $where_conditions[] = "consultas.data_consulta >= ?";
    $params[] = $hoje;
    $param_types .= "s";
    $titulo_agenda = "Próximas Consultas";
}

$where_clause = " WHERE " . implode(" AND ", $where_conditions);

// MELHORIA DE PAGINAÇÃO (Passo 1: Contar o total de resultados)
$sql_count = "SELECT COUNT(consultas.id) as total FROM consultas" . $where_clause;
$stmt_count = mysqli_prepare($conexao, $sql_count);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt_count, $param_types, ...$params);
}
mysqli_stmt_execute($stmt_count);
$total_resultados = mysqli_stmt_get_result($stmt_count)->fetch_assoc()['total'];
$resultados_por_pagina = 5;
$total_paginas = ceil($total_resultados / $resultados_por_pagina);
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $resultados_por_pagina;

// MELHORIA DE PAGINAÇÃO (Passo 2: Adicionar LIMIT e OFFSET à consulta principal)
$sql = "SELECT consultas.*, pacientes.nome as nome_paciente, medicos.nome_completo as nome_medico 
        FROM consultas 
        JOIN pacientes ON consultas.paciente_id = pacientes.id 
        JOIN medicos ON consultas.medico_id = medicos.id" . $where_clause . " 
        ORDER BY data_consulta ASC, horario_consulta ASC
        LIMIT ?, ?";

$stmt = mysqli_prepare($conexao, $sql);
$all_params = array_merge($params, [$offset, $resultados_por_pagina]);
$all_param_types = $param_types . 'ii'; // Adiciona 'ii' para offset e limit (integers)
mysqli_stmt_bind_param($stmt, $all_param_types, ...$all_params);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
?>

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
                            $classe_dia = in_array($data_completa, $dias_com_agenda) ? 'dia-com-agenda' : '';
                            if (date('Y-m-d') == $data_completa) { $classe_dia .= ' hoje'; }
                            if ($data_selecionada == $data_completa) { $classe_dia .= ' selecionado'; }
                            
                            echo "<td class='$classe_dia'><a href='?data_selecionada=$data_completa'>$dia_atual</a></td>";
                            
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
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3><i class="fas fa-calendar-alt me-2"></i> <?= $titulo_agenda ?></h3>
            <a href="agendar_consulta.php" class="btn btn-primary"><i class="fas fa-plus"></i> Agendar Consulta</a>
        </div>
        <div class="list-group">
            <?php if ($resultado && mysqli_num_rows($resultado) > 0): ?>
                <?php while ($consulta = mysqli_fetch_assoc($resultado)): ?>
                    <a href="detalhes_consulta.php?id=<?= $consulta['id'] ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h4 class="mb-1 nome-paciente"><?= date('H:i', strtotime($consulta['horario_consulta'])) ?> - <?= htmlspecialchars($consulta['nome_paciente']) ?></h4>
                            <strong><?= date('d/m/Y', strtotime($consulta['data_consulta'])) ?></strong>
                        </div>
                        <div class="d-flex w-100 justify-content-between text-muted">
                             <p class="mb-1">Dr(a). <?= htmlspecialchars($consulta['nome_medico']) ?> - <?= htmlspecialchars($consulta['especialidade']) ?></p>
                            <small>Status: <span class="badge bg-info text-dark"><?= htmlspecialchars($consulta['status']) ?></span></small>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info">Nenhuma consulta encontrada.</div>
            <?php endif; ?>
        </div>

        <?php if ($total_paginas > 1): ?>
        <nav aria-label="Navegação de páginas" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <?php
                    $parametros_url['pagina'] = $i;
                    $link = '?' . http_build_query($parametros_url);
                    ?>
                    <li class="page-item <?= ($i == $pagina_atual) ? 'active' : '' ?>">
                        <a class="page-link" href="<?= $link ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>

        <?php if (!empty($data_selecionada)): ?>
             <a href="consultas.php" class="btn btn-secondary mt-3">Ver Todas as Próximas Consultas</a>
        <?php endif; ?>
    </div>
</div>

<style>
/* Muda o destaque do calendário para um retângulo com cantos arredondados */
.calendar .dia-com-agenda a { font-weight: bold; background-color: #e9ecef; border-radius: 0.25rem; display: block; }
.calendar .hoje a { background-color: #0d6efd; color: white !important; border-radius: 0.25rem; }
.calendar .selecionado a { background-color: #198754; color: white !important; border-radius: 0.25rem;}

.calendar td { vertical-align: middle; padding: 2px; }
.calendar a { text-decoration: none; color: inherit; }

/* Diminui a fonte do nome do paciente */
.nome-paciente {
    font-size: 1.15rem; 
}
</style>

<?php require_once '../footer.php'; ?>