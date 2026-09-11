<?php
// ===== INÍCIO DA LÓGICA DE AUTORIZAÇÃO (JÁ EXISTENTE) =====
require_once '../auth.php';

// 1. Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /auth/login.php');
    exit();
}

// 2. Pega o ID do paciente que está sendo solicitado
$id_paciente_solicitado = 0;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_paciente_solicitado = (int)$_POST['id'];
} elseif (isset($_GET['id'])) {
    $id_paciente_solicitado = (int)$_GET['id'];
} else {
    // Se não houver ID, não podemos fazer nada.
    // ❌ Ponto 1: ID não fornecido
    include '../acesso_negado_tela.php'; 
    exit();
}

// 3. Pega os dados do usuário logado
$usuario_tipo = $_SESSION['usuario_tipo'];
$usuario_id_referencia = (int)$_SESSION['id_referencia'];

// 4. Aplica as regras de permissão
if ($usuario_tipo == 'gerente') {
    // Gerente pode editar qualquer um. Deixa passar.
} elseif ($usuario_tipo == 'paciente') {
    // Paciente só pode editar a si mesmo.
    if ($id_paciente_solicitado != $usuario_id_referencia) {
        // ❌ Ponto 2: Paciente tentando editar outro perfil
        include '../acesso_negado_tela.php'; 
        exit();
    }
} else {
    // Medico ou outro tipo não pode estar aqui.
    // ❌ Ponto 3: Tipo de usuário não autorizado a editar perfis
    include '../acesso_negado_tela.php'; 
    exit();
}
// ===== FIM DA LÓGICA DE AUTORIZAÇÃO =====

require_once '../conexao.php';

$erro = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ==========================================================
    // LÓGICA DE SALVAMENTO (UPDATE)
    // ==========================================================
    $id = $id_paciente_solicitado;
    $nome = trim($_POST['nome']);
    $cpf = trim($_POST['cpf']);
    $data_nascimento = trim($_POST['data_nascimento']);
    $email = trim($_POST['email']);
    $telefone = trim($_POST['telefone']);
    $genero = trim($_POST['genero']);
    $cor_raca = trim($_POST['cor_raca']);
    $peso_kg_val = !empty($_POST['peso_kg']) ? (float)$_POST['peso_kg'] : null;
    $altura_cm_val = !empty($_POST['altura_cm']) ? (int)$_POST['altura_cm'] : null;

    // Lógica para is_gestante
    $is_gestante = 0;
    $data_ultima_menstruacao = NULL;
    $medico_prenatal_id = NULL;

    if ($genero == 'Feminino') {
        $is_gestante = isset($_POST['is_gestante']) ? 1 : 0;
        
        if ($is_gestante == 1) {
            $data_ultima_menstruacao = !empty($_POST['data_ultima_menstruacao']) ? trim($_POST['data_ultima_menstruacao']) : NULL;
            $medico_prenatal_id = !empty($_POST['medico_prenatal_id']) ? (int)$_POST['medico_prenatal_id'] : NULL;
        }
    }

    // ================== NOVOS CAMPOS DO RESPONSÁVEL ==================
    $responsavel_nome = !empty($_POST['responsavel_nome']) ? trim($_POST['responsavel_nome']) : null;
    $responsavel_parentesco = !empty($_POST['responsavel_parentesco']) ? trim($_POST['responsavel_parentesco']) : null;
    // ================== FIM DOS NOVOS CAMPOS ==================
    
    // ================== NOVOS CAMPOS DE HISTÓRICO CLÍNICO ==================
    $tipo_sanguineo = !empty($_POST['tipo_sanguineo']) ? trim($_POST['tipo_sanguineo']) : null;
    $alergias_conhecidas = !empty($_POST['alergias_conhecidas']) ? trim($_POST['alergias_conhecidas']) : null;
    $is_diabetico = isset($_POST['is_diabetico']) ? 1 : 0;
    $is_cardiaco = isset($_POST['is_cardiaco']) ? 1 : 0;
    $is_hipertenso = isset($_POST['is_hipertenso']) ? 1 : 0;
    
    // NOVOS CAMPOS RESPIRATÓRIOS E FUMANTE
    $is_asma = isset($_POST['is_asma']) ? 1 : 0;
    $is_bronquite_cronica = isset($_POST['is_bronquite_cronica']) ? 1 : 0;
    $is_dpoc = isset($_POST['is_dpoc']) ? 1 : 0;
    $is_rinite_sinusite = isset($_POST['is_rinite_sinusite']) ? 1 : 0;
    $is_fumante = isset($_POST['is_fumante']) ? 1 : 0;
    // NOVAS VIROSES
    $is_dengue = isset($_POST['is_dengue']) ? 1 : 0;
    $is_chikungunya = isset($_POST['is_chikungunya']) ? 1 : 0;
    $is_zika = isset($_POST['is_zika']) ? 1 : 0;
    $is_covid19 = isset($_POST['is_covid19']) ? 1 : 0;
    // ================== FIM DOS NOVOS CAMPOS ==================

    // Query UPDATE ATUALIZADA (24 colunas sendo atualizadas + 1 ID)
    $sql = "UPDATE pacientes SET 
                nome = ?, cpf = ?, data_nascimento = ?, email = ?, telefone = ?, 
                genero = ?, is_gestante = ?, data_ultima_menstruacao = ?, medico_prenatal_id = ?,
                cor_raca = ?, peso_kg = ?, altura_cm = ?,
                responsavel_nome = ?, responsavel_parentesco = ?, 
                tipo_sanguineo = ?, alergias_conhecidas = ?, 
                is_diabetico = ?, is_cardiaco = ?, is_hipertenso = ?,
                is_asma = ?, is_bronquite_cronica = ?, is_dpoc = ?, is_rinite_sinusite = ?, is_fumante = ?,
                is_dengue = ?, is_chikungunya = ?, is_zika = ?, is_covid19 = ?
            WHERE id = ?"; 
    
    $stmt = mysqli_prepare($conexao, $sql);
    
    // CORREÇÃO CRÍTICA NA STRING DE TIPOS (25 's'/'i'/'d' para 25 variáveis)
    // ssssss i s i s d i s s s s i i i i i i i i i
    mysqli_stmt_bind_param($stmt, "ssssssisisdissssiiiiiiiiiiiii", 
        $nome, $cpf, $data_nascimento, $email, $telefone, 
        $genero, $is_gestante, $data_ultima_menstruacao, $medico_prenatal_id, 
        $cor_raca, $peso_kg_val, $altura_cm_val,
        $responsavel_nome, $responsavel_parentesco, 
        $tipo_sanguineo, $alergias_conhecidas, 
        $is_diabetico, $is_cardiaco, $is_hipertenso,
        $is_asma, $is_bronquite_cronica, $is_dpoc, $is_rinite_sinusite, $is_fumante, // Novos campos
        $is_dengue, $is_chikungunya, $is_zika, $is_covid19, // viruses
        $id // último parâmetro
    );

    if (mysqli_stmt_execute($stmt)) {
        
        // GATILHO DO PLANO DE PRÉ-NATAL (MANTIDO)
        if ($is_gestante == 1 && !empty($data_ultima_menstruacao) && !empty($medico_prenatal_id)) {
            require_once '../helpers/plano_prenatal_helper.php';
            // Assumimos que gerarPlanoPrenatal não dá erro fatal
        }
        
        // 2. Atualizar E-mail do Usuário (MANTIDO)
        $sql_user_email = "UPDATE usuarios SET email = ? WHERE id_referencia = ? AND tipo_usuario = 'paciente'";
        $stmt_user_email = mysqli_prepare($conexao, $sql_user_email);
        mysqli_stmt_bind_param($stmt_user_email, "si", $email, $id);
        mysqli_stmt_execute($stmt_user_email);

        // Redirecionamento (MANTIDO)
        if ($usuario_tipo == 'gerente') {
            header("Location: detalhes_paciente.php?id=$id&status=sucesso");
        } else {
             if ($is_gestante == 1 && !empty($data_ultima_menstruacao) && !empty($medico_prenatal_id)) {
                 header("Location: ../area_paciente.php?status=" . urlencode("Plano de pré-natal iniciado com sucesso!"));
            } else {
                 header("Location: ../area_paciente.php?status=perfil_atualizado");
            }
        }
        exit();
    } else {
        $erro = "Erro ao atualizar paciente: " . mysqli_error($conexao);
    }
    
    // Se houve erro no POST, a página precisa recarregar os dados para a edição
    $id_paciente_solicitado = $id; 
}

$id = $id_paciente_solicitado;

// Query SELECT ATUALIZADA - Traz todos os campos do paciente (p.*) e o email de login (u.email)
$sql_paciente = "SELECT p.*, u.email AS email_usuario FROM pacientes p 
                 LEFT JOIN usuarios u ON p.id = u.id_referencia AND u.tipo_usuario = 'paciente' 
                 WHERE p.id = ?";
$stmt_get = mysqli_prepare($conexao, $sql_paciente);
mysqli_stmt_bind_param($stmt_get, "i", $id);
mysqli_stmt_execute($stmt_get);
$resultado = mysqli_stmt_get_result($stmt_get);
$paciente = mysqli_fetch_assoc($resultado);

if (!$paciente) {
    header("Location: pacientes.php");
    exit();
}

// ================== BUSCAR OBSTETRAS (JÁ EXISTENTE) ==================
$sql_medicos = "SELECT id, nome_completo FROM medicos 
                WHERE especialidade = 'Ginecologia e Obstetrícia' 
                   OR especialidade = 'Obstetrícia'
                ORDER BY nome_completo ASC";
$resultado_medicos = mysqli_query($conexao, $sql_medicos);
$lista_obstetras = [];
if($resultado_medicos) {
    while($med = mysqli_fetch_assoc($resultado_medicos)) {
        $lista_obstetras[] = $med;
    }
}
// ================== FIM DA BUSCA ==================


require_once '../header.php';
?>

<h1>Editar Paciente</h1>
<hr>

<?php if ($erro): ?>
    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($erro) ?></div>
<?php endif; ?>

<form action="editar_paciente.php" method="POST">
    <input type="hidden" name="id" value="<?= $paciente['id'] ?>">
    <div class="row">
        <div class="col-md-6 mb-3"><label for="nome" class="form-label">Nome Completo:</label><input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($paciente['nome'] ?? '') ?>" required></div>
        <div class="col-md-6 mb-3"><label for="cpf" class="form-label">CPF:</label><input type="text" class="form-control" id="cpf" name="cpf" value="<?= htmlspecialchars($paciente['cpf'] ?? '') ?>" required></div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3"><label for="data_nascimento" class="form-label">Data de Nascimento:</label><input type="date" class="form-control" id="data_nascimento" name="data_nascimento" value="<?= htmlspecialchars($paciente['data_nascimento'] ?? '') ?>"></div>
        <div class="col-md-4 mb-3"><label for="email" class="form-label">Email de Login:</label><input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($paciente['email_usuario'] ?? '') ?>"></div>
        <div class="col-md-4 mb-3"><label for="telefone" class="form-label">Telefone:</label><input type="text" class="form-control" id="telefone" name="telefone" value="<?= htmlspecialchars($paciente['telefone'] ?? '') ?>"></div>
    </div>
    
    <div class="row">
        <div class="col-md-3 mb-3">
            <label for="genero" class="form-label">Gênero:</label>
            <select class="form-select" id="genero" name="genero">
                <option value="Masculino" <?= ($paciente['genero'] ?? '') == 'Masculino' ? 'selected' : '' ?>>Masculino</option>
                <option value="Feminino" <?= ($paciente['genero'] ?? '') == 'Feminino' ? 'selected' : '' ?>>Feminino</option>
                <option value="Outro" <?= ($paciente['genero'] ?? '') == 'Outro' ? 'selected' : '' ?>>Outro</option>
            </select>
        </div>
        
        <div class="col-md-3 mb-3 d-flex align-items-end" id="gestante_checkbox_container" style="display: none;">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="is_gestante" name="is_gestante" value="1"
                       <?php if (!empty($paciente['is_gestante']) && $paciente['is_gestante'] == 1) echo 'checked'; ?>
                       >
                <label class="form-check-label" for="is_gestante">
                    É Gestante?
                </label>
            </div>
        </div>

        <div class="col-md-3 mb-3" id="dum_container" style="display: none;">
             <label for="data_ultima_menstruacao" class="form-label">Data da Última Menstruação (DUM):</label>
             <input type="date" class="form-control" id="data_ultima_menstruacao" name="data_ultima_menstruacao"
                    value="<?= htmlspecialchars($paciente['data_ultima_menstruacao'] ?? '') ?>">
        </div>
        
        <div class="col-md-3 mb-3" id="medico_prenatal_container" style="display: none;">
             <label for="medico_prenatal_id" class="form-label">Médico Responsável (Pré-Natal):</label>
             <select class="form-select" id="medico_prenatal_id" name="medico_prenatal_id">
                 <option value="">Selecione um(a) obstetra...</option>
                 <?php foreach ($lista_obstetras as $medico): ?>
                    <option value="<?= $medico['id'] ?>" 
                            <?= ($paciente['medico_prenatal_id'] ?? '') == $medico['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($medico['nome_completo']) ?>
                    </option>
                 <?php endforeach; ?>
             </select>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3 mb-3"><label for="cor_raca" class="form-label">Cor ou Raça:</label>
            <select class="form-select" id="cor_raca" name="cor_raca">
                <option value="Branca" <?= ($paciente['cor_raca'] ?? '') == 'Branca' ? 'selected' : '' ?>>Branca</option>
                <option value="Preta" <?= ($paciente['cor_raca'] ?? '') == 'Preta' ? 'selected' : '' ?>>Preta</option>
                <option value="Parda" <?= ($paciente['cor_raca'] ?? '') == 'Parda' ? 'selected' : '' ?>>Parda</option>
                <option value="Amarela" <?= ($paciente['cor_raca'] ?? '') == 'Amarela' ? 'selected' : '' ?>>Amarela</option>
                <option value="Indígena" <?= ($paciente['cor_raca'] ?? '') == 'Indígena' ? 'selected' : '' ?>>Indígena</option>
            </select>
        </div>
        <div class="col-md-3 mb-3"><label for="peso_kg" class="form-label">Peso (kg):</label><input type="number" step="0.01" class="form-control" id="peso_kg" name="peso_kg" value="<?= htmlspecialchars($paciente['peso_kg'] ?? '') ?>" placeholder="Ex: 75.50"></div>
        <div class="col-md-3 mb-3"><label for="altura_cm" class="form-label">Altura (cm):</label><input type="number" class="form-control" id="altura_cm" name="altura_cm" value="<?= htmlspecialchars($paciente['altura_cm'] ?? '') ?>" placeholder="Ex: 178"></div>
    </div>
    
    <hr>
    <h5 class="mb-3 text-primary"><i class="fas fa-heartbeat me-2"></i>Histórico Clínico Essencial</h5>
    <div class="row">
        <div class="col-md-3 mb-3">
            <label for="tipo_sanguineo" class="form-label">Tipo Sanguíneo:</label>
            <select class="form-select" id="tipo_sanguineo" name="tipo_sanguineo">
                <option value="">Selecione...</option>
                <option value="A+" <?= ($paciente['tipo_sanguineo'] ?? '') == 'A+' ? 'selected' : '' ?>>A+</option>
                <option value="A-" <?= ($paciente['tipo_sanguineo'] ?? '') == 'A-' ? 'selected' : '' ?>>A-</option>
                <option value="B+" <?= ($paciente['tipo_sanguineo'] ?? '') == 'B+' ? 'selected' : '' ?>>B+</option>
                <option value="B-" <?= ($paciente['tipo_sanguineo'] ?? '') == 'B-' ? 'selected' : '' ?>>B-</option>
                <option value="AB+" <?= ($paciente['tipo_sanguineo'] ?? '') == 'AB+' ? 'selected' : '' ?>>AB+</option>
                <option value="AB-" <?= ($paciente['tipo_sanguineo'] ?? '') == 'AB-' ? 'selected' : '' ?>>AB-</option>
                <option value="O+" <?= ($paciente['tipo_sanguineo'] ?? '') == 'O+' ? 'selected' : '' ?>>O+</option>
                <option value="O-" <?= ($paciente['tipo_sanguineo'] ?? '') == 'O-' ? 'selected' : '' ?>>O-</option>
            </select>
        </div>
        <div class="col-md-9 mb-3">
            <label for="alergias_conhecidas" class="form-label">Alergias Conhecidas:</label>
            <input type="text" class="form-control" id="alergias_conhecidas" name="alergias_conhecidas" 
                   value="<?= htmlspecialchars($paciente['alergias_conhecidas'] ?? '') ?>" placeholder="Ex: Penicilina, Látex, Pólen...">
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12 mb-3">
            <label class="form-label fw-bold">1. Condições Metabólicas e Cardiovasculares:</label><br>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="is_diabetico" name="is_diabetico" value="1"
                       <?= (!empty($paciente['is_diabetico']) && $paciente['is_diabetico'] == 1) ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_diabetico">Diabético</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="is_cardiaco" name="is_cardiaco" value="1"
                       <?= (!empty($paciente['is_cardiaco']) && $paciente['is_cardiaco'] == 1) ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_cardiaco">Cardíaco</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="is_hipertenso" name="is_hipertenso" value="1"
                       <?= (!empty($paciente['is_hipertenso']) && $paciente['is_hipertenso'] == 1) ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_hipertenso">Hipertenso</label>
            </div>
        </div>
        
        <div class="col-md-12 mb-3">
             <label class="form-label fw-bold">2. Patologias Respiratórias e Hábito:</label><br>
             <div class="form-check form-check-inline">
                 <input class="form-check-input" type="checkbox" id="is_asma" name="is_asma" value="1"
                        <?= (!empty($paciente['is_asma']) && $paciente['is_asma'] == 1) ? 'checked' : '' ?>>
                 <label class="form-check-label" for="is_asma">Asma</label>
             </div>
             <div class="form-check form-check-inline">
                 <input class="form-check-input" type="checkbox" name="is_bronquite_cronica" id="is_bronquite_cronica" value="1"
                        <?= (!empty($paciente['is_bronquite_cronica']) && $paciente['is_bronquite_cronica'] == 1) ? 'checked' : '' ?>>
                 <label class="form-check-label" for="is_bronquite_cronica">Bronquite Crônica</label>
             </div>
             <div class="form-check form-check-inline">
                 <input class="form-check-input" type="checkbox" name="is_dpoc" id="is_dpoc" value="1"
                        <?= (!empty($paciente['is_dpoc']) && $paciente['is_dpoc'] == 1) ? 'checked' : '' ?>>
                 <label class="form-check-label" for="is_dpoc">DPOC/Enfisema</label>
             </div>
             <div class="form-check form-check-inline">
                 <input class="form-check-input" type="checkbox" name="is_rinite_sinusite" id="is_rinite_sinusite" value="1"
                        <?= (!empty($paciente['is_rinite_sinusite']) && $paciente['is_rinite_sinusite'] == 1) ? 'checked' : '' ?>>
                 <label class="form-check-label" for="is_rinite_sinusite">Rinite/Sinusite</label>
             </div>
             <div class="form-check form-check-inline">
                 <input class="form-check-input" type="checkbox" name="is_fumante" id="is_fumante" value="1"
                        <?= (!empty($paciente['is_fumante']) && $paciente['is_fumante'] == 1) ? 'checked' : '' ?>>
                 <label class="form-check-label" for="is_fumante">Fumante (Atual/Ex)</label>
             </div>
        </div>
        
        <div class="col-md-12 mb-3">
             <label class="form-label fw-bold">3. Você tem ou já teve algumas das seguintes viroses?</label><br>
             <div class="form-check form-check-inline">
                 <input class="form-check-input" type="checkbox" id="is_dengue" name="is_dengue" value="1"
                        <?= (!empty($paciente['is_dengue']) && $paciente['is_dengue'] == 1) ? 'checked' : '' ?>>
                 <label class="form-check-label" for="is_dengue">Dengue</label>
             </div>
             <div class="form-check form-check-inline">
                 <input class="form-check-input" type="checkbox" name="is_chikungunya" id="is_chikungunya" value="1"
                        <?= (!empty($paciente['is_chikungunya']) && $paciente['is_chikungunya'] == 1) ? 'checked' : '' ?>>
                 <label class="form-check-label" for="is_chikungunya">Chikungunya</label>
             </div>
             <div class="form-check form-check-inline">
                 <input class="form-check-input" type="checkbox" name="is_zika" id="is_zika" value="1"
                        <?= (!empty($paciente['is_zika']) && $paciente['is_zika'] == 1) ? 'checked' : '' ?>>
                 <label class="form-check-label" for="is_zika">Zika</label>
             </div>
             <div class="form-check form-check-inline">
                 <input class="form-check-input" type="checkbox" name="is_covid19" id="is_covid19" value="1"
                        <?= (!empty($paciente['is_covid19']) && $paciente['is_covid19'] == 1) ? 'checked' : '' ?>>
                 <label class="form-check-label" for="is_covid19">COVID-19</label>
             </div>

    </div>
    
    <div id="responsavel_fields" style="display: none;">
        <hr>
        <h5 class="mb-3 text-warning"><i class="fas fa-user-shield me-2"></i>Informações do Responsável (Obrigatório para menores de 18)</h5>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="responsavel_nome" class="form-label">Nome do Responsável:</label>
                <input type="text" class="form-control" id="responsavel_nome" name="responsavel_nome" 
                       maxlength="100" placeholder="Nome completo do responsável legal"
                       value="<?= htmlspecialchars($paciente['responsavel_nome'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="responsavel_parentesco" class="form-label">Parentesco/Relação:</label>
                <input type="text" class="form-control" id="responsavel_parentesco" name="responsavel_parentesco" 
                       maxlength="50" placeholder="Ex: Mãe, Pai, Tutor"
                       value="<?= htmlspecialchars($paciente['responsavel_parentesco'] ?? '') ?>">
            </div>
        </div>
    </div>
    <div class="mt-3">
        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        <?php
        $link_cancelar = "detalhes_paciente.php?id=" . $paciente['id'];
        if ($usuario_tipo == 'paciente') {
            $link_cancelar = '../area_paciente.php';
        }
        ?>
        <a href="<?= $link_cancelar ?>" class="btn btn-secondary">Cancelar</a>
        <p class="mt-4 text-muted small">Para alterar o seu email ou senha de login, por favor, aceda à <a href="/auth/editar_perfil_usuario.php">página de edição de conta</a>.</p>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Referências a todos os campos
    const generoSelect = document.getElementById('genero');
    const gestanteContainer = document.getElementById('gestante_checkbox_container');
    const gestanteCheckbox = document.getElementById('is_gestante');
    const dumContainer = document.getElementById('dum_container');
    const dumInput = document.getElementById('data_ultima_menstruacao');
    const medicoContainer = document.getElementById('medico_prenatal_container');
    const medicoInput = document.getElementById('medico_prenatal_id');

    // --- Lógica de Responsável (Idade) ---
    const dataNascimentoInput = document.getElementById('data_nascimento');
    const responsavelFieldsDiv = document.getElementById('responsavel_fields');
    const responsavelNomeInput = document.getElementById('responsavel_nome');
    const responsavelParentescoInput = document.getElementById('responsavel_parentesco');

    // Função de GESTANTE
    function toggleGestanteFields() {
        if (generoSelect.value === 'Feminino') {
            gestanteContainer.style.display = 'flex'; 
            
            if (gestanteCheckbox.checked) {
                dumContainer.style.display = 'block'; 
                medicoContainer.style.display = 'block'; 
                dumInput.required = true; 
                medicoInput.required = true; 
            } else {
                dumContainer.style.display = 'none'; 
                medicoContainer.style.display = 'none'; 
                dumInput.required = false;
                medicoInput.required = false;
                dumInput.value = ''; 
                medicoInput.value = ''; 
            }
        } else {
            // Se não for 'Feminino', esconde TUDO
            gestanteContainer.style.display = 'none';
            dumContainer.style.display = 'none';
            medicoContainer.style.display = 'none';
            gestanteCheckbox.checked = false;
            dumInput.required = false;
            medicoInput.required = false;
            dumInput.value = '';
            medicoInput.value = '';
        }
    }

    // Função de RESPONSÁVEL
    function verificarIdadeEResponsavel() {
        const dataNascStr = dataNascimentoInput.value;
        if (!dataNascStr) {
            responsavelFieldsDiv.style.display = 'none';
            responsavelNomeInput.required = false;
            responsavelParentescoInput.required = false;
            return;
        }

        const dataNasc = new Date(dataNascStr + 'T00:00:00'); 
        const hoje = new Date();
        
        let idade = hoje.getFullYear() - dataNasc.getFullYear();
        const mes = hoje.getMonth() - dataNasc.getMonth();
        
        if (mes < 0 || (mes === 0 && hoje.getDate() < dataNasc.getDate())) {
            idade--;
        }

        // Lógica: Menor de 18 anos
        if (idade < 18) {
            responsavelFieldsDiv.style.display = 'block';
            responsavelNomeInput.required = true;
            responsavelParentescoInput.required = true;
        } else {
            responsavelFieldsDiv.style.display = 'none';
            responsavelNomeInput.required = false;
            responsavelParentescoInput.required = false;
            
            // Limpa os valores para evitar salvar dados antigos se o paciente se tornar maior
            responsavelNomeInput.value = '';
            responsavelParentescoInput.value = '';
        }
    }


    // Adiciona os \"ouvintes\" para os controles
    generoSelect.addEventListener('change', toggleGestanteFields);
    gestanteCheckbox.addEventListener('change', toggleGestanteFields);
    dataNascimentoInput.addEventListener('change', verificarIdadeEResponsavel); 

    // Executa as funções uma vez ao carregar a página para definir o estado inicial correto
    toggleGestanteFields();
    verificarIdadeEResponsavel();
});
</script>
<?php require_once '../footer.php'; ?>
