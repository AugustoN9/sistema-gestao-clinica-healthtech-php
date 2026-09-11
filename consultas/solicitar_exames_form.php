<?php
// 1. Inclusões e Proteção
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../auth.php';
proteger_pagina(['medico']); // Apenas médicos podem solicitar exames
require_once '../conexao.php';

$consulta_id = $_GET['consulta_id'] ?? null;
$medico_id_logado = $_SESSION['id_referencia'] ?? 0;

if (!$consulta_id || !is_numeric($consulta_id)) {
    die("ID da consulta inválido.");
}

// 2. Busca dados da consulta e do paciente (para o cabeçalho e formulário)
$sql_info = "SELECT p.id AS paciente_id, p.nome AS nome_paciente, m.nome_completo AS nome_medico, m.crm AS crm_medico
             FROM consultas c
             JOIN pacientes p ON c.paciente_id = p.id
             JOIN medicos m ON c.medico_id = m.id
             WHERE c.id = ? AND c.medico_id = ?"; // Filtra por ID da consulta e pelo médico logado
$stmt_info = mysqli_prepare($conexao, $sql_info);
mysqli_stmt_bind_param($stmt_info, "ii", $consulta_id, $medico_id_logado);
mysqli_stmt_execute($stmt_info);
$dados_consulta = mysqli_stmt_get_result($stmt_info)->fetch_assoc();

if (!$dados_consulta) {
    die("Acesso negado ou consulta não encontrada.");
}

// 3. Busca Todos os Exames Ativos no Catálogo Mestre (com seus vínculos de laboratório)
$sql_exames = "SELECT 
                    e.id AS exame_id, 
                    e.nome_exame, 
                    e.grupo_principal, 
                    e.tipo_documento,
                    el.laboratorio_id,
                    el.codigo_exame_lab,
                    lp.nome_empresa AS nome_laboratorio
               FROM 
                    exames e
               JOIN 
                    exames_laboratorios el ON e.id = el.exame_id
               JOIN
                    laboratorios_parceiros lp ON el.laboratorio_id = lp.id
               ORDER BY 
                    e.tipo_documento, e.grupo_principal, e.nome_exame";

$resultado_exames = mysqli_query($conexao, $sql_exames);

// 4. Agrupa os Exames e Laboratórios para o Select Box
$exames_agrupados = [];
$laboratorios_list = [];
while ($row = mysqli_fetch_assoc($resultado_exames)) {
    $chave_grupo = $row['tipo_documento'] . ' - ' . $row['grupo_principal'];
    
    if (!isset($exames_agrupados[$chave_grupo])) {
        $exames_agrupados[$chave_grupo] = [];
    }
    
    // Adiciona o laboratório como uma opção para este exame específico
    $exames_agrupados[$chave_grupo][] = $row;
    
    // Constrói uma lista de laboratórios únicos para o seletor final (se houver)
    if (!isset($laboratorios_list[$row['laboratorio_id']])) {
        $laboratorios_list[$row['laboratorio_id']] = [
            'id' => $row['laboratorio_id'],
            'nome' => $row['nome_laboratorio']
        ];
    }
}

// 5. Busca pedidos já feitos para esta consulta (Para marcar os checkboxes)
$pedidos_atuais = [];
$sql_pedidos = "SELECT exame_id FROM pedidos_exames WHERE consulta_id = ?";
$stmt_pedidos = mysqli_prepare($conexao, $sql_pedidos);
mysqli_stmt_bind_param($stmt_pedidos, "i", $consulta_id);
mysqli_stmt_execute($stmt_pedidos);
$resultado_pedidos = mysqli_stmt_get_result($stmt_pedidos);
while ($pedido = mysqli_fetch_assoc($resultado_pedidos)) {
    $pedidos_atuais[$pedido['exame_id']] = true;
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Solicitação de Exames - Consulta #<?= $consulta_id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .document-container { max-width: 900px; margin: 30px auto; padding: 30px; background: white; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        .exam-group-item { border-bottom: 1px dashed #eee; padding-bottom: 10px; margin-bottom: 15px; }
        .exam-item-row { margin-bottom: 10px; }
        .exam-item-row label { cursor: pointer; }
        .lab-select { font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="document-container">
        
        <div class="text-center mb-4">
            <h1 class="text-primary"><i class="fas fa-file-invoice me-2"></i> Solicitação de Exames</h1>
            <p><strong>Médico:</strong> Dr(a). <?= htmlspecialchars($dados_consulta['nome_medico']) ?> (CRM: <?= htmlspecialchars($dados_consulta['crm_medico']) ?>)</p>
            <p><strong>Paciente:</strong> <?= htmlspecialchars($dados_consulta['nome_paciente']) ?></p>
            <hr>
            <div id="status-message" class="alert alert-info d-none"></div>
        </div>

        <form method="POST" action="processa_solicitacao_exame.php" id="formSolicitacaoExames">
            <input type="hidden" name="consulta_id" value="<?= $consulta_id ?>">
            <input type="hidden" name="medico_id" value="<?= $medico_id_logado ?>">
            <input type="hidden" name="paciente_id" value="<?= $dados_consulta['paciente_id'] ?>">
            
            <h4 class="mb-3"><i class="fas fa-list-check me-2"></i> Exames Solicitados</h4>
            <p class="text-muted small">Marque o exame e selecione o Laboratório de destino. Apenas exames vinculados aparecem aqui.</p>

            <div class="row">
                <?php $i = 0; foreach ($exames_agrupados as $chave_grupo => $exames_do_grupo): $i++; ?>
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-light">
                                <h6 class="fw-bold text-secondary mb-0"><?= $chave_grupo ?></h6>
                            </div>
                            <div class="card-body p-3">
                                
                                <?php foreach ($exames_do_grupo as $exame): 
                                    $exame_id = $exame['exame_id'];
                                    $is_solicitado = isset($pedidos_atuais[$exame_id]);
                                    $nome_campo_select = "lab_select_{$exame_id}";
                                ?>
                                
                                <div class="exam-item-row border-bottom pb-2 pt-1 row align-items-center">
                                    
                                    <div class="col-md-7">
                                        <div class="form-check">
                                            <input class="form-check-input check-exame" type="checkbox" 
                                                   name="exames[<?= $exame_id ?>]" 
                                                   value="1" 
                                                   id="exame_<?= $exame_id ?>" 
                                                   <?= $is_solicitado ? 'checked' : '' ?>>
                                            <label class="form-check-label small" for="exame_<?= $exame_id ?>">
                                                <?= htmlspecialchars($exame['nome_exame']) ?>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-5">
                                        <select class="form-select lab-select" 
                                                id="<?= $nome_campo_select ?>" 
                                                name="<?= $nome_campo_select ?>"> <option value="">Selecione o Laboratório</option>
                                            
                                            <?php 
                                            // Filtra e agrupa as opções de laboratório DISPONÍVEIS para ESTE exame
                                            $laboratorios_para_este_exame = [];
                                            
                                            foreach ($exames_do_grupo as $item) {
                                                if ($item['exame_id'] == $exame_id) {
                                                    $laboratorios_para_este_exame[$item['laboratorio_id']] = $item['nome_laboratorio'];
                                                }
                                            }
                                            
                                            // Itera sobre as opções disponíveis
                                            foreach ($laboratorios_para_este_exame as $lab_id => $lab_nome):
                                            ?>
                                                <option value="<?= $lab_id ?>">
                                                    <?= htmlspecialchars($lab_nome) ?>
                                                </option>
                                            <?php endforeach; ?>
                                            
                                        </select>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <h4 class="mt-4"><i class="fas fa-comment-medical me-2"></i> Instruções Adicionais</h4>
            <div class="mb-4">
                <textarea class="form-control" name="instrucoes_adicionais" rows="3" placeholder="Ex: Paciente em jejum de 12 horas, coletar amostra até às 10h..."></textarea>
            </div>


            <div class="document-footer text-center mt-4">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-paper-plane me-1"></i> Gerar Solicitação e Salvar
                </button>
                <a href="detalhes_consulta.php?id=<?= $consulta_id ?>" class="btn btn-secondary ms-3">
                    <i class="fas fa-times me-1"></i> Cancelar e Voltar
                </a>
            </div>
        </form>

    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JS para garantir que o SELECT seja obrigatório se o CHECKBOX estiver marcado
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('formSolicitacaoExames');

            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                // Itera sobre todos os checkboxes marcados
                document.querySelectorAll('.check-exame:checked').forEach(checkbox => {
                    const exameId = checkbox.id.split('_')[1];
                    const labSelect = document.getElementById(`lab_select_${exameId}`);
                    
                    if (!labSelect.value) {
                        labSelect.classList.add('is-invalid');
                        isValid = false;
                    } else {
                        labSelect.classList.remove('is-invalid');
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    // Altera a cor e exibe a mensagem de erro
                    const statusMessage = document.getElementById('status-message');
                    statusMessage.classList.remove('d-none', 'alert-info');
                    statusMessage.classList.add('alert-danger');
                    statusMessage.textContent = 'Erro: Selecione um Laboratório para cada exame marcado.';
                }
            });
            
            // Opcional: Adicionar um listener para limpar a validação visual (is-invalid) ao mudar o select
            document.querySelectorAll('.lab-select').forEach(select => {
                select.addEventListener('change', function() {
                    if (this.value) {
                        this.classList.remove('is-invalid');
                    }
                });
            });
            
        });
    </script>
</body>
</html>