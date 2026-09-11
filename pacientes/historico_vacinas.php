<?php
require_once '../conexao.php';
require_once '../calendario_vacinal.php';

// (Adicionado session_start() para a proteção)
session_start();
// (É altamente recomendado adicionar a proteção de página aqui)
// require_once '../auth.php';
// proteger_pagina(['gerente', 'medico']);

if (!isset($_GET['paciente_id']) || !is_numeric($_GET['paciente_id'])) {
    header("Location: pacientes.php");
    exit();
}
$paciente_id = (int)$_GET['paciente_id'];

// SQL para buscar paciente (JÁ ATUALIZADO com a correção do bug da idade)
$sql_paciente = "SELECT nome, data_nascimento, genero, is_gestante FROM pacientes WHERE id = ?";
$stmt_paciente = mysqli_prepare($conexao, $sql_paciente);
mysqli_stmt_bind_param($stmt_paciente, "i", $paciente_id);
mysqli_stmt_execute($stmt_paciente);
$resultado_paciente = mysqli_stmt_get_result($stmt_paciente);
$paciente = mysqli_fetch_assoc($resultado_paciente);

if (!$paciente) { header("Location: pacientes.php"); exit(); }
$nome_paciente = $paciente['nome'];

// Lógica de Perfil e Idade (JÁ ATUALIZADA com Gestante e correção de bug)
$idade = 'N/D';
$perfil = 'N/D';
if (!empty($paciente['data_nascimento'])) {
    $data_nasc = new DateTime($paciente['data_nascimento']);
    $hoje = new DateTime('now');
    if ($data_nasc->format('Y') > 0 && $data_nasc->format('Y') < date('Y')) {
        $idade = $data_nasc->diff($hoje)->y;
        if (isset($paciente['genero']) && $paciente['genero'] == 'Feminino' && isset($paciente['is_gestante']) && $paciente['is_gestante'] == 1) {
            $perfil = 'Gestante';
        } 
        elseif ($idade <= 9) { $perfil = 'Criança'; } 
        elseif ($idade <= 19) { $perfil = 'Adolescente'; } 
        elseif ($idade <= 24) { $perfil = 'Jovem'; }
        elseif ($idade <= 59) { $perfil = 'Adulto'; } 
        else { $perfil = 'Idoso'; }
    }
}
// Fim da lógica de perfil

$calendario_recomendado = getCalendarioVacinal($perfil);

// Busca vacinas aplicadas (JÁ ATUALIZADO para prepared statement)
$sql_vacinas = "SELECT * FROM vacinas WHERE paciente_id = ? ORDER BY data_aplicacao DESC";
$stmt_vacinas = mysqli_prepare($conexao, $sql_vacinas);
mysqli_stmt_bind_param($stmt_vacinas, "i", $paciente_id);
mysqli_stmt_execute($stmt_vacinas);
$resultado_vacinas = mysqli_stmt_get_result($stmt_vacinas);
$vacinas_aplicadas = [];
if($resultado_vacinas){
    while($row = mysqli_fetch_assoc($resultado_vacinas)){
        $vacinas_aplicadas[] = strtolower($row['nome_vacina']);
    }
}

// Lógica de Vacinas Pendentes (existente)
$vacinas_pendentes = [];
foreach ($calendario_recomendado as $vacina_rec) {
    if (is_array($vacina_rec) && isset($vacina_rec['vacina'])) {
        $encontrou = false;
        foreach ($vacinas_aplicadas as $vacina_apl) {
            if (stripos($vacina_apl, strtolower($vacina_rec['vacina'])) !== false || stripos(strtolower($vacina_rec['vacina']), $vacina_apl) !== false) {
                $encontrou = true;
                break;
            }
        }
        if (!$encontrou) {
            $vacinas_pendentes[] = $vacina_rec; // Adiciona o array completo
        }
    }
}

require_once '../header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1>Histórico de Vacinas de <?= htmlspecialchars($nome_paciente) ?></h1>
        <p class="lead mb-0">Perfil do Paciente: <strong><?= $perfil ?></strong> (<?= $idade ?> anos)</p>
    </div>
    <a href="pacientes.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i> Voltar para Pacientes
    </a>
</div>
<hr>
<div class="row">
    <div class="col-md-7">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Vacinas Aplicadas</h4>
            <a href="../vacinas/adicionar_vacina.php?paciente_id=<?= $paciente_id ?>" class="btn btn-success"><i class="fas fa-plus"></i> Registrar Vacina (Manual)</a>
        </div>
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr><th>Data</th><th>Vacina</th><th>Dose</th><th>Lote</th><th>Ações</th></tr>
            </thead>
            <tbody>
                <?php if ($resultado_vacinas && mysqli_num_rows($resultado_vacinas) > 0): mysqli_data_seek($resultado_vacinas, 0); ?>
                    <?php while ($vacina = mysqli_fetch_assoc($resultado_vacinas)): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($vacina['data_aplicacao'])) ?></td>
                            <td><?= htmlspecialchars($vacina['nome_vacina'] ?? '') ?></td>
                            <td><?= htmlspecialchars($vacina['dose'] ?? '') ?></td>
                            <td><?= htmlspecialchars($vacina['lote'] ?? '') ?></td>
                            <td>
                                <a href="../vacinas/editar_vacina.php?id=<?= $vacina['id'] ?>" class="btn btn-warning btn-sm">Editar</a>
                                <a href="../vacinas/excluir_vacina.php?id=<?= $vacina['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza?');">Excluir</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center">Nenhuma vacina registrada.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="col-md-5">
        <h4>Vacinas Pendentes Recomendadas</h4>
        <p class="text-muted small">Clique em uma vacina abaixo para registrá-la rapidamente.</p>
        <?php if (!empty($vacinas_pendentes)): ?>
            <div class="list-group"> <?php foreach($vacinas_pendentes as $vacina): ?>
                    <?php
                        // Prepara os dados para a URL
                        $vacina_nome = $vacina['vacina'] ?? '';
                        $vacina_dose = $vacina['dose'] ?? '';
                        $vacina_descricao = $vacina['previne'] ?? ($vacina['doencas'] ?? 'Descrição indisponível');
                        
                        // O link que pré-preenche o formulário
                        $link = "../vacinas/adicionar_vacina.php?" . http_build_query([
                            'paciente_id' => $paciente_id,
                            'nome_vacina' => $vacina_nome,
                            'dose' => $vacina_dose
                        ]);
                    ?>
                    <a href="<?= $link ?>" class="list-group-item list-group-item-action">
                        <strong><?= htmlspecialchars($vacina_nome) ?></strong>
                        <br><small class="text-muted"><?= htmlspecialchars($vacina_descricao) ?></small>
                        <?php if($vacina_dose): ?>
                            <br><small class="text-info">Dose Recomendada: <?= htmlspecialchars($vacina_dose) ?></small>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-success">Parabéns! O calendário de vacinas para este perfil está em dia.</div>
        <?php endif; ?>
    </div>
    </div>

<?php require_once '../footer.php'; ?>