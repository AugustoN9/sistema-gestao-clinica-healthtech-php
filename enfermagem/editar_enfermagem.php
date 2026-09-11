<?php
// ===== INÍCIO DA NOVA LÓGICA DE AUTORIZAÇÃO E INCLUSÕES =====
require_once '../auth.php'; // Inclui o script de autenticação e funções de proteção
session_start(); // Garante que a sessão está iniciada
require_once '../conexao.php';

// --- VARIÁVEIS DE SESSÃO E ERRO ---
$usuario_tipo = $_SESSION['usuario_tipo'] ?? null;
$usuario_id_referencia = (int)($_SESSION['id_referencia'] ?? 0);
$erro = null;
$sucesso = $_GET['sucesso'] ?? null;

// 1. Pega o ID do profissional que está sendo solicitado
$id_profissional_solicitado = 0;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_profissional_solicitado = (int)($_POST['id_profissional'] ?? 0);
} elseif (isset($_GET['id'])) {
    $id_profissional_solicitado = (int)$_GET['id'];
} else {
    die('ID do profissional de enfermagem não fornecido.');
}

// 2. Aplica as regras de permissão (Admin e Gerente editam todos, Enfermagem edita a si mesma)
if ($usuario_tipo == 'admin' || $usuario_tipo == 'gerente') {
    // Admin e Gerente podem editar qualquer profissional. Deixa passar.
} elseif ($usuario_tipo == 'enfermagem') {
    // Profissional de Enfermagem só pode editar a si mesmo.
    if ($id_profissional_solicitado != $usuario_id_referencia) {
        die('Acesso negado. Você só pode editar o seu próprio perfil.');
    }
} else {
    // Paciente, Médico e outros caem aqui e são negados.
    die('Acesso negado. Você não tem permissão para editar perfis de enfermagem.');
}
// ===== FIM DA NOVA LÓGICA DE AUTORIZAÇÃO =====


// =================================================================
// LÓGICA POST: PROCESSAMENTO DA EDIÇÃO
// =================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recebe e valida dados
    $id = $id_profissional_solicitado; 
    $nome_completo = trim($_POST['nome_completo'] ?? '');
    $coren = trim($_POST['coren'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha_nova = $_POST['nova_senha'] ?? '';
    
    $categorias_validas = ['tecnico', 'enfermeiro'];
    if (!in_array($categoria, $categorias_validas)) {
        $erro = "Categoria de enfermagem inválida.";
    }

    if (!$erro) {
        // Inicia a transação para garantir atomicidade
        mysqli_begin_transaction($conexao);
        $sucesso_transacao = true;

        // 1. UPDATE na tabela ENFERMAGEM
        $sql_enfermagem = "UPDATE enfermagem SET nome_completo = ?, coren = ?, categoria = ? WHERE id = ?";
        $stmt_enfermagem = mysqli_prepare($conexao, $sql_enfermagem);
        mysqli_stmt_bind_param($stmt_enfermagem, "sssi", $nome_completo, $coren, $categoria, $id);
        if (!mysqli_stmt_execute($stmt_enfermagem)) {
            $erro = "Erro ao atualizar dados do profissional: " . mysqli_error($conexao);
            $sucesso_transacao = false;
        }

        // 2. UPDATE na tabela USUARIOS (Email)
        if ($sucesso_transacao) {
            $sql_check_user = "SELECT COUNT(*) FROM usuarios WHERE id_referencia = ? AND tipo_usuario = 'enfermagem'";
            $stmt_check = mysqli_prepare($conexao, $sql_check_user);
            mysqli_stmt_bind_param($stmt_check, "i", $id);
            mysqli_stmt_execute($stmt_check);
            $result_check = mysqli_stmt_get_result($stmt_check);
            $user_exists = mysqli_fetch_row($result_check)[0] > 0;
            mysqli_stmt_close($stmt_check);
            
            if ($user_exists) {
                $sql_usuario_email = "UPDATE usuarios SET email = ? WHERE id_referencia = ? AND tipo_usuario = 'enfermagem'";
                $stmt_usuario_email = mysqli_prepare($conexao, $sql_usuario_email);
                mysqli_stmt_bind_param($stmt_usuario_email, "si", $email, $id);
                if (!mysqli_stmt_execute($stmt_usuario_email)) {
                    $erro = "Erro ao atualizar email do usuário: " . mysqli_error($conexao);
                    $sucesso_transacao = false;
                }
            } 
            // Se o usuário de login não existe, não faz nada neste passo, espera pela possível inserção de senha.
        }
        
        // 3. UPDATE na tabela USUARIOS (Senha - APENAS SE FOR FORNECIDA)
        if ($sucesso_transacao && !empty($senha_nova)) {
            $senha_hash = password_hash($senha_nova, PASSWORD_DEFAULT);
            $sql_senha = "UPDATE usuarios SET senha = ? WHERE id_referencia = ? AND tipo_usuario = 'enfermagem'";
            $stmt_senha = mysqli_prepare($conexao, $sql_senha);
            mysqli_stmt_bind_param($stmt_senha, "si", $senha_hash, $id);
            if (!mysqli_stmt_execute($stmt_senha)) {
                $erro = "Erro ao atualizar senha do usuário: " . mysqli_error($conexao);
                $sucesso_transacao = false;
            }
        }
        
        // Finaliza a transação
        if ($sucesso_transacao) {
            mysqli_commit($conexao);
            
            // Lógica de Redirecionamento
            if ($usuario_tipo == 'admin' || $usuario_tipo == 'gerente') {
                header("Location: enfermagem.php?sucesso=Profissional+editado+com+sucesso!");
            } else {
                $_SESSION['usuario_nome'] = $nome_completo; 
                header("Location: ../area_enfermagem.php?sucesso=Perfil+atualizado+com+sucesso!");
            }
            exit();
        } else {
            mysqli_rollback($conexao);
        }
    }
}


// =================================================================
// LÓGICA GET: BUSCA INICIAL DO PROFISSIONAL DE ENFERMAGEM
// =================================================================

$id = $id_profissional_solicitado; 

$sql = "SELECT 
            e.id, 
            e.nome_completo, 
            e.coren, 
            e.categoria, 
            u.email,
            u.id AS usuario_id 
        FROM enfermagem e
        LEFT JOIN usuarios u ON e.id = u.id_referencia AND u.tipo_usuario = 'enfermagem'
        WHERE e.id = ?";

$stmt_get = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt_get, "i", $id);
mysqli_stmt_execute($stmt_get);
$resultado = mysqli_stmt_get_result($stmt_get);

$profissional = mysqli_fetch_assoc($resultado);

if (!$profissional) {
    header("Location: enfermagem.php"); 
    exit();
}

$categorias = ['tecnico' => 'Técnico(a) de Enfermagem', 'enfermeiro' => 'Enfermeiro(a)'];

require_once '../header.php';
?>

<h1>Editar Profissional de Enfermagem</h1>
<hr>

<?php if ($erro): ?>
    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($erro) ?></div>
<?php endif; ?>
<?php if ($sucesso): ?>
    <div class="alert alert-success" role="alert"><?= htmlspecialchars($sucesso) ?></div>
<?php endif; ?>

<form action="editar_enfermagem.php" method="POST">
    <input type="hidden" name="id_profissional" value="<?= $profissional['id'] ?>">
    <input type="hidden" name="usuario_id" value="<?= $profissional['usuario_id'] ?? '' ?>">

    <h4 class="mt-4">Dados Profissionais</h4>
    <div class="row">
        <div class="col-md-12 mb-3">
            <label for="nome_completo" class="form-label">Nome Completo:</label>
            <input type="text" class="form-control" id="nome_completo" name="nome_completo" value="<?= htmlspecialchars($profissional['nome_completo']) ?>" required>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="coren" class="form-label">COREN:</label>
            <input type="text" class="form-control" id="coren" name="coren" value="<?= htmlspecialchars($profissional['coren']) ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label for="categoria" class="form-label">Categoria:</label>
            <select class="form-select" id="categoria" name="categoria" required>
                <?php foreach ($categorias as $chave => $valor): ?>
                    <option value="<?= $chave ?>" <?= ($profissional['categoria'] == $chave) ? 'selected' : '' ?>>
                        <?= $valor ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <hr>
    <h4 class="mt-4">Credenciais de Acesso</h4>
    <div class="row">
        <div class="col-md-12 mb-3">
            <label for="email" class="form-label">Email (Login):</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($profissional['email'] ?? '') ?>" required>
            <div class="form-text">O email é obrigatório para login.</div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12 mb-3">
            <label for="nova_senha" class="form-label">Nova Senha:</label>
            <input type="password" class="form-control" id="nova_senha" name="nova_senha" placeholder="Deixe em branco para não alterar a senha">
            <div class="form-text">Preencha este campo **apenas** se quiser alterar a senha.</div>
        </div>
    </div>
    
   <div class="mt-3">
        <button type="submit" class="btn btn-primary">
            Salvar Alterações
        </button>
        
        <?php 
        $link_retorno = 'enfermagem.php'; // Padrão para Admin/Gerente
        if ($usuario_tipo == 'enfermagem') {
            $link_retorno = '../area_enfermagem.php'; // Para o próprio profissional
        }
        ?>
        <a href="<?= $link_retorno ?>" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

<?php require_once '../footer.php'; ?>