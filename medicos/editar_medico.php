<?php
// ===== INÍCIO DA NOVA LÓGICA DE AUTORIZAÇÃO =====
require_once '../auth.php'; // Inclui o script de autenticação
//session_start(); // Garante que a sessão está iniciada para usar $_SESSION

// 1. Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /auth/login.php');
    exit();
}

// 2. Pega o ID do médico que está sendo solicitado
// Se for POST, pega do formulário, senão, pega da URL
$id_medico_solicitado = 0;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_medico_solicitado = (int)($_POST['medico_id'] ?? $_POST['id']); // Usa 'medico_id' ou 'id'
} elseif (isset($_GET['id'])) {
    $id_medico_solicitado = (int)$_GET['id'];
} else {
    // Se não houver ID, não podemos fazer nada.
    die('ID do médico não fornecido.'); 
}

// 3. Pega os dados do usuário logado
$usuario_tipo = $_SESSION['usuario_tipo'];
$usuario_id_referencia = (int)$_SESSION['id_referencia'];

// 4. Aplica as regras de permissão
// Agora permite Gerente E Admin
if ($usuario_tipo == 'gerente' || $usuario_tipo == 'admin') {
    // Gerente e Admin podem editar qualquer médico. Deixa passar.
} elseif ($usuario_tipo == 'medico') {
    // Médico só pode editar a si mesmo.
    if ($id_medico_solicitado != $usuario_id_referencia) {
        // Aqui usamos a função de proteção para exibir uma mensagem de erro ou redirecionar
        require_once '../auth.php'; // Garante que a função está disponível
        // É mais limpo usar a proteção de página para este caso, se disponível.
        // Se a lógica for apenas um 'die'
        die('Acesso negado. Você só pode editar o seu próprio perfil.');
    }
} else {
    // Paciente, Enfermagem e outros caem aqui e são negados.
    // Garante que o acesso seja negado para outros perfis não autorizados.
    die('Acesso negado. Você não tem permissão para editar perfis de médicos.');
}
// ===== FIM DA NOVA LÓGICA DE AUTORIZAÇÃO =====


require_once '../conexao.php';

$erro = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recebe e valida dados
    $id = $id_medico_solicitado; 
    $nome_completo = trim($_POST['nome_completo'] ?? '');
    $crm = trim($_POST['crm'] ?? '');
    $especialidade = trim($_POST['especialidade'] ?? '');
    $email = trim($_POST['email'] ?? ''); // NOVO: Email
    $senha_nova = $_POST['senha'] ?? ''; // NOVO: Nova Senha

    // Inicia a transação para garantir atomicidade (Ambas as tabelas atualizadas ou nenhuma)
    mysqli_begin_transaction($conexao);
    $sucesso = true;

    // 1. UPDATE na tabela MEDICO
    $sql_medico = "UPDATE medicos SET nome_completo = ?, crm = ?, especialidade = ? WHERE id = ?";
    $stmt_medico = mysqli_prepare($conexao, $sql_medico);
    mysqli_stmt_bind_param($stmt_medico, "sssi", $nome_completo, $crm, $especialidade, $id);
    if (!mysqli_stmt_execute($stmt_medico)) {
        $erro = "Erro ao atualizar dados do médico: " . mysqli_error($conexao);
        $sucesso = false;
    }

    // 2. UPDATE na tabela USUARIOS (Email)
    if ($sucesso) {
        $sql_usuario_email = "UPDATE usuarios SET email = ? WHERE id_referencia = ? AND tipo_usuario = 'medico'";
        $stmt_usuario_email = mysqli_prepare($conexao, $sql_usuario_email);
        mysqli_stmt_bind_param($stmt_usuario_email, "si", $email, $id);
        if (!mysqli_stmt_execute($stmt_usuario_email)) {
            $erro = "Erro ao atualizar email do usuário: " . mysqli_error($conexao);
            $sucesso = false;
        }
    }
    
    // 3. UPDATE na tabela USUARIOS (Senha - APENAS SE FOR FORNECIDA)
    if ($sucesso && !empty($senha_nova)) {
        $senha_hash = password_hash($senha_nova, PASSWORD_DEFAULT);
        $sql_senha = "UPDATE usuarios SET senha = ? WHERE id_referencia = ? AND tipo_usuario = 'medico'";
        $stmt_senha = mysqli_prepare($conexao, $sql_senha);
        mysqli_stmt_bind_param($stmt_senha, "si", $senha_hash, $id);
        if (!mysqli_stmt_execute($stmt_senha)) {
            $erro = "Erro ao atualizar senha do usuário: " . mysqli_error($conexao);
            $sucesso = false;
        }
    }
    
    // Finaliza a transação
    if ($sucesso) {
        mysqli_commit($conexao);
        // === Lógica de Redirecionamento ===
        if ($usuario_tipo == 'gerente') {
            header("Location: medicos.php?status=sucesso_editar");
        } else {
            // Se o próprio médico atualizou o perfil, ele pode precisar atualizar o $_SESSION['usuario_nome']
            $_SESSION['usuario_nome'] = $nome_completo; 
            header("Location: ../area_medico.php?status=perfil_atualizado");
        }
        exit();
    } else {
        mysqli_rollback($conexao);
        // O erro já foi setado acima
    }
}

// =================================================================
// LÓGICA GET: BUSCA INICIAL DO MÉDICO
// =================================================================

$id = $id_medico_solicitado; 

// QUERY ATUALIZADA: FAZENDO JOIN COM USUARIOS PARA OBTER O EMAIL
$sql = "SELECT 
            m.*, 
            u.email 
        FROM medicos m
        LEFT JOIN usuarios u ON m.id = u.id_referencia AND u.tipo_usuario = 'medico'
        WHERE m.id = ?";

$stmt_get = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt_get, "i", $id);
mysqli_stmt_execute($stmt_get);
$resultado = mysqli_stmt_get_result($stmt_get);

$medico = mysqli_fetch_assoc($resultado);

if (!$medico) {
    header("Location: medicos.php");
    exit();
}

require_once '../header.php';
?>

<h1>Editar Médico</h1>
<hr>

<?php if ($erro): ?>
    <div class="alert alert-danger" role="alert"><?= $erro ?></div>
<?php endif; ?>

<form action="editar_medico.php" method="POST">
    <input type="hidden" name="medico_id" value="<?= $medico['id'] ?>">

    <h4 class="mt-4">Dados Profissionais</h4>
    <div class="row">
        <div class="col-md-8 mb-3">
            <label for="nome_completo" class="form-label">Nome Completo:</label>
            <input type="text" class="form-control" id="nome_completo" name="nome_completo" value="<?= htmlspecialchars($medico['nome_completo'] ?? '') ?>" required>
        </div>
        <div class="col-md-4 mb-3">
            <label for="crm" class="form-label">CRM:</label>
            <input type="text" class="form-control" id="crm" name="crm" value="<?= htmlspecialchars($medico['crm'] ?? '') ?>" required>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12 mb-3">
            <label for="especialidade" class="form-label">Especialidade:</label>
            <input type="text" class="form-control" id="especialidade" name="especialidade" value="<?= htmlspecialchars($medico['especialidade'] ?? '') ?>" required>
        </div>
    </div>
    
    <hr>
    <h4 class="mt-4">Credenciais de Acesso</h4>
    <div class="row">
        <div class="col-md-12 mb-3">
            <label for="email" class="form-label">Email (Login):</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($medico['email'] ?? '') ?>" required>
            <div class="form-text">O email é obrigatório para login.</div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12 mb-3">
            <label for="senha" class="form-label">Nova Senha:</label>
            <input type="password" class="form-control" id="senha" name="senha" placeholder="Deixe em branco para não alterar">
            <div class="form-text">Preencha este campo **apenas** se desejar alterar a senha.</div>
        </div>
    </div>
    <div class="mt-3">
        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        
        <?php
        $link_cancelar = 'medicos.php'; // Padrão para gerente
        if ($usuario_tipo == 'medico') {
            $link_cancelar = '../area_medico.php'; // Para médico
        }
        ?>
        <a href="<?= $link_cancelar ?>" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

<?php
require_once '../footer.php';
?>