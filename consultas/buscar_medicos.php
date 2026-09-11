<?php


header('Content-Type: application/json');

require_once '../conexao.php';

$especialidade = $_GET['especialidade'] ?? '';

if (empty($especialidade)) {
    echo json_encode([]);
    exit();
}

$sql = "SELECT id, nome_completo FROM medicos WHERE especialidade = ? ORDER BY nome_completo ASC";
$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, "s", $especialidade);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

$medicos = [];
while ($medico = mysqli_fetch_assoc($resultado)) {
    $medicos[] = $medico;
}

echo json_encode($medicos);
mysqli_close($conexao);
?>