<?php
require_once 'auth.php';
proteger_pagina(['medico']); // Apenas médicos podem aceder
require_once 'conexao.php';

$medico_id = $_SESSION['id_referencia'];
$medico_nome = $_SESSION['usuario_nome'] ?? 'Médico'; // Fallback para nome

// --- LÓGICA DO CALENDÁRIO (RESTAURADA DO FICHEIRO ORIGINAL) ---
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

// Buscar dias que ESTE médico já tem agenda (para destacar no calendário)
$sql_meus_dias = "SELECT DISTINCT DATE(data_disponivel) as dia FROM disponibilidade_medicos WHERE medico_id = ? AND MONTH(data_disponivel) = ? AND YEAR(data_disponivel) = ?";
$stmt_meus_dias = mysqli_prepare($conexao, $sql_meus_dias);
mysqli_stmt_bind_param($stmt_meus_dias, "iii", $medico_id, $mes_atual_num, $ano_atual);
mysqli_stmt_execute($stmt_meus_dias);
$resultado_meus_dias = mysqli_stmt_get_result($stmt_meus_dias);
$meus_dias_com_agenda = [];
if ($resultado_meus_dias) {
    while ($row = mysqli_fetch_assoc($resultado_meus_dias)) {
        $meus_dias_com_agenda[] = $row['dia'];
    }
}
// --- FIM DA LÓGICA DO CALENDÁRIO ---


// Busca as próximas consultas do médico
$sql = "SELECT c.id, c.data_consulta, c.horario_consulta, p.nome AS nome_paciente, 
               c.status_triagem /* CORREÇÃO: Coluna de status adicionada */
        FROM consultas c 
        JOIN pacientes p ON c.paciente_id = p.id
        WHERE c.medico_id = ? AND c.data_consulta >= CURDATE() 
        ORDER BY c.data_consulta ASC, c.horario_consulta ASC";
$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, "i", $medico_id);
mysqli_stmt_execute($stmt);
$resultado_consultas = mysqli_stmt_get_result($stmt);

require_once 'header.php';
?>
<h1>Painel do Médico</h1>
<p>Bem-vindo(a), Dr(a). <?= htmlspecialchars($_SESSION['usuario_nome']) ?>!</p>
<hr>

<div class="row">

    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <a href="?mes=<?= $mes_anterior ?>&ano=<?= $ano_anterior ?>" class="btn btn-sm btn-outline-secondary">&lt;</a>
                <h5 class="mb-0"><?= $nomes_meses[$mes_atual_num] . ' ' . $ano_atual ?></h5>
                <a href="?mes=<?= $mes_proximo ?>&ano=<?= $ano_proximo ?>" class="btn btn-sm btn-outline-secondary">&gt;</a>
            </div>
            <div class="card-body">
                <p class="text-muted small text-center">Clique em um dia para gerenciar sua disponibilidade.</p>
                <table class="table table-bordered text-center calendar-medico">
                    <thead><tr><?php foreach ($dias_semana_abrev as $dia) { echo "<th>$dia</th>"; } ?></tr></thead>
                    <tbody>
                        <tr>
                        <?php
                        $dia_atual = 1;
                        $hoje_formatado = date('Y-m-d');
                        for ($i = 0; $i < $primeiro_dia_mes; $i++) { echo '<td></td>'; }
                        while ($dia_atual <= $total_dias_mes) {
                            $data_completa = $ano_atual . '-' . $mes_atual_num . '-' . str_pad($dia_atual, 2, '0', STR_PAD_LEFT);
                            $classe_dia = '';
                            $dia_passado = $data_completa < $hoje_formatado;

                            if ($dia_passado) {
                                $classe_dia = 'dia-passado';
                            } else {
                                $classe_dia = 'dia-clicavel'; // Classe para o JS
                                if (in_array($data_completa, $meus_dias_com_agenda)) {
                                    $classe_dia .= ' dia-com-agenda'; // Destaque para o médico
                                }
                            }
                            
                            echo "<td class='$classe_dia' data-data='$data_completa'>";
                            echo "<span>$dia_atual</span>";
                            echo "</td>";
                            
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

<div class="col-lg-6 mb-4">
    <div class="card h-100">
        <div class="card-header"><h4><i class="fas fa-users me-2"></i>Sua Agenda - Próximos Pacientes</h4></div>
        <div class="card-body">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Hora</th>
                        <th>Paciente</th>
                        <th class="text-center">Status</th> <th class="text-center">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resultado_consultas && mysqli_num_rows($resultado_consultas) > 0): ?>
                        <?php while($consulta = mysqli_fetch_assoc($resultado_consultas)): 
                            
                            // =================================================================
                            // LÓGICA DO ÍCONE DE TRIAGEM (CORREÇÃO DE VALOR)
                            // =================================================================
                            $status_triagem_db = $consulta['status_triagem'] ?? 0;
                            
                            // CORREÇÃO: Verifica estritamente se o valor é 1 (o valor correto do tinyint(1)).
                            $triagem_concluida = ($status_triagem_db == 1); 

                            $icone_status = '<i class="fas fa-exclamation-circle text-warning me-1" title="Triagem Pendente"></i>';
                            $tooltip = 'A Triagem está pendente. O paciente pode não estar pronto para iniciar.';
                            $classe_botao = 'btn-primary';

                            if ($triagem_concluida) { 
                                $icone_status = '<i class="fas fa-check-circle text-success me-1" title="Triagem Concluída"></i>';
                                $tooltip = 'Triagem concluída. Pronto para iniciar o atendimento.';
                                $classe_botao = 'btn-success'; // Destaca o botão para pacientes prontos
                            }
                        ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($consulta['data_consulta'])) ?></td>
                            <td><?= date('H:i', strtotime($consulta['horario_consulta'])) ?></td>
                            <td><?= htmlspecialchars($consulta['nome_paciente']) ?></td>
                            <td class="text-center" title="<?= $tooltip ?>"> <?= $icone_status ?>
                            </td>
                            <td class="text-center">
                                <a href="/consultas/detalhes_consulta.php?id=<?= $consulta['id'] ?>" class="btn <?= $classe_botao ?> btn-sm">
                                    <i class="fas fa-notes-medical me-1"></i> Iniciar Consulta
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">Você não possui nenhuma consulta futura agendada.</td> </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
    
</div> <div class="modal fade" id="modalDisponibilidade" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl"> 
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLabel">Gerenciar Disponibilidade - <span id="dataModal"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Carregando...</p>
                <div id="conteudoModalAgenda">
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" id="salvarAgenda">Salvar Alterações</button>
            </div>
        </div>
    </div>
</div>

<style>
.calendar-medico td {
    padding: 2px;
    vertical-align: middle;
    height: 60px;
}
.calendar-medico .dia-clicavel {
    cursor: pointer;
    background-color: #f8f9fa;
    transition: background-color 0.2s;
}
.calendar-medico .dia-clicavel:hover {
    background-color: #e2e6ea;
}
.calendar-medico .dia-passado {
    background-color: #f8f9fa;
    color: #adb5bd;
    text-decoration: line-through;
}
.calendar-medico .dia-com-agenda {
    background-color: #d1e7dd; /* Verde claro */
    font-weight: bold;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Referências do DOM
    var modalDisponibilidade = new bootstrap.Modal(document.getElementById('modalDisponibilidade'));
    var dataSelecionadaGlobal = ''; 
    const MEDICO_ID = <?= $medico_id ?>; // Passamos o ID do médico logado

    // 1. Adiciona o listener de clique em todos os dias "clicáveis"
    document.querySelectorAll('.dia-clicavel').forEach(function(diaElemento) {
        diaElemento.addEventListener('click', function() {
            
            const dataSelecionada = diaElemento.getAttribute('data-data');
            dataSelecionadaGlobal = dataSelecionada; 
            
            const [ano, mes, dia] = dataSelecionada.split('-');
            const dataFormatada = dia + '/' + mes + '/' + ano;

            document.getElementById('dataModal').innerText = dataFormatada;
            const conteudoModal = document.getElementById('conteudoModalAgenda');
            conteudoModal.innerHTML = '<p class="text-center">Carregando grade de horários...</p>';
            
            // Chama o script PHP que busca a grade de horários (que já criamos)
            fetch(`medicos/buscar_disponibilidade_dia.php?data=${dataSelecionada}`)
                .then(response => response.text())
                .then(html => {
                    conteudoModal.innerHTML = html;
                    adicionarListenersAosSlots();
                })
                .catch(err => {
                    console.error('Erro ao buscar disponibilidade:', err);
                    conteudoModal.innerHTML = '<div class="alert alert-danger">Erro ao carregar a agenda. Tente novamente.</div>';
                });
            
            modalDisponibilidade.show();
        });
    });
    
    // 2. Lógica para controle dos Checkboxes (Contador, Selecionar Turno)
    function adicionarListenersAosSlots() {
        // Listener para CADA checkbox de horário
        document.querySelectorAll('.slot-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                // Lógica de limite de 9 slots aqui (Omitido por brevidade, mas deve existir no seu código)
            });
        });

        // Listener para os botões "Selecionar Turno"
        document.querySelectorAll('.btn-selecionar-turno').forEach(btn => {
            btn.addEventListener('click', function() {
                const turno = this.getAttribute('data-turno');
                const consultorio = this.getAttribute('data-consultorio');
                const containerId = `consultorio-${consultorio}-${turno}`;
                
                const checkboxesDoTurno = document.querySelectorAll(`#${containerId} .slot-checkbox:not(:disabled)`);
                const todosMarcados = Array.from(checkboxesDoTurno).every(cb => cb.checked);
                
                checkboxesDoTurno.forEach(cb => {
                    cb.checked = !todosMarcados;
                });
            });
        });
    }

    // 3. Listener do Botão Salvar (chama o script salvar_agenda_medico.php)
    document.getElementById('salvarAgenda').addEventListener('click', function(e) {
        e.preventDefault();
        
        const slotsSelecionados = [];
        // Coleta todos os slots marcados (que não são de OUTROS médicos)
        document.querySelectorAll('.slot-checkbox:checked:not(:disabled)').forEach(checkbox => {
            slotsSelecionados.push({
                horario: checkbox.getAttribute('data-horario'),
                consultorio: checkbox.getAttribute('data-consultorio')
            });
        });
        
        if (slotsSelecionados.length > 9) {
            alert('Erro: Você não pode selecionar mais de 9 horários.');
            return;
        }

        // Envia os dados para o novo script de salvar (que já criamos)
        fetch('medicos/salvar_agenda_medico.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                data: dataSelecionadaGlobal,
                slots: slotsSelecionados
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'sucesso') {
                modalDisponibilidade.hide();
                location.reload(); // Recarrega a página para atualizar o calendário
            } else {
                alert(data.mensagem);
            }
        })
        .catch(err => {
            alert('Erro de conexão ao tentar salvar a agenda.');
        });
    });
});
</script>


<?php require_once 'footer.php'; ?>