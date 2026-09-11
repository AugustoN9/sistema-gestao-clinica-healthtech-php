<?php
// 1. Proteção e Conexão
require_once 'auth.php';
proteger_pagina(['enfermagem', 'gerente', 'admin']); 
require_once 'conexao.php';
require_once 'header.php'; // Inclui o header (e a sessão)

// =================================================================
// LÓGICA DO CALENDÁRIO (Copiada de consultas.php)
// =================================================================
$nomes_meses = ['01'=>'Janeiro', '02'=>'Fevereiro', '03'=>'Março', '04'=>'Abril', '05'=>'Maio', '06'=>'Junho', '07'=>'Julho', '08'=>'Agosto', '09'=>'Setembro', '10'=>'Outubro', '11'=>'Novembro', '12'=>'Dezembro'];
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

// Determina a data a ser usada no SQL (usa a data da URL, ou hoje se nenhuma)
$data_selecionada = $_GET['data'] ?? date('Y-m-d');
$data_selecionada_formatada = date('d/m/Y', strtotime($data_selecionada));

// Busca dias que TÊM consultas agendadas
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
// FIM DA LÓGICA DO CALENDÁRIO
// =================================================================


// 2. Lógica SQL: Achar pacientes na fila de uma data específica
$sql = "SELECT 
            c.id AS consulta_id, 
            c.horario_consulta, 
            p.id AS paciente_id,
            p.nome AS nome_paciente, 
            p.data_nascimento,
            m.nome_completo AS nome_medico
        FROM 
            consultas c
        JOIN 
            pacientes p ON c.paciente_id = p.id
        JOIN 
            medicos m ON c.medico_id = m.id
        LEFT JOIN 
            triagem t ON c.id = t.consulta_id
        WHERE 
            c.data_consulta = ?  /* <--- CORREÇÃO: Filtra pela data da URL */
            AND t.id IS NULL
        ORDER BY 
            c.horario_consulta ASC";

$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, "s", $data_selecionada);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

if (!$resultado) {
    die("Erro ao buscar fila de triagem: " . mysqli_error($conexao));
}

?>

<style>
/* Estilos para o calendário */
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
            <a class="nav-link text-dark" href="area_enfermagem.php">
                <i class="fas fa-syringe me-1"></i> Agendamentos de Vacinação
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="enfermagem_fila_triagem.php">
                <i class="fas fa-clipboard-user me-1"></i> Fila de Triagem (Consultas)
            </a>
        </li>
    </ul>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <p class="lead mb-0">Fila de espera para consultas médicas.</p>
        <h4 class="text-muted"><?= $data_selecionada_formatada ?></h4>
    </div>
    <hr>


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
            <h4 class="mb-3">Pacientes Aguardando Triagem</h4>
            <div class="list-group">
                <?php if ($resultado && mysqli_num_rows($resultado) > 0): ?>
                    <?php while ($consulta = mysqli_fetch_assoc($resultado)): ?>
                        <?php
                        // Cálculo da Idade (existente)
                        $idade = 'N/D';
                        if (!empty($consulta['data_nascimento'])) {
                            $data_nasc = new DateTime($consulta['data_nascimento']);
                            $hoje_dt = new DateTime('now');
                            if ($data_nasc->format('Y') > 0) {
                                $idade = $data_nasc->diff($hoje_dt)->y;
                            }
                        }
                        ?>
                        
                        <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1"><?= date('H:i', strtotime($consulta['horario_consulta'])) ?> - <?= htmlspecialchars($consulta['nome_paciente']) ?></h5>
                                <p class="mb-1">
                                    <small class="text-muted">
                                        Paciente (ID: <?= $consulta['paciente_id'] ?>) | Idade: <?= $idade ?> anos
                                    </small>
                                </p>
                                <small>Consulta com: Dr(a). <?= htmlspecialchars($consulta['nome_medico']) ?></small>
                            </div>
                            
                            <a href="realizar_triagem.php?consulta_id=<?= $consulta['consulta_id'] ?>" class="btn btn-primary">
                                <i class="fas fa-clipboard-user me-1"></i> Iniciar Triagem
                            </a>
                        </div>

                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <h4 class="alert-heading"><i class="fas fa-check-circle"></i> Fila Limpa!</h4>
                        <p class="mb-0">Nenhum paciente aguardando triagem para o dia **<?= $data_selecionada_formatada ?>**.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>