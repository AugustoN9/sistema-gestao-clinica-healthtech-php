<?php


require_once '../conexao.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Para manter a integridade, você pode querer apagar os registos relacionados
    // (consultas, vacinas, utilizadores) antes de apagar o paciente.
    // Por agora, vamos apenas apagar o paciente.

    $sql = "DELETE FROM pacientes WHERE id = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: pacientes.php?status=excluido_sucesso");
        exit();
    } else {
        header("Location: pacientes.php?status=erro_excluir");
        exit();
    }
} else {
    header("Location: pacientes.php");
    exit();
}
?>