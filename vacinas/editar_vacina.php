<?php

require_once '../conexao.php';

$erro = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)$_POST['id'];
    $paciente_id = (int)$_POST['paciente_id'];
    $data_aplicacao = mysqli_real_escape_string($conexao, $_POST['data_aplicacao']);
    $nome_vacina = mysqli_real_escape_string($conexao, $_POST['nome_vacina']);
    $marca = mysqli_real_escape_string($conexao, $_POST['marca']);
    $dose = mysqli_real_escape_string($conexao, $_POST['dose']);
    $lote = mysqli_real_escape_string($conexao, $_POST['lote']);

    $sql = "UPDATE vacinas SET data_aplicacao = ?, nome_vacina = ?, marca = ?, dose = ?, lote = ? WHERE id = ?";
    
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, "sssssi", $data_aplicacao, $nome_vacina, $marca, $dose, $lote, $id);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: ../pacientes/historico_vacinas.php?paciente_id=$paciente_id&status=vacina_editada");
        exit();
    } else {
        $erro = "Erro ao atualizar vacina: " . mysqli_error($conexao);
    }
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../pacientes/pacientes.php");
    exit();
}
$id = (int)$_GET['id'];
$sql_vacina = "SELECT v.*, p.nome as nome_paciente FROM vacinas v JOIN pacientes p ON v.paciente_id = p.id WHERE v.id = $id";
$resultado = mysqli_query($conexao, $sql_vacina);
$vacina = mysqli_fetch_assoc($resultado);
if (!$vacina) {
    header("Location: ../pacientes/pacientes.php");
    exit();
}

require_once '../header.php';
?>

<h1>Editar Registro de Vacina de: <?= htmlspecialchars($vacina['nome_paciente']) ?></h1>
<hr>

<?php if (isset($erro)): ?>
    <div class="alert alert-danger" role="alert"><?= $erro ?></div>
<?php endif; ?>

<form action="editar_vacina.php" method="POST">
    <input type="hidden" name="id" value="<?= $vacina['id'] ?>">
    <input type="hidden" name="paciente_id" value="<?= $vacina['paciente_id'] ?>">

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="nome_vacina" class="form-label">Nome da Vacina:</label>
            <input type="text" class="form-control" id="nome_vacina" name="nome_vacina" value="<?= htmlspecialchars($vacina['nome_vacina'] ?? '') ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label for="marca" class="form-label">Marca / Fabricante:</label>
            <input type="text" class="form-control" id="marca" name="marca" value="<?= htmlspecialchars($vacina['marca'] ?? '') ?>">
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="data_aplicacao" class="form-label">Data de Aplicação:</label>
            <input type="date" class="form-control" id="data_aplicacao" name="data_aplicacao" value="<?= $vacina['data_aplicacao'] ?>" required>
        </div>
        <div class="col-md-4 mb-3">
            <label for="dose" class="form-label">Dose:</label>
            <input type="text" class="form-control" id="dose" name="dose" value="<?= htmlspecialchars($vacina['dose'] ?? '') ?>" placeholder="Ex: 1ª Dose, Dose Única...">
        </div>
        <div class="col-md-4 mb-3">
            <label for="lote" class="form-label">Lote:</label>
            <input type="text" class="form-control" id="lote" name="lote" value="<?= htmlspecialchars($vacina['lote'] ?? '') ?>">
        </div>
    </div>
    
    <div class="mt-3">
        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        <a href="../pacientes/historico_vacinas.php?paciente_id=<?= $vacina['paciente_id'] ?>" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

<?php require_once '../footer.php'; ?>