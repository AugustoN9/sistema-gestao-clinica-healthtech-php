<?php


require_once '../conexao.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: pacientes.php");
    exit();
}
$id = (int)$_GET['id'];

// SQL para buscar paciente (AGORA INCLUINDO AS NOVAS COLUNAS DE VIROSES)
$sql_paciente = "SELECT * FROM pacientes WHERE id = $id";
$resultado = mysqli_query($conexao, $sql_paciente);
$paciente = mysqli_fetch_assoc($resultado);
if (!$paciente) {
    header("Location: pacientes.php");
    exit();
}

require_once '../header.php';
?>

<div class="d-flex justify-content-between align-items-center">
    <h1>Detalhes do Paciente</h1>
    <a href="pacientes.php" class="btn btn-secondary">Voltar para a Lista</a>
</div>
<hr>

<fieldset disabled>
    <div class="row">
        <div class="col-md-6 mb-3"><label class="form-label">Nome Completo:</label><input type="text" class="form-control" value="<?= htmlspecialchars($paciente['nome'] ?? '') ?>"></div>
        <div class="col-md-6 mb-3"><label class="form-label">CPF:</label><input type="text" class="form-control" value="<?= htmlspecialchars($paciente['cpf'] ?? '') ?>"></div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3"><label class="form-label">Data de Nascimento:</label><input type="text" class="form-control" value="<?= !empty($paciente['data_nascimento']) ? date('d/m/Y', strtotime($paciente['data_nascimento'])) : '' ?>"></div>
        <div class="col-md-4 mb-3"><label class="form-label">Email:</label><input type="email" class="form-control" value="<?= htmlspecialchars($paciente['email'] ?? '') ?>"></div>
        <div class="col-md-4 mb-3"><label class="form-label">Telefone:</label><input type="text" class="form-control" value="<?= htmlspecialchars($paciente['telefone'] ?? '') ?>"></div>
    </div>
    <div class="row">
        <div class="col-md-3 mb-3"><label class="form-label">Gênero:</label><input type="text" class="form-control" value="<?= htmlspecialchars($paciente['genero'] ?? '') ?>"></div>
        <div class="col-md-3 mb-3"><label class="form-label">Cor ou Raça:</label><input type="text" class="form-control" value="<?= htmlspecialchars($paciente['cor_raca'] ?? '') ?>"></div>
        <div class="col-md-3 mb-3"><label class="form-label">Peso (kg):</label><input type="text" class="form-control" value="<?= htmlspecialchars($paciente['peso_kg'] ?? '') ?>"></div>
        <div class="col-md-3 mb-3"><label class="form-label">Altura (cm):</label><input type="text" class="form-control" value="<?= htmlspecialchars($paciente['altura_cm'] ?? '') ?>"></div>
    </div>
    <hr>
    <h5 class="mb-3 text-primary"><i class="fas fa-heartbeat me-2"></i>Histórico Clínico Essencial</h5>
    <div class="row">
        <div class="col-md-4 mb-3"><label class="form-label">Tipo Sanguíneo:</label><input type="text" class="form-control" value="<?= htmlspecialchars($paciente['tipo_sanguineo'] ?? '') ?>"></div>
        <div class="col-md-8 mb-3"><label class="form-label">Alergias Conhecidas:</label><input type="text" class="form-control" value="<?= htmlspecialchars($paciente['alergias_conhecidas'] ?? '') ?>"></div>
    </div>
    <div class="row">
        <div class="col-md-12 mb-3">
            <label class="form-label fw-bold">Condições de Saúde Registradas:</label>
            <?php
            $condicoes = [];
            // Condições Metabólicas e Cardiovasculares
            if (($paciente['is_diabetico'] ?? 0) == 1) $condicoes[] = 'Diabético';
            if (($paciente['is_cardiaco'] ?? 0) == 1) $condicoes[] = 'Cardíaco';
            if (($paciente['is_hipertenso'] ?? 0) == 1) $condicoes[] = 'Hipertenso';
            // Patologias Respiratórias e Hábito (NOVOS)
            if (($paciente['is_asma'] ?? 0) == 1) $condicoes[] = 'Asma';
            if (($paciente['is_bronquite_cronica'] ?? 0) == 1) $condicoes[] = 'Bronquite Crônica';
            if (($paciente['is_dpoc'] ?? 0) == 1) $condicoes[] = 'DPOC/Enfisema';
            if (($paciente['is_rinite_sinusite'] ?? 0) == 1) $condicoes[] = 'Rinite/Sinusite';
            if (($paciente['is_fumante'] ?? 0) == 1) $condicoes[] = 'Fumante';
            
            // START: NOVAS VIROSES
            if (($paciente['is_dengue'] ?? 0) == 1) $condicoes[] = 'Dengue';
            if (($paciente['is_chikungunya'] ?? 0) == 1) $condicoes[] = 'Chikungunya';
            if (($paciente['is_zika'] ?? 0) == 1) $condicoes[] = 'Zika';
            if (($paciente['is_covid19'] ?? 0) == 1) $condicoes[] = 'COVID-19';
            // END: NOVAS VIROSES
            
            if (empty($condicoes)): ?>
                <p class="form-control-plaintext">Nenhuma condição de saúde registrada.</p>
            <?php else: ?>
                <p class="form-control-plaintext">
                    <?php 
                    $viroses = ['Dengue', 'Chikungunya', 'Zika', 'COVID-19'];
                    foreach($condicoes as $c): 
                        $badge_class = in_array($c, $viroses) ? 'bg-danger' : 'bg-warning text-dark';
                    ?>
                        <span class="badge <?= $badge_class ?> me-1"><?= $c ?></span>
                    <?php endforeach; ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</fieldset>

<div class="mt-4">
    <a href="editar_paciente.php?id=<?= $paciente['id'] ?>" class="btn btn-primary"><i class="fas fa-pencil"></i> Editar Cadastro</a>
    <a href="excluir_paciente.php?id=<?= $paciente['id'] ?>" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja excluir este paciente? Esta ação não pode ser desfeita.');"><i class="fas fa-trash"></i> Excluir Cadastro</a>
</div>

<?php require_once '../footer.php'; ?>