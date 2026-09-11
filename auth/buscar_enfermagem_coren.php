<?php
// Define o tipo de conteúdo como JSON
header('Content-Type: application/json');

require_once '../conexao.php'; // Ajuste o caminho se necessário

if (!isset($_GET['coren'])) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'COREN não fornecido.']);
    exit();
}

$coren = trim($_GET['coren']);

// 1. Tenta encontrar o profissional pelo COREN
$sql_enf = "SELECT id, nome_completo, categoria FROM enfermagem WHERE coren = ?";
$stmt_enf = mysqli_prepare($conexao, $sql_enf);
mysqli_stmt_bind_param($stmt_enf, "s", $coren);
mysqli_stmt_execute($stmt_enf);
$resultado_enf = mysqli_stmt_get_result($stmt_enf);
$enfermagem = mysqli_fetch_assoc($resultado_enf);

if ($enfermagem) {
    // 2. Profissional encontrado. Verifica se já tem um login.
    $enfermagem_id = $enfermagem['id'];
    $sql_user = "SELECT id FROM usuarios WHERE tipo_usuario = 'enfermagem' AND id_referencia = ?";
    $stmt_user = mysqli_prepare($conexao, $sql_user);
    mysqli_stmt_bind_param($stmt_user, "i", $enfermagem_id);
    mysqli_stmt_execute($stmt_user);
    
    if (mysqli_stmt_get_result($stmt_user)->num_rows > 0) {
        // 3. Erro: Profissional encontrado, mas já tem um login.
        echo json_encode(['status' => 'erro', 'mensagem' => 'Este COREN já está associado a uma conta de login.']);
    } else {
        // 4. Sucesso: Profissional encontrado e sem login. Retorna os dados.
        echo json_encode([
            'status' => 'encontrado',
            'nome' => $enfermagem['nome_completo'],
            'categoria' => $enfermagem['categoria']
        ]);
    }
} else {
    // 5. Profissional não encontrado (novo cadastro).
    echo json_encode(['status' => 'nao_encontrado']);
}

mysqli_close($conexao);
?>