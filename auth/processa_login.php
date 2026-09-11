<?php
require_once '../conexao.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Sanitizar e obter dados
    $email_digitado = trim($_POST['email'] ?? '');
    $senha_digitada = $_POST['senha'] ?? '';
    $erro = '';

    if (empty($email_digitado) || empty($senha_digitada)) {
        $erro = 'Por favor, preencha o email e a senha.';
    } else {
        $email_seguro = mysqli_real_escape_string($conexao, $email_digitado);

        // 2. Buscar o usuário
        // CORRIGIDO: Mudança de 'senha_hash' para 'senha'
        $sql = "SELECT id, senha, tipo_usuario, id_referencia, nome_completo 
                FROM usuarios 
                WHERE email = ?"; 
        
        $stmt = mysqli_prepare($conexao, $sql);
        
        // Verifica se a preparação da query falhou (embora improvável após a correção da coluna)
        if ($stmt === false) {
             $erro = 'Erro na preparação da consulta: ' . mysqli_error($conexao);
        } else {
            mysqli_stmt_bind_param($stmt, "s", $email_seguro);
            mysqli_stmt_execute($stmt);
            $resultado = mysqli_stmt_get_result($stmt);
            $usuario = mysqli_fetch_assoc($resultado);
    
            if ($usuario) {
                // 3. O email existe: Verificar senha
                // A função password_verify() ainda deve ser usada, pois o valor em 'senha' é o hash
                if (password_verify($senha_digitada, $usuario['senha'])) {
                    // Login bem-sucedido
                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['usuario_nome'] = $usuario['nome_completo'];
                    $_SESSION['usuario_tipo'] = $usuario['tipo_usuario'];
                    $_SESSION['id_referencia'] = $usuario['id_referencia'];
    
                    // Redireciona com base no tipo de utilizador
                    if ($usuario['tipo_usuario'] == 'admin') {
                        header('Location: ../admin_painel.php');
                    } elseif  ($usuario['tipo_usuario'] == 'gerente') { // Nota: Corrigi para 'usuario_tipo' se o erro for do seu lado
                         header('Location: ../pacientes/pacientes.php');
                    } elseif ($usuario['tipo_usuario'] == 'paciente') {
                        header('Location: ../area_paciente.php');
                    } elseif ($usuario['tipo_usuario'] == 'medico') {
                        header('Location: ../area_medico.php');
                    } elseif ($usuario['tipo_usuario'] == 'enfermagem') {
                        header('Location: ../area_enfermagem.php');
                    }
                    exit();
                } else {
                    // Senha incorreta
                    $erro = 'Senha incorreta. Tente novamente.';
                }
            } else {
                // 4. O email não existe: Sugerir cadastro
                $erro = 'Email não encontrado. Sugerimos que crie uma conta.';
            }
        }
    }
    
    // Em caso de erro
    $_SESSION['login_erro'] = $erro;
    $_SESSION['email_digitado'] = $email_digitado; 
    header('Location: login.php');
    exit();
}

// Se a página for acedida sem POST, redireciona para o login
header('Location: login.php');
exit();
?>