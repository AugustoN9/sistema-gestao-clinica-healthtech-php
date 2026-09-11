<?php
require_once '../auth.php';
proteger_pagina(['admin']); 
require_once '../conexao.php';

// 1. Obter e validar o ID do profissional administrativo
$id_profissional = $_GET['id'] ?? null;

if (!$id_profissional || !is_numeric($id_profissional)) {
    header("Location: gerenciar_equipe_adm.php?erro=" . urlencode("ID do profissional inválido ou não fornecido."));
    exit();
}

// 2. Iniciar a Transação
mysqli_begin_transaction($conexao);
$sucesso = true;

try {
    // Para garantir a exclusão correta na tabela 'usuarios', precisamos do tipo de usuário (cargo)
    // para usar no WHERE clause do DELETE.

    // 2a. Buscar o Cargo/Tipo antes de excluir o profissional
    $sql_cargo = "SELECT cargo FROM profissionais_administrativos WHERE id = ?";
    $stmt_cargo = mysqli_prepare($conexao, $sql_cargo);
    mysqli_stmt_bind_param($stmt_cargo, "i", $id_profissional);
    mysqli_stmt_execute($stmt_cargo);
    $resultado_cargo = mysqli_stmt_get_result($stmt_cargo);
    $profissional_info = mysqli_fetch_assoc($resultado_cargo);

    if (!$profissional_info) {
        throw new Exception("Profissional não encontrado para exclusão.");
    }
    
    $cargo = $profissional_info['cargo'];

    // 3. EXCLUIR REGISTRO da tabela 'usuarios'
    // Usa id_referencia e tipo_usuario (cargo) para garantir que excluímos a conta correta.
    $sql_u = "DELETE FROM usuarios WHERE id_referencia = ? AND tipo_usuario = ?";
    $stmt_u = mysqli_prepare($conexao, $sql_u);
    mysqli_stmt_bind_param($stmt_u, "is", $id_profissional, $cargo);
    
    if (!mysqli_stmt_execute($stmt_u)) {
        throw new Exception("Erro ao excluir conta de usuário.");
    }

    // 4. EXCLUIR REGISTRO da tabela 'profissionais_administrativos'
    $sql_pa = "DELETE FROM profissionais_administrativos WHERE id = ?";
    $stmt_pa = mysqli_prepare($conexao, $sql_pa);
    mysqli_stmt_bind_param($stmt_pa, "i", $id_profissional);
    
    if (!mysqli_stmt_execute($stmt_pa)) {
        throw new Exception("Erro ao excluir registro administrativo.");
    }

    // 5. Commit se tudo correu bem
    mysqli_commit($conexao);
    
    header("Location: gerenciar_equipe_adm.php?sucesso=" . urlencode("Profissional ($cargo) excluído com sucesso!"));
    exit();

} catch (Exception $e) {
    // 6. Rollback em caso de erro
    mysqli_rollback($conexao);
    
    header("Location: gerenciar_equipe_adm.php?erro=" . urlencode($e->getMessage()));
    exit();
}
?>