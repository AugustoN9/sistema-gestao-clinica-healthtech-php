<?php
// Define o tipo de conteúdo como JSON
header('Content-Type: application/json');

require_once '../conexao.php'; // Ajuste o caminho se necessário

if (!isset($_GET['crm'])) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'CRM não fornecido.']);
    exit();
}

$crm = trim($_GET['crm']);

// 1. Tenta encontrar o médico pelo CRM
$sql_med = "SELECT id, nome_completo, especialidade FROM medicos WHERE crm = ?";
$stmt_med = mysqli_prepare($conexao, $sql_med);
mysqli_stmt_bind_param($stmt_med, "s", $crm);
mysqli_stmt_execute($stmt_med);
$resultado_med = mysqli_stmt_get_result($stmt_med);
$medico = mysqli_fetch_assoc($resultado_med);

if ($medico) {
    // 2. Médico encontrado. Verifica se ele JÁ TEM um login na tabela 'usuarios'.
    $medico_id = $medico['id'];
    $sql_user = "SELECT id FROM usuarios WHERE tipo_usuario = 'medico' AND id_referencia = ?";
    $stmt_user = mysqli_prepare($conexao, $sql_user);
    mysqli_stmt_bind_param($stmt_user, "i", $medico_id);
    mysqli_stmt_execute($stmt_user);
    
    if (mysqli_stmt_get_result($stmt_user)->num_rows > 0) {
        // 3. Erro: Médico encontrado, mas já tem um login vinculado.
        echo json_encode(['status' => 'erro', 'mensagem' => 'Este CRM já está associado a uma conta de login.']);
    } else {
        // 4. Sucesso: Médico encontrado e sem login. Retorna os dados.
        echo json_encode([
            'status' => 'encontrado',
            'nome' => $medico['nome_completo'],
            'especialidade' => $medico['especialidade']
        ]);
    }
} else {
    // 5. Médico não encontrado.
    echo json_encode(['status' => 'nao_encontrado']);
}

mysqli_close($conexao);
?>