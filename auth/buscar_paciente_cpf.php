<?php
// Define o tipo de conteúdo como JSON
header('Content-Type: application/json');

require_once '../conexao.php'; // Ajuste o caminho se necessário

if (!isset($_GET['cpf'])) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'CPF não fornecido.']);
    exit();
}

$cpf = trim($_GET['cpf']);

// 1. Tenta encontrar o paciente pelo CPF
// (A coluna 'nome' na tabela 'pacientes' guarda o nome completo)
$sql_pac = "SELECT id, nome, data_nascimento FROM pacientes WHERE cpf = ?";
$stmt_pac = mysqli_prepare($conexao, $sql_pac);
mysqli_stmt_bind_param($stmt_pac, "s", $cpf);
mysqli_stmt_execute($stmt_pac);
$resultado_pac = mysqli_stmt_get_result($stmt_pac);
$paciente = mysqli_fetch_assoc($resultado_pac);

if ($paciente) {
    // 2. Paciente encontrado. Verifica se ele JÁ TEM um login na tabela 'usuarios'.
    $paciente_id = $paciente['id'];
    $sql_user = "SELECT id FROM usuarios WHERE tipo_usuario = 'paciente' AND id_referencia = ?";
    $stmt_user = mysqli_prepare($conexao, $sql_user);
    mysqli_stmt_bind_param($stmt_user, "i", $paciente_id);
    mysqli_stmt_execute($stmt_user);
    
    if (mysqli_stmt_get_result($stmt_user)->num_rows > 0) {
        // 3. Erro: Paciente encontrado, mas já tem um login vinculado.
        echo json_encode(['status' => 'erro', 'mensagem' => 'Este CPF já está associado a uma conta de login.']);
    } else {
        // 4. Sucesso: Paciente encontrado e sem login. Retorna os dados.
        echo json_encode([
            'status' => 'encontrado',
            'nome' => $paciente['nome'],
            'data_nascimento' => $paciente['data_nascimento']
        ]);
    }
} else {
    // 5. Paciente não encontrado.
    echo json_encode(['status' => 'nao_encontrado']);
}

mysqli_close($conexao);
?>