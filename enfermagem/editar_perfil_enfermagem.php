<?php
require_once '../auth.php';
proteger_pagina(['enfermagem']); // Só enfermagem
require_once '../conexao.php';

$erro = null;
$sucesso = null;
$id_referencia = (int)$_SESSION['id_referencia'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome_completo = trim($_POST['nome_completo']);
    $coren = trim($_POST['coren']);
    $categoria = trim($_POST['categoria']);
    
    try {
        // Atualiza a tabela 'enfermagem'
        $sql_enf = "UPDATE enfermagem SET nome_completo = ?, coren = ?, categoria = ? WHERE id = ?";
        $stmt_enf = mysqli_prepare($conexao, $sql_enf);
        mysqli_stmt_bind_param($stmt_enf, "sssi", $nome_completo, $coren, $categoria, $id_referencia);
        mysqli_stmt_execute($stmt_enf);

        // Atualiza também o nome na tabela 'usuarios' para consistência
        $sql_user = "UPDATE usuarios SET nome_completo = ? WHERE id = ?";
        $stmt_user = mysqli_prepare($conexao, $sql_user);
        mysqli_stmt_bind_param($stmt_user, "si", $nome_completo, $_SESSION['usuario_id']);
        mysqli_stmt_execute($stmt_user);
        
        $_SESSION['usuario_nome'] = $nome_completo; // Atualiza o nome na sessão
        $sucesso = "Perfil profissional atualizado com sucesso!";
        
    } catch (Exception $e) {
        $erro = "Erro ao atualizar: " . $e->getMessage();
    }
}

// Busca os dados atuais do profissional de enfermagem
$sql = "SELECT nome_completo, coren, categoria FROM enfermagem WHERE id = ?";
$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_referencia);
mysqli_stmt_execute($stmt);
$enfermagem = mysqli_stmt_get_result($stmt)->fetch_assoc();

require_once '../header.php';
?>

<h1>Editar Perfil Profissional (Enfermagem)</h1>
<hr>

<?php if ($sucesso): ?>
    <div class="alert alert-success"><?= $sucesso ?></div>
<?php endif; ?>
<?php if ($erro): ?>
    <div class="alert alert-danger"><?= $erro ?></div>
<?php endif; ?>

<form action="editar_perfil_enfermagem.php" method="POST">
    <div class="row">
        <div class="col-md-8 mb-3">
            <label for="nome_completo" class="form-label">Nome Completo:</label>
            <input type="text" class="form-control" id="nome_completo" name="nome_completo" value="<?= htmlspecialchars($enfermagem['nome_completo'] ?? '') ?>" required>
        </div>
        <div class="col-md-4 mb-3">
            <label for="coren" class="form-label">COREN:</label>
            <input type="text" class="form-control" id="coren" name="coren" value="<?= htmlspecialchars($enfermagem['coren'] ?? '') ?>" required>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12 mb-3">
            <label for="categoria" class="form-label">Categoria:</label>
            <select class="form-select" id="categoria" name="categoria" required>
                <option value="tecnico" <?= ($enfermagem['categoria'] ?? '') == 'tecnico' ? 'selected' : '' ?>>Técnico(a) de Enfermagem</option>
                <option value="enfermeiro" <?= ($enfermagem['categoria'] ?? '') == 'enfermeiro' ? 'selected' : '' ?>>Enfermeiro(a)</option>
            </select>
        </div>
    </div>
    <div class="mt-3">
        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        <a href="<?= $link_home ?>" class="btn btn-secondary">Cancelar</a> </div>
</form>

<p class="mt-4 text-muted small">Para alterar o seu email ou senha de login, por favor, aceda à <a href="/auth/editar_perfil_usuario.php">página de edição de conta</a>.</p>

<?php require_once '../footer.php'; ?>