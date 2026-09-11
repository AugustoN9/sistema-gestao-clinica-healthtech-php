<?php
// Adiciona session_start() para garantir que temos o ID do usuário
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../conexao.php';

$erro = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $paciente_id = (int)$_POST['paciente_id'];
    $data_aplicacao = mysqli_real_escape_string($conexao, $_POST['data_aplicacao']);
    $nome_vacina = mysqli_real_escape_string($conexao, $_POST['nome_vacina']);
    $marca = mysqli_real_escape_string($conexao, $_POST['marca']);
    $dose = mysqli_real_escape_string($conexao, $_POST['dose']);
    $lote = mysqli_real_escape_string($conexao, $_POST['lote']);
    
    // (Lógica de salvar o ID do responsável já implementada)
    $usuario_id_registrou = (int)$_SESSION['usuario_id'];

    $sql = "INSERT INTO vacinas (paciente_id, data_aplicacao, nome_vacina, marca, dose, lote, responsavel_imunizacao_enfermagem) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, "isssssi", $paciente_id, $data_aplicacao, $nome_vacina, $marca, $dose, $lote, $usuario_id_registrou);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: ../pacientes/historico_vacinas.php?paciente_id=$paciente_id&status=vacina_sucesso");
        exit();
    } else {
        $erro = "Erro ao adicionar vacina: " . mysqli_error($conexao);
    }
}

// Busca o paciente para exibir o nome
if (!isset($_GET['paciente_id'])) {
    header("Location: ../pacientes/pacientes.php");
    exit();
}
$paciente_id = (int)$_GET['paciente_id'];

// ================== LÓGICA DE PRÉ-PREENCHIMENTO ==================
// (Pega os dados da URL, se existirem)
$nome_vacina_preenchido = $_GET['nome_vacina'] ?? '';
$dose_preenchida = $_GET['dose'] ?? '';
// (Define a data de hoje por padrão)
$data_hoje = date('Y-m-d');
// ================== FIM DA LÓGICA ==================

// Busca o nome do paciente (JÁ ATUALIZADO para ser seguro)
$sql_paciente = "SELECT nome FROM pacientes WHERE id = ?";
$stmt_paciente = mysqli_prepare($conexao, $sql_paciente);
mysqli_stmt_bind_param($stmt_paciente, "i", $paciente_id);
mysqli_stmt_execute($stmt_paciente);
$resultado_paciente = mysqli_stmt_get_result($stmt_paciente);
$paciente = mysqli_fetch_assoc($resultado_paciente);

if (!$paciente) {
    header("Location: ../pacientes/pacientes.php");
    exit();
}
$nome_paciente = $paciente['nome'];

require_once '../header.php';
?>

<h1>Registrar Vacina para: <?= htmlspecialchars($nome_paciente) ?></h1>
<hr>

<?php if (isset($erro)): ?>
    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($erro) ?></div>
<?php endif; ?>

<form action="adicionar_vacina.php" method="POST">
    <input type="hidden" name="paciente_id" value="<?= $paciente_id ?>">

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="nome_vacina" class="form-label">Nome da Vacina:</label>
            <input type="text" class="form-control" id="nome_vacina" name="nome_vacina" 
                   value="<?= htmlspecialchars($nome_vacina_preenchido) ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label for="marca" class="form-label">Marca / Fabricante:</label>
            <input type="text" class="form-control" id="marca" name="marca">
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="data_aplicacao" class="form-label">Data de Aplicação:</label>
            <input type="date" class="form-control" id="data_aplicacao" name="data_aplicacao" 
                   value="<?= $data_hoje ?>" required>
        </div>
        <div class="col-md-4 mb-3">
            <label for="dose" class="form-label">Dose:</label>
            <input type="text" class="form-control" id="dose" name="dose" 
                   value="<?= htmlspecialchars($dose_preenchida) ?>" placeholder="Ex: 1ª Dose, Dose Única...">
        </div>
        <div class="col-md-4 mb-3">
            <label for="lote" class="form-label">Lote:</label>
            <input type="text" class="form-control" id="lote" name="lote">
        </div>
    </div>
    
    <div class="mt-3">
        <button type="submit" class="btn btn-primary">Salvar Registro</button>
        <a href="../pacientes/historico_vacinas.php?paciente_id=<?= $paciente_id ?>" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
<?php require_once '../footer.php'; ?>