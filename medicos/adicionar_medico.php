<?php
 

require_once '../conexao.php';

$erro = null;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome_completo = mysqli_real_escape_string($conexao, $_POST['nome_completo']);
    $crm = mysqli_real_escape_string($conexao, $_POST['crm']);
    $especialidade = mysqli_real_escape_string($conexao, $_POST['especialidade']);

    $sql = "INSERT INTO medicos (nome_completo, crm, especialidade) VALUES (?, ?, ?)";
    
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $nome_completo, $crm, $especialidade);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: medicos.php?status=sucesso");
        exit();
    } else {
        $erro = "Erro ao cadastrar médico: " . mysqli_error($conexao);
    }
}

require_once '../header.php';
?>

<h1>Adicionar Novo Médico</h1>
<hr>

<?php if ($erro): ?>
    <div class="alert alert-danger" role="alert"><?= $erro ?></div>
<?php endif; ?>

<form action="adicionar_medico.php" method="POST">
    <div class="row">
        <div class="col-md-8 mb-3">
            <label for="nome_completo" class="form-label">Nome Completo:</label>
            <input type="text" class="form-control" id="nome_completo" name="nome_completo" required>
        </div>
        <div class="col-md-4 mb-3">
            <label for="crm" class="form-label">CRM:</label>
            <input type="text" class="form-control" id="crm" name="crm" required>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12 mb-3">
            <label for="especialidade" class="form-label">Especialidade:</label>
            <input type="text" class="form-control" id="especialidade" name="especialidade" required>
        </div>
    </div>
    <div class="mt-3">
        <button type="submit" class="btn btn-primary">Salvar</button>
        <a href="medicos.php" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

<?php
require_once '../footer.php';
?>