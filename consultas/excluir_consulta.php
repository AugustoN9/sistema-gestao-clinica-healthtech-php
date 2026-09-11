<?php


require_once '../conexao.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "DELETE FROM consultas WHERE id = ?";
    
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: consultas.php?status=excluido_sucesso");
        exit();
    } else {
        header("Location: consultas.php?status=erro_excluir");
        exit();
    }
} else {
    header("Location: consultas.php");
    exit();
}
?>