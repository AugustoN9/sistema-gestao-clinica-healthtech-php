<?php
// Inicia a sessão para verificar o login
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../conexao.php';
require_once '../auth.php'; // Inclui a função proteger_pagina

// ===== LÓGICA DE PERMISSÃO E ID DO PACIENTE =====
$usuario_tipo = $_SESSION['usuario_tipo'];
$paciente_id_logado = 0; // 0 se for gerente
$paciente_nome_logado = '';

// Pacientes e Gerentes/Admin podem agendar.
if (!in_array($usuario_tipo, ['paciente', 'gerente', 'admin'])) {
    // Redirecionamento de segurança caso o tipo de usuário não seja permitido
    proteger_pagina(['paciente', 'gerente', 'admin']);
}

if ($usuario_tipo == 'paciente') {
    $paciente_id_logado = (int)$_SESSION['id_referencia'];
    $paciente_nome_logado = $_SESSION['usuario_nome'];
}
// ===== FIM DA LÓGICA DE PERMISSÃO =====

// ===============================================
// ===== LÓGICA DE PRÉ-SELEÇÃO VIA URL (mantida para outros fluxos) =====
$especialidade_pre_selecionada = $_GET['especialidade'] ?? '';
$medico_id_pre_selecionado = (int)($_GET['medico_id'] ?? 0);
// ===============================================

// ===============================================
// ===== NOVO: LÓGICA DE PRÉ-SELEÇÃO POR PERFIL (OBSTETRA) =====
if ($usuario_tipo == 'paciente') {
    
    // 1. Buscar dados de pré-natal do paciente
    $sql_paciente_info = "SELECT medico_prenatal_id, is_gestante FROM pacientes WHERE id = ?";
    $stmt_paciente_info = mysqli_prepare($conexao, $sql_paciente_info);
    mysqli_stmt_bind_param($stmt_paciente_info, "i", $paciente_id_logado);
    mysqli_stmt_execute($stmt_paciente_info);
    $paciente_info = mysqli_stmt_get_result($stmt_paciente_info)->fetch_assoc();
    mysqli_stmt_close($stmt_paciente_info);
    
    $medico_id_prenatal = $paciente_info['medico_prenatal_id'] ?? 0;
    $is_gestante = $paciente_info['is_gestante'] ?? 0;
    
    // 2. Se for gestante E tiver médico registrado (Override)
    if ($is_gestante == 1 && $medico_id_prenatal > 0) {
        
        // 3. Buscar a especialidade do médico registrado
        $sql_especialidade = "SELECT especialidade FROM medicos WHERE id = ?";
        $stmt_esp = mysqli_prepare($conexao, $sql_especialidade);
        mysqli_stmt_bind_param($stmt_esp, "i", $medico_id_prenatal);
        mysqli_stmt_execute($stmt_esp);
        $resultado_esp = mysqli_stmt_get_result($stmt_esp)->fetch_assoc();
        mysqli_stmt_close($stmt_esp);
        
        $especialidade_prenatal = $resultado_esp['especialidade'] ?? 'Ginecologia e Obstetrícia'; 
        
        // 4. SOBRESCREVER AS VARIÁVEIS DE PRÉ-SELEÇÃO
        $especialidade_pre_selecionada = $especialidade_prenatal;
        $medico_id_pre_selecionado = $medico_id_prenatal;
    }
}
// ===== FIM DA LÓGICA DE PRÉ-SELEÇÃO POR PERFIL =====


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Pega o ID do paciente (do form se for gerente/admin, da sessão se for paciente)
    $paciente_id = ($usuario_tipo == 'paciente') ? $paciente_id_logado : (int)$_POST['paciente_id'];
    
    $medico_id = (int)($_POST['medico_id'] ?? 0);
    $especialidade = mysqli_real_escape_string($conexao, $_POST['especialidade'] ?? '');
    $motivo = mysqli_real_escape_string($conexao, $_POST['motivo'] ?? ''); 
    $observacoes = mysqli_real_escape_string($conexao, $_POST['observacoes'] ?? ''); 
    $slot_selecionado = $_POST['slot_selecionado'] ?? '';
    
    // Validação básica
    if ($paciente_id == 0 || $medico_id == 0 || empty($slot_selecionado)) {
        $erro_msg = "Erro: Por favor, preencha todos os campos obrigatórios (Paciente, Médico e Horário).";
        header('Location: agendar_consulta.php?erro=' . urlencode($erro_msg));
        exit();
    }
    
    $partes_slot = explode('|', $slot_selecionado);
    
    // -------------------------------------------------------------
    // VALIDAÇÃO E EXTRAÇÃO DO SLOT
    // -------------------------------------------------------------
    if (count($partes_slot) != 3) {
        $erro_msg = "Erro: O horário selecionado é inválido (Formato esperado: DATA|HORA|CONSULTÓRIO).";
        header('Location: agendar_consulta.php?erro=' . urlencode($erro_msg));
        exit();
    }
    
    $data_consulta = $partes_slot[0];
    $horario_consulta = $partes_slot[1]; // A HORA (ex: '08:00:00')
    $consultorio = (int)$partes_slot[2];

    // Outra validação
    if (empty($horario_consulta)) {
         $erro_msg = "Erro: O horário da consulta não foi especificado corretamente.";
        header('Location: agendar_consulta.php?erro=' . urlencode($erro_msg));
        exit();
    }

    // -------------------------------------------------------------
    // INICIA A TRANSAÇÃO: Agendamento Seguro (Garante Consistência)
    // -------------------------------------------------------------
    mysqli_begin_transaction($conexao);
    
    try {
        // A. Insere a Consulta
        $sql_insert_consulta = "INSERT INTO consultas 
                                (paciente_id, medico_id, especialidade, data_consulta, horario_consulta, motivo, observacoes, consultorio, status) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Agendada')";
        $stmt_insert = mysqli_prepare($conexao, $sql_insert_consulta);
        
        // TIPOS CORRIGIDOS (iisssssi): 8 caracteres para 8 variáveis
        mysqli_stmt_bind_param($stmt_insert, "iisssssi", 
                                $paciente_id, 
                                $medico_id, 
                                $especialidade, 
                                $data_consulta, 
                                $horario_consulta, 
                                $motivo, 
                                $observacoes,
                                $consultorio);
        
        if (!mysqli_stmt_execute($stmt_insert)) {
            // Verifica se o erro foi de slot já ocupado (Chave Única)
             if (mysqli_errno($conexao) == 1062) {
                 throw new Exception("Erro de agendamento: O horário selecionado já foi ocupado por outro paciente. Por favor, escolha outro slot.");
            }
            throw new Exception("Erro ao inserir a consulta: " . mysqli_error($conexao));
        }
        
        // B. Remove o Slot da Tabela de Disponibilidade
        $sql_delete_dispo = "DELETE FROM disponibilidade_medicos 
                             WHERE medico_id = ? AND data_disponivel = ? AND horario_disponivel = ? AND consultorio = ? LIMIT 1";
        $stmt_delete = mysqli_prepare($conexao, $sql_delete_dispo);
        mysqli_stmt_bind_param($stmt_delete, "issi", $medico_id, $data_consulta, $horario_consulta, $consultorio);
        
        if (!mysqli_stmt_execute($stmt_delete) || mysqli_stmt_affected_rows($stmt_delete) === 0) {
            // Se o delete falhar (0 linhas afetadas), significa que o slot já foi ocupado ou removido.
             throw new Exception("Erro de concorrência: O horário selecionado não estava mais disponível. Por favor, tente novamente.");
        }
        
        // Confirma as duas operações
        mysqli_commit($conexao);

        // Redireciona com sucesso
        $link_sucesso = ($usuario_tipo == 'paciente') ? '../area_paciente.php' : 'agendar_consulta.php';
        header('Location: ' . $link_sucesso . '?sucesso=' . urlencode('Consulta agendada com sucesso para ' . date('d/m/Y', strtotime($data_consulta)) . ' às ' . date('H:i', strtotime($horario_consulta)) . '.'));
        exit();
        
    } catch (Exception $e) {
        mysqli_rollback($conexao);
        
        $erro_msg = "Falha no Agendamento: " . $e->getMessage();
        header('Location: agendar_consulta.php?erro=' . urlencode($erro_msg));
        exit();
    }
}
// -------------------------------------------------------------
// FIM DO PROCESSAMENTO PHP / INÍCIO DO HTML / FORMULÁRIO
// -------------------------------------------------------------

require_once '../header.php';

// Busca todas as especialidades distintas (para o primeiro select)
$sql_especialidades = "SELECT DISTINCT especialidade FROM medicos ORDER BY especialidade ASC";
$resultado_especialidades = mysqli_query($conexao, $sql_especialidades);

// Se for gerente, busca a lista de pacientes (para o primeiro select)
$pacientes = [];
if ($usuario_tipo != 'paciente') {
    $sql_pacientes = "SELECT id, nome FROM pacientes ORDER BY nome ASC";
    $resultado_pacientes = mysqli_query($conexao, $sql_pacientes);
    while ($p = mysqli_fetch_assoc($resultado_pacientes)) {
        $pacientes[] = $p;
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1>Agendar Nova Consulta</h1>
        <p class="lead">Preencha os passos abaixo para marcar um horário.</p>

        <?php if (isset($_GET['erro'])): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($_GET['erro']) ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['status'])): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_GET['status']) ?>
            </div>
        <?php endif; ?>

        <form action="agendar_consulta.php" method="POST">
            
            <?php if ($usuario_tipo != 'paciente'): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>Paciente</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="paciente_id" class="form-label">Selecionar Paciente:</label>
                        <select class="form-select" id="paciente_id" name="paciente_id" required>
                            <option value="">Selecione o paciente...</option>
                            <?php foreach ($pacientes as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <?php else: // Paciente Logado ?>
            <div class="alert alert-info" role="alert">
                <i class="fas fa-id-card me-2"></i>Agendando como <strong><?= htmlspecialchars($paciente_nome_logado) ?></strong>.
            </div>
            <input type="hidden" name="paciente_id" value="<?= $paciente_id_logado ?>">
            <?php endif; ?>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-stethoscope me-2"></i>Especialidade e Médico</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="especialidade" class="form-label">Especialidade:</label>
                            <select class="form-select" id="especialidade" name="especialidade" required>
                                <option value="">Selecione a especialidade...</option>
                                <?php while ($row = mysqli_fetch_assoc($resultado_especialidades)): ?>
                                    <?php 
                                    $especialidade_valor = htmlspecialchars($row['especialidade']);
                                    // NOVO: Pré-seleção da Especialidade
                                    $selecionado = ($especialidade_valor == $especialidade_pre_selecionada) ? 'selected' : '';
                                    ?>
                                    <option value="<?= $especialidade_valor ?>" <?= $selecionado ?>><?= $especialidade_valor ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="medico_id" class="form-label">Médico:</label>
                            <select class="form-select" id="medico_id" name="medico_id" disabled required>
                                <option value="">Selecione a especialidade primeiro.</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Data e Horário</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="slot_selecionado" class="form-label">Horário Disponível:</label>
                        <select class="form-select" id="slot_selecionado" name="slot_selecionado" disabled required>
                            <option value="">Selecione o médico primeiro.</option>
                        </select>
                        <div class="form-text text-danger" id="loading_horarios"></div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-notes-medical me-2"></i>Detalhes da Consulta</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="motivo" class="form-label">Motivo da Consulta (Breve resumo):</label>
                        <input type="text" class="form-control" id="motivo" name="motivo" maxlength="150" required>
                    </div>
                    <div class="mb-3">
                        <label for="observacoes" class="form-label">Observações Adicionais (Opcional):</label>
                        <textarea type="text" class="form-control" id="observacoes" name="observacoes" rows="2" maxlength="300"></textarea>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2 mt-4"> 
                <button type="submit" class="btn btn-success btn-lg" id="btn_agendar">
                    <i class="fas fa-calendar-check me-2"></i>Confirmar Agendamento
                </button>
                <?php 
                // Define a URL de retorno: Lista de consultas para Gerente/Admin, Painel do Paciente para Paciente
                $url_cancelar = ($usuario_tipo == 'paciente') ? '../area_paciente.php' : 'consultas.php';
                ?>
                <a href="<?= $url_cancelar ?>" class="btn btn-outline-secondary btn-lg mt-2">
                    <i class="fas fa-times me-2"></i> Cancelar e Voltar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const especialidadeSelect = document.getElementById('especialidade');
    const medicoSelect = document.getElementById('medico_id');
    const slotSelect = document.getElementById('slot_selecionado');
    const loadingDiv = document.getElementById('loading_horarios');

    // Valores pré-selecionados do PHP
    const especialidadePreSelecionada = '<?= $especialidade_pre_selecionada ?>';
    const medicoIdPreSelecionado = <?= $medico_id_pre_selecionado ?>;

    // ----------------------------------------------------
    // PASSO 1: Mudar Especialidade -> Carregar Médicos
    // ----------------------------------------------------
    function carregarMedicos(especialidade, medicoIdParaSelecionar = 0) {
        medicoSelect.innerHTML = '<option value="">Carregando médicos...</option>';
        medicoSelect.disabled = true;
        slotSelect.innerHTML = '<option value="">Selecione o médico primeiro.</option>';
        slotSelect.disabled = true;
        
        if (!especialidade) {
            medicoSelect.innerHTML = '<option value="">Selecione a especialidade primeiro.</option>';
            return;
        }

        // Caminho para buscar_medicos.php (assumindo que está no mesmo diretório)
        fetch('buscar_medicos.php?especialidade=' + encodeURIComponent(especialidade))
            .then(response => response.json())
            .then(medicos => {
                medicoSelect.innerHTML = '<option value="">Selecione o médico...</option>';
                if (medicos.length > 0) {
                    medicos.forEach(medico => {
                        const option = document.createElement('option');
                        option.value = medico.id;
                        option.textContent = medico.nome_completo;
                        if (medico.id == medicoIdParaSelecionar) { // Verifica a pré-seleção
                            option.selected = true;
                        }
                        medicoSelect.appendChild(option);
                    });
                    medicoSelect.disabled = false;

                    // Se pré-selecionamos um médico, disparamos o carregamento dos slots
                    if (medicoIdParaSelecionar > 0) {
                        // Dispara o evento change para carregar os slots (Passo 2)
                        medicoSelect.dispatchEvent(new Event('change')); 
                    }
                } else {
                    medicoSelect.innerHTML = '<option value="">Nenhum médico encontrado nesta especialidade.</option>';
                }
            })
            .catch(err => {
                console.error('Erro ao carregar médicos:', err);
                medicoSelect.innerHTML = '<option value="">Erro ao carregar médicos.</option>';
            });
    }

    // Listener para mudança manual da Especialidade
    especialidadeSelect.addEventListener('change', function() {
        carregarMedicos(this.value); 
    });

    // ----------------------------------------------------
    // PASSO 2: Mudar Médico -> Carregar Slots Disponíveis
    // ----------------------------------------------------
    medicoSelect.addEventListener('change', function() {
        const medicoId = this.value;
        const especialidade = especialidadeSelect.value;
        slotSelect.innerHTML = '<option value="">Carregando horários...</option>';
        slotSelect.disabled = true;
        loadingDiv.textContent = 'Buscando horários disponíveis...';

        if (!medicoId) {
            slotSelect.innerHTML = '<option value="">Selecione o médico primeiro.</option>';
            loadingDiv.textContent = '';
            return;
        }

        // Caminho para buscar horários (assumindo que está no mesmo diretório)
        fetch(`buscar_horarios_disponiveis.php?medico_id=${medicoId}&especialidade=${encodeURIComponent(especialidade)}`)
            .then(response => response.json())
            .then(data => {
                loadingDiv.textContent = '';
                slotSelect.innerHTML = '<option value="">Selecione o dia e horário...</option>';

                if (data.status === 'ok' && Object.keys(data.horarios_disponiveis).length > 0) {
                    
                    for (const dataAgrupada in data.horarios_disponiveis) {
                        
                        const partes = dataAgrupada.split('-');
                        const dataFormatada = `${partes[2]}/${partes[1]}/${partes[0]}`;
                        
                        const optgroup = document.createElement('optgroup');
                        optgroup.label = `Dia: ${dataFormatada}`;
                        
                        data.horarios_disponiveis[dataAgrupada].forEach(slot => {
                            const option = document.createElement('option');
                            // FORMATO CRÍTICO: DATA|HORA|CONSULTÓRIO
                            option.value = `${dataAgrupada}|${slot.horario}|${slot.consultorio}`; 
                            option.textContent = `Horário: ${slot.horario.substring(0, 5)} (Consultório ${slot.consultorio})`;
                            optgroup.appendChild(option);
                        });
                        slotSelect.appendChild(optgroup);
                    }
                    slotSelect.disabled = false;
                
                } else if (data.status === 'ok') {
                     slotSelect.innerHTML = '<option value="">Nenhum horário disponível encontrado para este médico.</option>';
                     slotSelect.disabled = true;
                } else {
                    // Mensagem de erro ao buscar horários
                    slotSelect.innerHTML = `<option value="">Erro ao buscar horários: ${data.mensagem || 'Erro desconhecido'}</option>`;
                    slotSelect.disabled = true;
                }
            })
            .catch(err => {
                console.error('Erro no fetch de horários:', err);
                loadingDiv.textContent = 'Erro de conexão ao buscar horários.';
                slotSelect.innerHTML = '<option value="">Erro de conexão.</option>';
                slotSelect.disabled = true;
            });
    });
    
    // ----------------------------------------------------
    // PASSO 3: INICIALIZAÇÃO COM PRÉ-SELEÇÃO (Executa a lógica)
    // ----------------------------------------------------
    if (especialidadePreSelecionada) {
        // 1. Define o valor da especialidade no SELECT (que foi pré-selecionado no PHP)
        especialidadeSelect.value = especialidadePreSelecionada; 
        
        // 2. Dispara o carregamento dos médicos, passando o ID do médico para pré-selecionar
        carregarMedicos(especialidadePreSelecionada, medicoIdPreSelecionado);
    }
});
</script>

<?php
require_once '../footer.php';
?>