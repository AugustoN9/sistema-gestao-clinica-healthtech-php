<?php


require_once '../conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)$_POST['id'];
    $paciente_id = (int)$_POST['paciente_id'];
    $medico_id = (int)$_POST['medico_id'];
    $data_consulta = mysqli_real_escape_string($conexao, $_POST['data_consulta']);
    $horario_consulta = mysqli_real_escape_string($conexao, $_POST['horario_consulta']);
    $motivo = mysqli_real_escape_string($conexao, $_POST['motivo']);
    $observacoes = mysqli_real_escape_string($conexao, $_POST['observacoes']);
    $especialidade = mysqli_real_escape_string($conexao, $_POST['especialidade']);

    $sql = "UPDATE consultas SET 
                paciente_id = ?, medico_id = ?, data_consulta = ?, horario_consulta = ?, 
                motivo = ?, especialidade = ?, observacoes = ? 
            WHERE id = ?";
    
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, "iisssssi", $paciente_id, $medico_id, $data_consulta, $horario_consulta, $motivo, $especialidade, $observacoes, $id);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: detalhes_consulta.php?id=$id&status=sucesso");
        exit();
    } else {
        $erro = "Erro ao atualizar consulta: " . mysqli_error($conexao);
    }
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: consultas.php");
    exit();
}
$id = (int)$_GET['id'];
$sql_consulta = "SELECT * FROM consultas WHERE id = $id";
$resultado_consulta = mysqli_query($conexao, $sql_consulta);
$consulta = mysqli_fetch_assoc($resultado_consulta);
if (!$consulta) {
    header("Location: consultas.php");
    exit();
}

$pacientes_resultado = mysqli_query($conexao, "SELECT id, nome FROM pacientes ORDER BY nome ASC");
$especialidades_resultado = mysqli_query($conexao, "SELECT DISTINCT especialidade FROM medicos ORDER BY especialidade ASC");

require_once '../header.php';
?>

<h1>Editar Consulta #<?= $id ?></h1>
<hr>

<?php if (isset($erro)): ?>
    <div class="alert alert-danger" role="alert"><?= $erro ?></div>
<?php endif; ?>

<form action="editar_consulta.php" method="POST">
    <input type="hidden" name="id" value="<?= $consulta['id'] ?>">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="paciente_id" class="form-label">Paciente:</label>
            <select class="form-select" id="paciente_id" name="paciente_id" required>
                <?php while ($paciente = mysqli_fetch_assoc($pacientes_resultado)): ?>
                    <option value="<?= $paciente['id'] ?>" <?= ($paciente['id'] == $consulta['paciente_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($paciente['nome']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label for="especialidade" class="form-label">Especialidade:</label>
            <select class="form-select" id="especialidade" name="especialidade" required>
                <?php while ($especialidade = mysqli_fetch_assoc($especialidades_resultado)): ?>
                    <option value="<?= htmlspecialchars($especialidade['especialidade']) ?>" <?= ($especialidade['especialidade'] == $consulta['especialidade']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($especialidade['especialidade']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="medico_id" class="form-label">Médico:</label>
            <select class="form-select" id="medico_id" name="medico_id" required>
                <option value="">Aguardando especialidade...</option>
            </select>
        </div>
        <div class="col-md-3 mb-3">
            <label for="data_consulta" class="form-label">Data da Consulta:</label>
            <input type="date" class="form-control" id="data_consulta" name="data_consulta" value="<?= htmlspecialchars($consulta['data_consulta']) ?>" required>
        </div>
        <div class="col-md-3 mb-3">
            <label for="horario_consulta" class="form-label">Horário:</label>
            <input type="time" class="form-control" id="horario_consulta" name="horario_consulta" value="<?= htmlspecialchars($consulta['horario_consulta']) ?>" required>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12 mb-3">
            <label for="motivo" class="form-label">Motivo da Consulta:</label>
            <textarea class="form-control" id="motivo" name="motivo" rows="3"><?= htmlspecialchars($consulta['motivo']) ?></textarea>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12 mb-3">
            <label for="observacoes" class="form-label">Observações/Diagnóstico:</label>
            <textarea class="form-control" id="observacoes" name="observacoes" rows="3"><?= htmlspecialchars($consulta['observacoes'] ?? '') ?></textarea>
        </div>
    </div>
    
    <div class="mt-3">
        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        <a href="detalhes_consulta.php?id=<?= $consulta['id'] ?>" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

<script>
function carregarMedicos(especialidade, medicoIdParaSelecionar = null) {
    const medicoSelect = document.getElementById('medico_id');
    medicoSelect.innerHTML = '<option value="">Carregando...</option>';
    medicoSelect.disabled = true;
    if (especialidade) {
        fetch('buscar_medicos.php?especialidade=' + encodeURIComponent(especialidade))
            .then(response => response.json())
            .then(medicos => {
                medicoSelect.innerHTML = '<option value="">Selecione um médico...</option>';
                medicos.forEach(medico => {
                    const option = document.createElement('option');
                    option.value = medico.id;
                    option.textContent = medico.nome_completo;
                    if (medico.id == medicoIdParaSelecionar) {
                        option.selected = true;
                    }
                    medicoSelect.appendChild(option);
                });
                medicoSelect.disabled = false;
            });
    } else {
        medicoSelect.innerHTML = '<option value="">Aguardando especialidade...</option>';
    }
}
document.getElementById('especialidade').addEventListener('change', function() {
    carregarMedicos(this.value);
});
document.addEventListener('DOMContentLoaded', function() {
    const especialidadeInicial = document.getElementById('especialidade').value;
    const medicoInicialId = <?= $consulta['medico_id'] ?>;
    if (especialidadeInicial) {
        carregarMedicos(especialidadeInicial, medicoInicialId);
    }
});
</script>

<?php
require_once '../footer.php';
?>