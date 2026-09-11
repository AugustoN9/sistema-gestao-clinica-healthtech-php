<?php
require_once '../auth.php';
// Proteção: Garante que o utilizador só pode editar a si mesmo
proteger_pagina(); // Apenas logado (qualquer tipo)

require_once '../conexao.php';

$erro = null;
$sucesso = null;

// O ID vem da SESSÃO, não da URL, para máxima segurança
$usuario_id_logado = (int)$_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome_completo = trim($_POST['nome_completo']);
    $email = trim($_POST['email']);
    
    // Lógica de atualização de senha (opcional)
    $nova_senha = trim($_POST['nova_senha']);
    $confirmar_senha = trim($_POST['confirmar_senha']);

    // (Validação de Email Duplicado - Opcional mas recomendado)
    // ...

    try {
        if (!empty($nova_senha)) {
            if ($nova_senha != $confirmar_senha) {
                throw new Exception("As novas senhas não coincidem.");
            }
            // Se a senha foi fornecida, atualiza a senha
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET nome_completo = ?, email = ?, senha = ? WHERE id = ?";
            $stmt = mysqli_prepare($conexao, $sql);
            mysqli_stmt_bind_param($stmt, "sssi", $nome_completo, $email, $senha_hash, $usuario_id_logado);
        } else {
            // Se a senha não foi fornecida, atualiza só o nome e email
            $sql = "UPDATE usuarios SET nome_completo = ?, email = ? WHERE id = ?";
            $stmt = mysqli_prepare($conexao, $sql);
            mysqli_stmt_bind_param($stmt, "ssi", $nome_completo, $email, $usuario_id_logado);
        }
        
        mysqli_stmt_execute($stmt);
        
        // Atualiza o nome na sessão para que o header mude
        $_SESSION['usuario_nome'] = $nome_completo;
        $sucesso = "Perfil atualizado com sucesso!";

    } catch (Exception $e) {
        $erro = "Erro ao atualizar: " . $e->getMessage();
    }
}

// Busca os dados atuais do utilizador
$sql_user = "SELECT nome_completo, email FROM usuarios WHERE id = ?";
$stmt_user = mysqli_prepare($conexao, $sql_user);
mysqli_stmt_bind_param($stmt_user, "i", $usuario_id_logado);
mysqli_stmt_execute($stmt_user);
$usuario = mysqli_stmt_get_result($stmt_user)->fetch_assoc();

require_once '../header.php';
?>

<h1>Editar Meu Perfil</h1>
<hr>

<?php if ($sucesso): ?>
    <div class="alert alert-success"><?= $sucesso ?></div>
<?php endif; ?>
<?php if ($erro): ?>
    <div class="alert alert-danger"><?= $erro ?></div>
<?php endif; ?>

<form action="editar_perfil_usuario.php" method="POST">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="nome_completo" class="form-label">Nome Completo:</label>
            <input type="text" class="form-control" id="nome_completo" name="nome_completo" value="<?= htmlspecialchars($usuario['nome_completo'] ?? '') ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label for="email" class="form-label">Email (Login):</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" required>
        </div>
    </div>
    <hr>
    <h5>Alterar Senha (Opcional)</h5>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="nova_senha" class="form-label">Nova Senha:</label>
            <input type="password" class="form-control" id="nova_senha" name="nova_senha" placeholder="Deixe em branco para não alterar">
        </div>
        <div class="col-md-6 mb-3">
            <label for="confirmar_senha" class="form-label">Confirmar Nova Senha:</label>
            <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha">
        </div>
    </div>
    
    <div class="mt-3">
        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        <a href="<?= $link_home ?>" class="btn btn-secondary">Cancelar</a> </div>
</form>

<?php require_once '../footer.php'; ?>