<?php

require_once '../conexao.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Primeiro, descobrir para qual paciente esta vacina pertence para poder redirecionar
    $sql_get_paciente = "SELECT paciente_id FROM vacinas WHERE id = ?";
    $stmt_get = mysqli_prepare($conexao, $sql_get_paciente);
    mysqli_stmt_bind_param($stmt_get, "i", $id);
    mysqli_stmt_execute($stmt_get);
    $resultado = mysqli_stmt_get_result($stmt_get);
    
    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $vacina = mysqli_fetch_assoc($resultado);
        $paciente_id = $vacina['paciente_id'];

        // Agora, apagar a vacina
        $sql_delete = "DELETE FROM vacinas WHERE id = ?";
        $stmt_del = mysqli_prepare($conexao, $sql_delete);
        mysqli_stmt_bind_param($stmt_del, "i", $id);

        if (mysqli_stmt_execute($stmt_del)) {
            header("Location: ../pacientes/historico_vacinas.php?paciente_id=$paciente_id&status=vacina_excluida");
            exit();
        }
    }
}

// Se algo deu errado, redireciona para a lista geral de pacientes.
header("Location: ../pacientes/pacientes.php");
exit();
?>