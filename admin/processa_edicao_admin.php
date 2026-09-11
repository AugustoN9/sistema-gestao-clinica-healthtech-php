<?php
require_once '../auth.php';
proteger_pagina(['admin']); 
require_once '../conexao.php';

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header('Location: gerenciar_equipe_adm.php');
    exit();
}

$id_profissional = $_POST['id_profissional'] ?? null;
$acao = $_POST['acao'] ?? '';
$redirect_url = "editar_admin.php?id=$id_profissional";

if (!$id_profissional || !is_numeric($id_profissional)) {
    header("Location: gerenciar_equipe_adm.php?erro=" . urlencode("ID do profissional inválido."));
    exit();
}

mysqli_begin_transaction($conexao);

try {
    if ($acao === 'salvar_dados') {
        // ==========================================
        // AÇÃO: SALVAR DADOS PESSOAIS E PROFISSIONAIS
        // ==========================================
        $nome_completo = trim($_POST['nome_completo'] ?? '');
        $cpf = trim($_POST['cpf'] ?? '');
        $data_nascimento = trim($_POST['data_nascimento'] ?? '');
        $cargo = trim($_POST['cargo'] ?? '');

        if (empty($nome_completo) || empty($cpf) || empty($data_nascimento) || empty($cargo)) {
            throw new Exception("Todos os campos obrigatórios devem ser preenchidos.");
        }

        // 1. Atualizar a tabela profissionais_administrativos
        $sql_pa = "UPDATE profissionais_administrativos 
                   SET nome_completo = ?, cpf = ?, data_nascimento = ?, cargo = ? 
                   WHERE id = ?";
        $stmt_pa = mysqli_prepare($conexao, $sql_pa);
        mysqli_stmt_bind_param($stmt_pa, "ssssi", $nome_completo, $cpf, $data_nascimento, $cargo, $id_profissional);
        mysqli_stmt_execute($stmt_pa);

        // 2. Atualizar o nome e tipo (cargo) na tabela usuarios (essencial)
        $sql_u = "UPDATE usuarios 
                  SET nome_completo = ?, tipo_usuario = ? 
                  WHERE id_referencia = ? AND tipo_usuario = (SELECT cargo FROM profissionais_administrativos WHERE id = ?)"; // Usa sub-consulta para pegar o cargo antigo se necessário, ou confiar na lógica
        
        // Versão mais segura: assumir que o cargo na tabela 'usuarios' deve ser atualizado para o novo cargo
        $sql_u = "UPDATE usuarios SET nome_completo = ?, tipo_usuario = ? WHERE id_referencia = ? AND tipo_usuario = ?";
        
        // Aqui, precisamos do tipo_usuario ANTERIOR para garantir que estamos a atualizar o registo correto
        // Uma solução mais simples e robusta (sem precisar saber o cargo antigo) é:
        $sql_u = "UPDATE usuarios SET nome_completo = ?, tipo_usuario = ? WHERE id_referencia = ? AND tipo_usuario IN ('gerente', 'supervisor', 'auxiliar_adm', 'admin')";
        
        // A forma mais direta (atualiza nome e cargo/tipo)
        $sql_u = "UPDATE usuarios SET nome_completo = ?, tipo_usuario = ? WHERE id_referencia = ?";
        $stmt_u = mysqli_prepare($conexao, $sql_u);
        mysqli_stmt_bind_param($stmt_u, "ssi", $nome_completo, $cargo, $id_profissional);
        mysqli_stmt_execute($stmt_u);

        $sucesso_msg = "Dados pessoais e profissionais atualizados com sucesso!";
        
    } elseif ($acao === 'salvar_conta') {
        // ==========================================
        // AÇÃO: SALVAR DADOS DA CONTA (EMAIL/SENHA)
        // ==========================================
        $email = trim($_POST['email'] ?? '');
        $nova_senha = $_POST['nova_senha'] ?? '';
        $usuario_id = $_POST['usuario_id'] ?? null; // ID da tabela usuarios

        if (empty($email)) {
            throw new Exception("O email de login é obrigatório.");
        }

        if (!$usuario_id) {
             throw new Exception("Conta de usuário de login não encontrada. Não é possível alterar email/senha.");
        }
        
        // 1. Atualizar Email
        $sql_u = "UPDATE usuarios SET email = ? WHERE id = ?";
        $stmt_u = mysqli_prepare($conexao, $sql_u);
        mysqli_stmt_bind_param($stmt_u, "si", $email, $usuario_id);
        mysqli_stmt_execute($stmt_u);

        // 2. Atualizar Senha (se fornecida)
        if (!empty($nova_senha)) {
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $sql_s = "UPDATE usuarios SET senha = ? WHERE id = ?";
            $stmt_s = mysqli_prepare($conexao, $sql_s);
            mysqli_stmt_bind_param($stmt_s, "si", $senha_hash, $usuario_id);
            mysqli_stmt_execute($stmt_s);
        }

        $sucesso_msg = "Dados da conta (Email/Senha) atualizados com sucesso!";
        
    } else {
        throw new Exception("Ação inválida.");
    }
    
    // Commit da transação e redirecionamento de sucesso
    mysqli_commit($conexao);
    header("Location: $redirect_url&sucesso=" . urlencode($sucesso_msg));
    exit();

} catch (Exception $e) {
    // Rollback da transação e redirecionamento de erro
    mysqli_rollback($conexao);
    header("Location: $redirect_url&erro=" . urlencode($e->getMessage()));
    exit();
}