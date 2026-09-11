<?php
require_once '../conexao.php';
session_start(); // Garante que podemos usar a sessão para feedback futuro

if ($_SERVER["REQUEST_METHOD"] != "POST") { exit(); }

// Dados comuns
$tipo_usuario = $_POST['tipo_usuario'] ?? '';
$nome_completo = trim($_POST['nome_completo'] ?? '');
$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';

// Validação básica
if (empty($tipo_usuario) || empty($nome_completo) || empty($email) || empty($senha)) {
    header("Location: registro.php?erro=" . urlencode("Todos os campos de login e tipo de usuário são obrigatórios."));
    exit();
}

// Validação de email duplicado (essencial)
$sql_check = "SELECT id FROM usuarios WHERE email = ?";
$stmt_check = mysqli_prepare($conexao, $sql_check);
mysqli_stmt_bind_param($stmt_check, "s", $email);
mysqli_stmt_execute($stmt_check);
if (mysqli_stmt_get_result($stmt_check)->num_rows > 0) {
    header("Location: registro.php?erro=" . urlencode("Este email já está a ser utilizado."));
    exit();
}

mysqli_begin_transaction($conexao);
try {
    $id_referencia = 0;

    if ($tipo_usuario === 'paciente') {
        // ============ LÓGICA DO PACIENTE ============
        // ATENÇÃO: Nomes dos campos adaptados do front-end que forneci (registro.php)
        $cpf = trim($_POST['cpf'] ?? $_POST['pac_cpf_input'] ?? ''); 
        $data_nascimento = trim($_POST['data_nascimento'] ?? $_POST['pac_data_nascimento_input'] ?? ''); 
        $telefone = trim($_POST['telefone'] ?? '');

        // 1. Tenta encontrar o paciente pelo CPF
        $sql_find_pac = "SELECT id FROM pacientes WHERE cpf = ?";
        $stmt_find_pac = mysqli_prepare($conexao, $sql_find_pac);
        mysqli_stmt_bind_param($stmt_find_pac, "s", $cpf);
        mysqli_stmt_execute($stmt_find_pac);
        $paciente_existente = mysqli_stmt_get_result($stmt_find_pac)->fetch_assoc();

        if ($paciente_existente) {
            $id_referencia = $paciente_existente['id'];
            $sql_user_check = "SELECT id FROM usuarios WHERE tipo_usuario = 'paciente' AND id_referencia = ?";
            $stmt_user_check = mysqli_prepare($conexao, $sql_user_check);
            mysqli_stmt_bind_param($stmt_user_check, "i", $id_referencia);
            mysqli_stmt_execute($stmt_user_check);
            if (mysqli_stmt_get_result($stmt_user_check)->num_rows > 0) {
                throw new Exception("Este CPF já está associado a uma conta de utilizador.");
            }
        } else {
            $sql = "INSERT INTO pacientes (nome, cpf, data_nascimento, email, telefone) VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conexao, $sql);
            mysqli_stmt_bind_param($stmt, "sssss", $nome_completo, $cpf, $data_nascimento, $email, $telefone); 
            mysqli_stmt_execute($stmt);
            $id_referencia = mysqli_insert_id($conexao);
        }
    
    } elseif ($tipo_usuario === 'medico') {
        // ============ LÓGICA DO MÉDICO ============
        $crm = trim($_POST['crm'] ?? $_POST['crm_input'] ?? '');
        $especialidade = trim($_POST['especialidade'] ?? $_POST['especialidade_input'] ?? '');

        $sql_find_med = "SELECT id FROM medicos WHERE crm = ?";
        $stmt_find_med = mysqli_prepare($conexao, $sql_find_med);
        mysqli_stmt_bind_param($stmt_find_med, "s", $crm);
        mysqli_stmt_execute($stmt_find_med);
        $medico_existente = mysqli_stmt_get_result($stmt_find_med)->fetch_assoc();

        if ($medico_existente) {
            $id_referencia = $medico_existente['id'];
            $sql_user_check = "SELECT id FROM usuarios WHERE tipo_usuario = 'medico' AND id_referencia = ?";
            $stmt_user_check = mysqli_prepare($conexao, $sql_user_check);
            mysqli_stmt_bind_param($stmt_user_check, "i", $id_referencia);
            mysqli_stmt_execute($stmt_user_check);
            if (mysqli_stmt_get_result($stmt_user_check)->num_rows > 0) {
                throw new Exception("Este CRM já está associado a uma conta de utilizador.");
            }
        } else {
            $sql = "INSERT INTO medicos (nome_completo, crm, especialidade) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conexao, $sql);
            mysqli_stmt_bind_param($stmt, "sss", $nome_completo, $crm, $especialidade);
            mysqli_stmt_execute($stmt);
            $id_referencia = mysqli_insert_id($conexao);
        }

    } elseif ($tipo_usuario === 'enfermagem') {
        // ============ LÓGICA DA ENFERMAGEM ============
        $coren = trim($_POST['coren'] ?? $_POST['coren_input'] ?? '');
        $categoria = trim($_POST['categoria'] ?? $_POST['categoria_input'] ?? '');

        $sql_find_enf = "SELECT id FROM enfermagem WHERE coren = ?";
        $stmt_find_enf = mysqli_prepare($conexao, $sql_find_enf);
        mysqli_stmt_bind_param($stmt_find_enf, "s", $coren);
        mysqli_stmt_execute($stmt_find_enf);
        $enfermagem_existente = mysqli_stmt_get_result($stmt_find_enf)->fetch_assoc();

        if ($enfermagem_existente) {
            $id_referencia = $enfermagem_existente['id'];
            $sql_user_check = "SELECT id FROM usuarios WHERE tipo_usuario = 'enfermagem' AND id_referencia = ?";
            $stmt_user_check = mysqli_prepare($conexao, $sql_user_check);
            mysqli_stmt_bind_param($stmt_user_check, "i", $id_referencia);
            mysqli_stmt_execute($stmt_user_check);
            if (mysqli_stmt_get_result($stmt_user_check)->num_rows > 0) {
                throw new Exception("Este COREN já está associado a uma conta de utilizador.");
            }
        } else {
            $sql = "INSERT INTO enfermagem (nome_completo, coren, categoria) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conexao, $sql);
            mysqli_stmt_bind_param($stmt, "sss", $nome_completo, $coren, $categoria);
            mysqli_stmt_execute($stmt);
            $id_referencia = mysqli_insert_id($conexao);
        }

    } elseif ($tipo_usuario === 'gerente') {      
        // ============ NOVA LÓGICA: INSERÇÃO EM profissionais_administrativos ============
        // ATENÇÃO: Nomes dos campos adaptados do front-end que forneci (registro.php)
        $admin_cpf = trim($_POST['ger_cpf_input'] ?? ''); 
        $admin_data_nascimento = trim($_POST['ger_data_nascimento_input'] ?? '');
        $cargo = 'gerente'; // Cargo específico para a nova tabela
        
        // 1. Inserção na tabela 'profissionais_administrativos'
        $sql = "INSERT INTO profissionais_administrativos (nome_completo, cpf, data_nascimento, cargo) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, "ssss", $nome_completo, $admin_cpf, $admin_data_nascimento, $cargo);
        
        if (!mysqli_stmt_execute($stmt)) {
             throw new Exception("Erro ao inserir dados do Gerente na tabela administrativa.");
        }
        
        $id_referencia = mysqli_insert_id($conexao);
        
        if ($id_referencia === 0) {
            throw new Exception("Falha ao obter o ID de referência do Gerente.");
        }
        // ============ FIM DA LÓGICA ADMINISTRATIVA ============

    } else {
        throw new Exception("Tipo de usuário inválido.");
    }
    
    // 4. Criação da conta de login na tabela 'usuarios'
    // Este passo é comum a todos e garante que a conta de acesso seja criada
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    $sql_user = "INSERT INTO usuarios (nome_completo, email, senha, tipo_usuario, id_referencia) VALUES (?, ?, ?, ?, ?)";
    $stmt_user = mysqli_prepare($conexao, $sql_user);
    mysqli_stmt_bind_param($stmt_user, "ssssi", $nome_completo, $email, $senha_hash, $tipo_usuario, $id_referencia);
    mysqli_stmt_execute($stmt_user);

    mysqli_commit($conexao);
    
    // Redireciona com mensagem de sucesso
    $_SESSION['login_erro'] = 'Conta criada com sucesso! Faça login para aceder.';
    header("Location: login.php");
    exit();

} catch (Exception $e) {
    mysqli_rollback($conexao);
    // Redireciona com a mensagem de erro específica
    header("Location: registro.php?erro=" . urlencode($e->getMessage()));
    exit();
}
?>