<?php
// 1. Proteção e Conexão
require_once 'auth.php';
proteger_pagina(['enfermagem', 'gerente', 'admin']); 
require_once 'conexao.php';

// 2. Validar a Consulta
if (!isset($_GET['consulta_id']) || !is_numeric($_GET['consulta_id'])) {
    header("Location: enfermagem_fila_triagem.php?erro=" . urlencode("ID da consulta inválido."));
    exit();
}
$consulta_id = (int)$_GET['consulta_id'];

// 3. Buscar dados da Consulta e do Paciente (Adicionado campos de responsável para menores)
$sql = "SELECT 
            c.id AS consulta_id, 
            c.horario_consulta, 
            p.id AS paciente_id,
            p.nome AS nome_paciente, 
            p.data_nascimento,
            p.responsavel_nome,     
            p.responsavel_parentesco, 
            m.nome_completo AS nome_medico
        FROM 
            consultas c
        JOIN 
            pacientes p ON c.paciente_id = p.id
        JOIN 
            medicos m ON c.medico_id = m.id
        WHERE 
            c.id = ?";

$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, "i", $consulta_id);
mysqli_stmt_execute($stmt);
$consulta = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$consulta) {
    header("Location: enfermagem_fila_triagem.php?erro=" . urlencode("Consulta não encontrada."));
    exit();
}

// 4. Calcular Idade e status de menor
$idade = 'N/D';
$eh_menor_de_12 = false; 
if (!empty($consulta['data_nascimento'])) {
    $data_nasc = new DateTime($consulta['data_nascimento']);
    $hoje_dt = new DateTime('now');
    if ($data_nasc->format('Y') > 0 && $data_nasc->format('Y') < date('Y')) {
        $idade = $data_nasc->diff($hoje_dt)->y;
        if ($idade < 12) {
             $eh_menor_de_12 = true;
        }
    }
}

require_once 'header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-0">Iniciar Triagem</h2>
                            <p class="lead mb-0">Paciente: <strong><?= htmlspecialchars($consulta['nome_paciente']) ?></strong> (<?= $idade ?> anos)</p>
                        </div>
                        <div class="text-end">
                            <h5 class="mb-0"><?= date('H:i', strtotime($consulta['horario_consulta'])) ?></h5>
                            <small class="text-muted">Dr(a). <?= htmlspecialchars($consulta['nome_medico']) ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4>Sinais Vitais e Medidas Antropométricas</h4>
                </div>
                <div class="card-body">
                    <form action="salvar_triagem.php" method="POST" id="formTriagem">
                        <input type="hidden" name="consulta_id" value="<?= $consulta['consulta_id'] ?>">
                        <input type="hidden" name="paciente_id" value="<?= $consulta['paciente_id'] ?>">
                        
                        <?php if ($eh_menor_de_12): ?>
                            <div class="alert alert-warning mb-4 border-2 border-danger">
                                <h5 class="alert-heading"><i class="fas fa-child me-2"></i>Paciente Menor de Idade (<?= $idade ?> anos)</h5>
                                <p class="mb-2">A **presença e identificação do responsável** são obrigatórias.</p>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="responsavel_confirmado" class="form-label">Nome do Responsável Presente:</label>
                                        <input type="text" class="form-control" id="responsavel_confirmado" name="responsavel_nome_triagem" 
                                               placeholder="Nome de quem está acompanhando" 
                                               value="<?= htmlspecialchars($consulta['responsavel_nome'] ?? '') ?>"
                                               required>
                                    </div>
                                     <div class="col-md-6 mb-3">
                                        <label for="responsavel_parentesco" class="form-label">Parentesco/Relação:</label>
                                        <input type="text" class="form-control" id="responsavel_parentesco" name="responsavel_parentesco_triagem" 
                                               placeholder="Ex: Mãe, Pai, Tutor" 
                                               value="<?= htmlspecialchars($consulta['responsavel_parentesco'] ?? '') ?>"
                                               required>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="responsavel_nome_triagem" value="N/A (Maior de idade)">
                            <input type="hidden" name="responsavel_parentesco_triagem" value="N/A">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="pressao_arterial" class="form-label">Pressão Arterial</label>
                                <input type="text" class="form-control" id="pressao_arterial" name="pressao_arterial" placeholder="Ex: 120/80">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="temperatura_c" class="form-label">Temperatura (°C)</label>
                                <input type="number" step="0.1" class="form-control" id="temperatura_c" name="temperatura_c" placeholder="Ex: 36.5">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="frequencia_cardiaca" class="form-label">Freq. Cardíaca (bpm)</label>
                                <input type="number" class="form-control" id="frequencia_cardiaca" name="frequencia_cardiaca" placeholder="Ex: 80">
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="peso_kg" class="form-label">Peso (kg)</label>
                                <input type="number" step="0.1" class="form-control" id="peso_kg" name="peso_kg" placeholder="Ex: 70.5" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="altura_cm" class="form-label">Altura (cm)</label>
                                <input type="number" class="form-control" id="altura_cm" name="altura_cm" placeholder="Ex: 175" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="imc_calculado" class="form-label">IMC (Calculado)</label>
                                <input type="text" class="form-control" id="imc_calculado" name="imc_calculado" readonly disabled placeholder="Auto">
                                <input type="hidden" id="imc_calculado_hidden" name="imc_calculado_hidden">
                                
                                <input type="hidden" id="imc_classificacao_hidden" name="imc_classificacao_hidden">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Classificação</label>
                                <input type="text" class="form-control fw-bold" id="imc_classificacao_display" readonly disabled placeholder="N/D">
                            </div>
                        </div>
                        
                        <hr>

                        <div class="row">
                             <div class="col-md-3 mb-3">
                                <label for="nivel_dor" class="form-label">Nível de Dor (0-10)</label>
                                <input type="number" class="form-control" id="nivel_dor" name="nivel_dor" min="0" max="10" value="0">
                            </div>
                            <div class="col-md-9 mb-3">
                                <label for="local_dor" class="form-label">Local da Dor (se houver)</label>
                                <input type="text" class="form-control" id="local_dor" name="local_dor" placeholder="Ex: Costas, cabeça...">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="observacoes_triagem" class="form-label">Observações da Enfermagem (Triagem)</label>
                            <textarea class="form-control" id="observacoes_triagem" name="observacoes_triagem" rows="3" placeholder="Ex: Paciente relata tosse seca há 2 dias..."></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-4">
                            <a href="enfermagem_fila_triagem.php" class="btn btn-secondary me-2">Cancelar</a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i> Salvar Triagem e Enviar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pesoInput = document.getElementById('peso_kg');
    const alturaInput = document.getElementById('altura_cm');
    const imcDisplay = document.getElementById('imc_calculado');
    const imcHiddenInput = document.getElementById('imc_calculado_hidden');
    const imcClassificacaoDisplay = document.getElementById('imc_classificacao_display');
    const imcClassificacaoHiddenInput = document.getElementById('imc_classificacao_hidden');

    // Funções de Classificação de IMC (Para Adultos - Tabela Padrão OMS)
    function getClassificacaoIMC(imc) {
        if (imc < 18.5) return { text: 'Magreza', class: 'bg-info' };
        if (imc < 25.0) return { text: 'Normal', class: 'bg-success' };
        if (imc < 30.0) return { text: 'Sobrepeso', class: 'bg-warning text-dark' };
        if (imc < 35.0) return { text: 'Obesidade Grau I', class: 'bg-danger' };
        if (imc < 40.0) return { text: 'Obesidade Grau II', class: 'bg-danger' };
        return { text: 'Obesidade Grau III', class: 'bg-dark text-white' };
    }

    function calcularIMC() {
        const peso = parseFloat(pesoInput.value);
        const alturaCm = parseInt(alturaInput.value, 10);
        let classificacao = { text: 'N/D', class: '' };

        if (peso > 0 && alturaCm > 0) {
            const alturaM = alturaCm / 100;
            const imc = peso / (alturaM * alturaM);
            
            classificacao = getClassificacaoIMC(imc);

            // Exibe o valor formatado
            imcDisplay.value = imc.toFixed(2);
            imcHiddenInput.value = imc.toFixed(2);

        } else {
            imcDisplay.value = '';
            imcHiddenInput.value = '';
        }
        
        // Atualiza a Classificação no display e no campo oculto
        imcClassificacaoDisplay.value = classificacao.text;
        // Atualiza a classe de cor no display
        imcClassificacaoDisplay.className = `form-control fw-bold ${classificacao.class} text-center`;
        imcClassificacaoHiddenInput.value = classificacao.text; // Salva o texto para o PHP
    }

    // Adiciona os "ouvintes" para calcular automaticamente
    pesoInput.addEventListener('input', calcularIMC);
    alturaInput.addEventListener('input', calcularIMC);
});
</script>
<?php require_once 'footer.php'; ?>
