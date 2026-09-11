<?php
require_once '../conexao.php';

// (É altamente recomendado adicionar a proteção de página aqui)
// require_once '../auth.php';
// proteger_pagina(['gerente']);

$erro = null;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = mysqli_real_escape_string($conexao, $_POST['nome']);
    $cpf = mysqli_real_escape_string($conexao, $_POST['cpf']);
    $data_nascimento = mysqli_real_escape_string($conexao, $_POST['data_nascimento']);
    $email = mysqli_real_escape_string($conexao, $_POST['email']);
    $telefone = mysqli_real_escape_string($conexao, $_POST['telefone']);
    $genero = mysqli_real_escape_string($conexao, $_POST['genero']);
    $cor_raca = mysqli_real_escape_string($conexao, $_POST['cor_raca']);
    $peso_kg_val = !empty($_POST['peso_kg']) ? (float)$_POST['peso_kg'] : null;
    $altura_cm_val = !empty($_POST['altura_cm']) ? (int)$_POST['altura_cm'] : null;

    // Lógica para is_gestante
    $is_gestante = isset($_POST['is_gestante']) ? 1 : 0;
    
    // ================== NOVOS CAMPOS DO RESPONSÁVEL ==================
    $responsavel_nome = !empty($_POST['responsavel_nome']) ? mysqli_real_escape_string($conexao, $_POST['responsavel_nome']) : null;
    $responsavel_parentesco = !empty($_POST['responsavel_parentesco']) ? mysqli_real_escape_string($conexao, $_POST['responsavel_parentesco']) : null;
    // ================== FIM DOS NOVOS CAMPOS ==================

    // ================== NOVOS CAMPOS DE HISTÓRICO CLÍNICO ==================
    $tipo_sanguineo = !empty($_POST['tipo_sanguineo']) ? mysqli_real_escape_string($conexao, $_POST['tipo_sanguineo']) : null;
    $alergias_conhecidas = !empty($_POST['alergias_conhecidas']) ? mysqli_real_escape_string($conexao, $_POST['alergias_conhecidas']) : null;
    $is_diabetico = isset($_POST['is_diabetico']) ? 1 : 0;
    $is_cardiaco = isset($_POST['is_cardiaco']) ? 1 : 0;
    $is_hipertenso = isset($_POST['is_hipertenso']) ? 1 : 0;
    
    // NOVOS CAMPOS RESPIRATÓRIOS E FUMANTE
    $is_asma = isset($_POST['is_asma']) ? 1 : 0;
    $is_bronquite_cronica = isset($_POST['is_bronquite_cronica']) ? 1 : 0;
    $is_dpoc = isset($_POST['is_dpoc']) ? 1 : 0;
    $is_rinite_sinusite = isset($_POST['is_rinite_sinusite']) ? 1 : 0;
    $is_fumante = isset($_POST['is_fumante']) ? 1 : 0;
    
    // START: NOVAS COLS. DE VIROSES (4 COLUNAS)
    $is_dengue = isset($_POST['is_dengue']) ? 1 : 0;
    $is_chikungunya = isset($_POST['is_chikungunya']) ? 1 : 0;
    $is_zika = isset($_POST['is_zika']) ? 1 : 0;
    $is_covid19 = isset($_POST['is_covid19']) ? 1 : 0;
    // END: NOVAS COLS. DE VIROSES
    // ================== FIM DOS NOVOS CAMPOS ==================


    // Query ATUALIZADA (Total de 26 colunas)
    $sql = "INSERT INTO pacientes (
                nome, cpf, data_nascimento, email, telefone, genero, is_gestante, cor_raca, peso_kg, altura_cm, 
                responsavel_nome, responsavel_parentesco, tipo_sanguineo, alergias_conhecidas, 
                is_diabetico, is_cardiaco, is_hipertenso, is_asma, is_bronquite_cronica, is_dpoc, is_rinite_sinusite, is_fumante,
                is_dengue, is_chikungunya, is_zika, is_covid19 
            ) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"; // Total de 26 '?'
    
    $stmt = mysqli_prepare($conexao, $sql);
    
    // Bind ATUALIZADO (Total de 26 parâmetros: ssssssisdisssssiiiiiiiiiiii)
    mysqli_stmt_bind_param($stmt, "ssssssisdisssssiiiiiiiiiiii", 
        $nome, $cpf, $data_nascimento, $email, $telefone, 
        $genero, $is_gestante, $cor_raca, $peso_kg_val, $altura_cm_val,
        $responsavel_nome, $responsavel_parentesco, // Responsável
        $tipo_sanguineo, $alergias_conhecidas, // Histórico
        $is_diabetico, $is_cardiaco, $is_hipertenso, // Crônicas
        $is_asma, $is_bronquite_cronica, $is_dpoc, $is_rinite_sinusite, $is_fumante, // Respiratórias/Hábito
        $is_dengue, $is_chikungunya, $is_zika, $is_covid19 // NOVAS VIROSES
    );

    if (mysqli_stmt_execute($stmt)) {
        header("Location: pacientes.php?status=sucesso");
        exit();
    } else {
        $erro = "Erro ao cadastrar paciente: " . mysqli_error($conexao);
    }
}

require_once '../header.php';
?>

<h1>Adicionar Novo Paciente</h1>
<hr>

<?php if ($erro): ?>
    <div class="alert alert-danger" role="alert"><?= $erro ?></div>
<?php endif; ?>

<form action="adicionar_paciente.php" method="POST">
    <div class="row">
        <div class="col-md-6 mb-3"><label for="nome" class="form-label">Nome Completo:</label><input type="text" class="form-control" id="nome" name="nome" required></div>
        <div class="col-md-6 mb-3"><label for="cpf" class="form-label">CPF:</label><input type="text" class="form-control" id="cpf" name="cpf" required></div>
    </div>
    <div class="row">
        <div class="col-md-3 mb-3">
             <label for="data_nascimento" class="form-label">Data de Nascimento:</label>
             <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" required>
        </div>
        <div class="col-md-3 mb-3"><label for="email" class="form-label">Email:</label><input type="email" class="form-control" id="email" name="email"></div>
        <div class="col-md-3 mb-3"><label for="telefone" class="form-label">Telefone:</label><input type="text" class="form-control" id="telefone" name="telefone"></div>
    </div>
    
    <div class="row">
        <div class="col-md-3 mb-3">
            <label for="genero" class="form-label">Gênero:</label>
            <select class="form-select" id="genero" name="genero">
                <option value="">Selecione...</option>
                <option value="Masculino">Masculino</option>
                <option value="Feminino">Feminino</option>
                <option value="Outro">Outro</option>
            </select>
        </div>
        
        <div class="col-md-3 mb-3 d-flex align-items-end" id="gestante_checkbox_container" style="display: none;">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="is_gestante" name="is_gestante" value="1">
                <label class="form-check-label" for="is_gestante">
                    É Gestante?
                </label>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <label for="cor_raca" class="form-label">Cor ou Raça:</label>
            <select class="form-select" id="cor_raca" name="cor_raca">
                <option value="">Selecione...</option>
                <option value="Branca">Branca</option>
                <option value="Preta">Preta</option>
                <option value="Parda">Parda</option>
                <option value="Amarela">Amarela</option>
                <option value="Indígena">Indígena</option>
            </select>
        </div>
        <div class="col-md-3 mb-3"><label for="peso_kg" class="form-label">Peso (kg):</label><input type="number" step="0.01" class="form-control" id="peso_kg" name="peso_kg" placeholder="Ex: 75.50"></div>
        <div class="col-md-3 mb-3"><label for="altura_cm" class="form-label">Altura (cm):</label><input type="number" class="form-control" id="altura_cm" name="altura_cm" placeholder="Ex: 178"></div>
    </div>
    
    <hr>
    <h5 class="mb-3 text-primary"><i class="fas fa-heartbeat me-2"></i>Histórico Clínico Essencial</h5>
    <div class="row">
        <div class="col-md-3 mb-3">
            <label for="tipo_sanguineo" class="form-label">Tipo Sanguíneo:</label>
            <select class="form-select" id="tipo_sanguineo" name="tipo_sanguineo">
                <option value="">Selecione...</option>
                <option value="A+">A+</option>
                <option value="A-">A-</option>
                <option value="B+">B+</option>
                <option value="B-">B-</option>
                <option value="AB+">AB+</option>
                <option value="AB-">AB-</option>
                <option value="O+">O+</option>
                <option value="O-">O-</option>
            </select>
        </div>
        <div class="col-md-9 mb-3">
            <label for="alergias_conhecidas" class="form-label">Alergias Conhecidas:</label>
            <input type="text" class="form-control" id="alergias_conhecidas" name="alergias_conhecidas" placeholder="Ex: Penicilina, Látex, Pólen...">
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12 mb-3">
            <label class="form-label fw-bold">1. Condições Metabólicas e Cardiovasculares:</label><br>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="is_diabetico" name="is_diabetico" value="1">
                <label class="form-check-label" for="is_diabetico">Diabético</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="is_cardiaco" name="is_cardiaco" value="1">
                <label class="form-check-label" for="is_cardiaco">Cardíaco</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="is_hipertenso" name="is_hipertenso" value="1">
                <label class="form-check-label" for="is_hipertenso">Hipertenso</label>
            </div>
        </div>
        
        <div class="col-md-12 mb-3">
             <label class="form-label fw-bold">2. Patologias Respiratórias e Hábito:</label><br>
             <div class="form-check form-check-inline">
                 <input class="form-check-input" type="checkbox" id="is_asma" name="is_asma" value="1">
                 <label class="form-check-label" for="is_asma">Asma</label>
             </div>
             <div class="form-check form-check-inline">
                 <input class="form-check-input" type="checkbox" name="is_bronquite_cronica" id="is_bronquite_cronica" value="1">
                 <label class="form-check-label" for="is_bronquite_cronica">Bronquite Crônica</label>
             </div>
             <div class="form-check form-check-inline">
                 <input class="form-check-input" type="checkbox" name="is_dpoc" id="is_dpoc" value="1">
                 <label class="form-check-label" for="is_dpoc">DPOC/Enfisema</label>
             </div>
             <div class="form-check form-check-inline">
                 <input class="form-check-input" type="checkbox" name="is_rinite_sinusite" id="is_rinite_sinusite" value="1">
                 <label class="form-check-label" for="is_rinite_sinusite">Rinite/Sinusite</label>
             </div>
             <div class="form-check form-check-inline">
                 <input class="form-check-input" type="checkbox" name="is_fumante" id="is_fumante" value="1">
                 <label class="form-check-label" for="is_fumante">Fumante (Atual/Ex)</label>
             </div>
        </div>
        
        <div class="col-md-12 mb-3">
             <label class="form-label fw-bold text-danger">3. Histórico de Viroses:</label><br>
             <div class="form-check form-check-inline">
                 <input class="form-check-input" type="checkbox" id="is_dengue" name="is_dengue" value="1">
                 <label class="form-check-label" for="is_dengue">Dengue</label>
             </div>
             <div class="form-check form-check-inline">
                 <input class="form-check-input" type="checkbox" name="is_chikungunya" id="is_chikungunya" value="1">
                 <label class="form-check-label" for="is_chikungunya">Chikungunya</label>
             </div>
             <div class="form-check form-check-inline">
                 <input class="form-check-input" type="checkbox" name="is_zika" id="is_zika" value="1">
                 <label class="form-check-label" for="is_zika">Zika</label>
             </div>
             <div class="form-check form-check-inline">
                 <input class="form-check-input" type="checkbox" name="is_covid19" id="is_covid19" value="1">
                 <label class="form-check-label" for="is_covid19">COVID-19</label>
             </div>
        </div>
        </div>
    
    <div id="responsavel_fields" style="display: none;">
        <hr>
        <h5 class="mb-3 text-warning"><i class="fas fa-user-shield me-2"></i>Informações do Responsável (Obrigatório para menores de 18)</h5>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="responsavel_nome" class="form-label">Nome do Responsável:</label>
                <input type="text" class="form-control" id="responsavel_nome" name="responsavel_nome" 
                       maxlength="100" placeholder="Nome completo do responsável legal">
            </div>
            <div class="col-md-6 mb-3">
                <label for="responsavel_parentesco" class="form-label">Parentesco/Relação:</label>
                <input type="text" class="form-control" id="responsavel_parentesco" name="responsavel_parentesco" 
                       maxlength="50" placeholder="Ex: Mãe, Pai, Tutor">
            </div>
        </div>
    </div>
    <div class="mt-3"><button type="submit" class="btn btn-primary">Salvar</button><a href="pacientes.php" class="btn btn-secondary">Cancelar</a></div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Referências para o campo Gênero (lógica Gestante)
    const generoSelect = document.getElementById('genero');
    const gestanteContainer = document.getElementById('gestante_checkbox_container');
    const gestanteCheckbox = document.getElementById('is_gestante');
    
    // Referências para a lógica do Responsável (Nova lógica)
    const dataNascimentoInput = document.getElementById('data_nascimento');
    const responsavelFieldsDiv = document.getElementById('responsavel_fields');
    const responsavelNomeInput = document.getElementById('responsavel_nome');
    const responsavelParentescoInput = document.getElementById('responsavel_parentesco');

    // --- Lógica 1: Exibir Gestante ---
    generoSelect.addEventListener('change', function() {
        if (this.value === 'Feminino') {
            gestanteContainer.style.display = 'flex';
        } else {
            gestanteContainer.style.display = 'none';
            gestanteCheckbox.checked = false;
        }
    });

    // --- Lógica 2: Exibir Responsável (Menor de 18) ---
    function verificarIdadeEResponsavel() {
        const dataNascStr = dataNascimentoInput.value;
        if (!dataNascStr) {
            // Oculta e remove 'required' se a data estiver vazia
            responsavelFieldsDiv.style.display = 'none';
            responsavelNomeInput.required = false;
            responsavelParentescoInput.required = false;
            return;
        }

        const dataNasc = new Date(dataNascStr + 'T00:00:00'); // Adiciona T00:00:00 para evitar fusos
        const hoje = new Date();
        
        let idade = hoje.getFullYear() - dataNasc.getFullYear();
        const mes = hoje.getMonth() - dataNasc.getMonth();
        
        // Ajusta a idade se o aniversário ainda não ocorreu este ano
        if (mes < 0 || (mes === 0 && hoje.getDate() < dataNasc.getDate())) {
            idade--;
        }

        // Se o paciente for menor de 18 anos
        if (idade < 18) {
            responsavelFieldsDiv.style.display = 'block';
            responsavelNomeInput.required = true;
            responsavelParentescoInput.required = true;
        } else {
            responsavelFieldsDiv.style.display = 'none';
            responsavelNomeInput.required = false;
            responsavelParentescoInput.required = false;
            
            // Limpa os campos ocultos, garantindo que não sejam salvos dados de maior de 18
            responsavelNomeInput.value = '';
            responsavelParentescoInput.value = '';
        }
    }

    // Chama a função toda vez que a data de nascimento muda
    dataNascimentoInput.addEventListener('change', verificarIdadeEResponsavel);
    
    // Roda no carregamento inicial
    verificarIdadeEResponsavel(); 
});
</script>
<?php require_once '../footer.php'; ?>