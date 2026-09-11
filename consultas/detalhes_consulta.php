<?php
// ================== PASSO DE SEGURANÇA ==================
require_once '../auth.php';

// Apenas utilizadores de gestão ou o médico responsável podem ver
proteger_pagina(['gerente', 'medico', 'admin']); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$usuario_tipo = $_SESSION['usuario_tipo'] ?? 'default';
$id_referencia_logado = $_SESSION['id_referencia'] ?? 0;
// ================== FIM DO PASSO DE SEGURANÇA ==================

require_once '../conexao.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: consultas.php?status=id_invalido");
    exit();
}
$id = (int)$_GET['id'];

// ================== SQL PARA BUSCAR DADOS DA CONSULTA + PERFIL PACIENTE + TRIAGEM ==================
$sql = "SELECT 
            c.id, c.paciente_id, c.medico_id, c.data_consulta, c.horario_consulta, c.consultorio, c.status, c.especialidade,
            c.motivo AS motivo_consulta, 
            c.observacoes AS observacoes_medicas, /* Alias para o diagnóstico do médico */
            
            p.nome AS nome_paciente, 
            p.cpf AS cpf_paciente, 
            p.tipo_sanguineo,
            p.is_diabetico, p.is_cardiaco, p.is_hipertenso,
            p.alergias_conhecidas,
            p.ultimo_imc_valor, p.ultimo_imc_classificacao,
            
            m.nome_completo AS nome_medico,
            m.crm AS crm_medico,
            
            t.pressao_arterial, t.temperatura_c, t.frequencia_cardiaca,
            t.peso_kg, t.altura_cm, t.imc_calculado, t.nivel_dor,
            t.local_dor, t.observacoes_triagem, t.data_triagem,
            
            u_enf.nome_completo AS nome_enfermagem
            
        FROM consultas AS c
        LEFT JOIN pacientes AS p ON c.paciente_id = p.id
        LEFT JOIN medicos AS m ON c.medico_id = m.id
        LEFT JOIN triagem AS t ON c.id = t.consulta_id 
        LEFT JOIN usuarios AS u_enf ON t.enfermagem_usuario_id = u_enf.id
        WHERE c.id = ?";

$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$consulta = mysqli_fetch_assoc($resultado);

if (!$consulta) {
    header("Location: consultas.php?status=consulta_nao_encontrada");
    exit();
}

// ==============================================================
// LÓGICA DE PERMISSÃO CORRIGIDA: PERMITE FINALIZAR SE FOR 'Agendada' OU 'Aguardando Médico'
// ==============================================================
$url_retorno = ($usuario_tipo == 'medico') ? '../area_medico.php' : 'consultas.php'; 
$pode_finalizar = ($usuario_tipo == 'medico' && 
                   $consulta['medico_id'] == $id_referencia_logado && 
                   ($consulta['status'] == 'Agendada' || $consulta['status'] == 'Aguardando Médico'));

require_once '../header.php';
?>

<div class="d-flex justify-content-between align-items-center">
    <h1>Detalhes da Consulta</h1>
    <a href="<?= $url_retorno ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i> Voltar para a Lista
    </a>
</div>
<hr>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            Consulta #<?= htmlspecialchars($consulta['id']) ?> | 
            <?= date('d/m/Y', strtotime($consulta['data_consulta'])) ?> às 
            <?= date('H:i', strtotime($consulta['horario_consulta'])) ?> 
            (Consultório <?= htmlspecialchars($consulta['consultorio']) ?>)
        </h5>
    </div>
    <div class="card-body">
        
        <div class="row mb-4">
            <div class="col-md-6">
                <h6><i class="fas fa-user-injured me-2"></i>Paciente</h6>
                <p>
                    <strong><?= htmlspecialchars($consulta['nome_paciente']) ?></strong><br>
                    CPF: <?= htmlspecialchars($consulta['cpf_paciente']) ?>
                </p>
            </div>
            <div class="col-md-6">
                <h6><i class="fas fa-user-md me-2"></i>Médico</h6>
                <p>
                    <strong><?= htmlspecialchars($consulta['nome_medico']) ?></strong><br>
                    Especialidade: <?= htmlspecialchars($consulta['especialidade']) ?> (CRM: <?= htmlspecialchars($consulta['crm_medico']) ?>)
                </p>
            </div>
        </div>
        <hr>
        
        <div class="mb-4">
            <h6><i class="fas fa-comment-dots me-2"></i>Motivo da Consulta (Paciente/Gerente)</h6>
            <p class="alert alert-light border"><?= nl2br(htmlspecialchars($consulta['motivo_consulta'] ?? 'Nenhum motivo registado.')) ?></p>
        </div>
        
        <div class="card border-info shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-user-tag me-2"></i>Perfil de Saúde do Paciente</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 border-end">
                        <h6>Comorbidades Importantes:</h6>
                        <?php
                            $comorbidades_medico = [];
                            if (($consulta['is_diabetico'] ?? 0) == 1) $comorbidades_medico[] = '<span class="badge bg-danger">Diabético</span>';
                            if (($consulta['is_cardiaco'] ?? 0) == 1) $comorbidades_medico[] = '<span class="badge bg-danger">Cardíaco</span>';
                            if (($consulta['is_hipertenso'] ?? 0) == 1) $comorbidades_medico[] = '<span class="badge bg-danger">Hipertenso</span>';
                        ?>
                        <p class="mb-2">
                            <?= !empty($comorbidades_medico) ? implode(' ', $comorbidades_medico) : '<span class="text-success small">Nenhuma Crônica Registrada</span>' ?>
                        </p>
                        
                        <h6 class="mt-3">Tipo Sanguíneo:</h6>
                        <p class="mb-1">
                            <span class="badge bg-secondary fs-6"><?= htmlspecialchars($consulta['tipo_sanguineo'] ?? 'N/D') ?></span>
                        </p>
                        
                    </div>
                    <div class="col-md-6">
                        <h6>Outras Condições:</h6>
                        <p class="mb-1">
                            <?php if (!empty($consulta['alergias_conhecidas'])): ?>
                                <span class="badge bg-danger me-1">Alérgico</span>
                            <?php endif; ?>
                        </p>
                        <p class="small text-muted mb-0">
                            <strong>Detalhes:</strong> <?= !empty($consulta['alergias_conhecidas']) ? htmlspecialchars($consulta['alergias_conhecidas']) : 'Alergias não informadas.' ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php if (!empty($consulta['data_triagem'])): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Dados da Triagem</h6>
            </div>
            <div class="card-body">
                <p class="small text-muted">Triagem completa por <strong><?= htmlspecialchars($consulta['nome_enfermagem'] ?? 'N/D') ?></strong> em <?= date('d/m/Y', strtotime($consulta['data_triagem'])) ?>.</p>

                <div class="row mb-3 border-bottom pb-3">
                    <div class="col-md-4"><strong>PA:</strong> <?= htmlspecialchars($consulta['pressao_arterial'] ?? 'N/D') ?></div>
                    <div class="col-md-4"><strong>Temp:</strong> <?= htmlspecialchars($consulta['temperatura_c'] ?? 'N/D') ?> °C</div>
                    <div class="col-md-4"><strong>FC:</strong> <?= htmlspecialchars($consulta['frequencia_cardiaca'] ?? 'N/D') ?> bpm</div>
                </div>
                
                <div class="row mb-3 border-bottom pb-3">
                    <div class="col-md-4"><strong>Peso:</strong> <?= htmlspecialchars($consulta['peso_kg'] ?? 'N/D') ?> kg</div>
                    <div class="col-md-4"><strong>Altura:</strong> <?= htmlspecialchars($consulta['altura_cm'] ?? 'N/D') ?> cm</div>
                    <div class="col-md-4">
                        <strong>IMC Triagem:</strong> <?= htmlspecialchars($consulta['imc_calculado'] ?? 'N/D') ?>
                        <?php if ($consulta['ultimo_imc_classificacao']): ?>
                            <span class="badge bg-primary ms-2"><?= htmlspecialchars($consulta['ultimo_imc_classificacao']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4"><strong>Dor (0-10):</strong> <?= htmlspecialchars($consulta['nivel_dor'] ?? '0') ?></div>
                    <div class="col-md-8">
                        <strong>Local da Dor:</strong> <?= htmlspecialchars($consulta['local_dor'] ?? 'Não informado') ?>
                    </div>
                </div>

                <div class="alert alert-light border mt-3 mb-0">
                    <strong>Observações da Enfermagem:</strong><br>
                    <?= nl2br(htmlspecialchars($consulta['observacoes_triagem'] ?? 'Nenhuma observação registada.')) ?>
                </div>

            </div>
        </div>
        <?php else: ?>
            <div class="alert alert-info">Paciente ainda não passou pela triagem.</div>
        <?php endif; ?>
        <hr>

        <h4 class="mb-3"><i class="fas fa-notes-medical me-2"></i> Diagnóstico / Observações Médicas</h4>

        <?php 
            $texto_observacoes = htmlspecialchars($consulta['observacoes_medicas'] ?? '');
            $is_readonly = !$pode_finalizar;
        ?>

        <?php if ($pode_finalizar): ?> 
            <form action="finalizar_consulta.php" method="POST" id="formFinalizar">
                <input type="hidden" name="consulta_id" value="<?= $consulta['id'] ?>">
        <?php endif; ?>

            <div class="mb-3">
                <label for="observacoes_medicas_input" class="form-label">Diagnóstico e Observações Finais:</label>
                
                <?php if (!$is_readonly): ?>
                    <textarea class="form-control" id="observacoes_medicas_input" name="observacoes_medicas" rows="5" required><?= $texto_observacoes ?></textarea>
                <?php else: ?>
                    <div class="alert alert-secondary p-3 border">
                        <?= nl2br($texto_observacoes ?? 'Aguardando o diagnóstico final do médico.') ?>
                    </div>
                <?php endif; ?>

            </div>

        <?php if ($pode_finalizar): ?> 
            
            <div class="mb-4">
                <button type="button" class="btn btn-outline-success" onclick="abrirSolicitacaoExames(<?= $consulta['id'] ?>)">
                    <i class="fas fa-vial me-1"></i> Solicitar Exames
                </button>
                <small class="text-muted ms-3">Clique para abrir a lista de exames em uma nova janela.</small>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mt-4">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-check-circle me-2"></i> Salvar e Finalizar Consulta
                </button>
                
                <div>
                    <button type="button" class="btn btn-outline-info me-2" onclick="window.open('gerar_atestado.php?id=<?= $consulta['id'] ?>', '_blank')">
                        <i class="fas fa-file-pdf me-1"></i> Gerar Atestado
                    </button>
                    <button type="button" class="btn btn-outline-primary" onclick="window.open('gerar_receita.php?id=<?= $consulta['id'] ?>', '_blank')">
                        <i class="fas fa-file-prescription me-1"></i> Gerar Receita
                    </button>
                </div>
            </div>
            </form>
        <?php else: ?>
            <?php if ($consulta['status'] == 'Finalizada'): ?>
                  <h5 class="mt-4"><i class="fas fa-print me-2"></i>Documentos Clínicos</h5>
                  <div class="text-end">
                    <button type="button" class="btn btn-outline-info me-2" onclick="window.open('gerar_atestado.php?id=<?= $consulta['id'] ?>', '_blank')">
                         <i class="fas fa-file-pdf me-1"></i> Gerar Atestado
                    </button>
                    <button type="button" class="btn btn-outline-primary" onclick="window.open('gerar_receita.php?id=<?= $consulta['id'] ?>', '_blank')">
                        <i class="fas fa-file-prescription me-1"></i> Gerar Receita
                    </button>
                  </div>
             <?php endif; ?>
        <?php endif; ?>

    </div>
    
    <?php if (isset($_SESSION['usuario_tipo']) && in_array($_SESSION['usuario_tipo'], ['gerente', 'admin'])):  ?>
    <div class="card-footer text-end">
        <a href="editar_consulta.php?id=<?= $consulta['id'] ?>" class="btn btn-warning"><i class="fas fa-pencil"></i> Editar Consulta</a>
        <a href="excluir_consulta.php?id=<?= $consulta['id'] ?>" class="btn btn-danger" onclick="return confirm('Tem a certeza que deseja excluir esta consulta?');"><i class="fas fa-trash"></i> Excluir Consulta</a>
    </div>
    <?php endif; ?>
</div>

<script>
// === NOVA FUNÇÃO: ABRE O FORMULÁRIO DE SOLICITAÇÃO DE EXAMES ===
function abrirSolicitacaoExames(consultaId) {
    // A nova página que conterá a lista de exames
    window.open('solicitar_exames_form.php?consulta_id=' + consultaId, '_blank');
}

// Funções stub (A serem implementadas com scripts PHP de geração de PDF)
function gerarAtestado(consultaId) {
    window.open('gerar_atestado.php?id=' + consultaId, '_blank');
}
function gerarReceita(consultaId) {
    window.open('gerar_receita.php?id=' + consultaId, '_blank');
}
</script>

<?php require_once '../footer.php'; ?>