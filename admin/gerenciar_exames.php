<?php
// Inclui os arquivos essenciais
require_once '../auth.php';
proteger_pagina(['admin']); // Acesso apenas para Admin
require_once '../conexao.php';
require_once '../header.php';

// Variáveis de Controle
$erro = null;
$sucesso = null;
$exame_editando = null; // Usado para carregar dados se estiver editando

// =========================================================
// LÓGICA DE PROCESSAMENTO (CRUD Actions)
// =========================================================

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $acao = $_POST['acao'] ?? '';
    $nome_exame = trim($_POST['nome_exame'] ?? '');
    $grupo_principal = trim($_POST['grupo_principal'] ?? '');
    $tipo_documento = trim($_POST['tipo_documento'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $exame_id = $_POST['exame_id'] ?? null;

    if (empty($nome_exame) || empty($grupo_principal) || empty($tipo_documento)) {
        $erro = "Por favor, preencha o Nome, Grupo e Tipo do Exame.";
    } else {
        try {
            if ($acao == 'adicionar') {
                $sql = "INSERT INTO exames (nome_exame, grupo_principal, tipo_documento, descricao) VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($conexao, $sql);
                mysqli_stmt_bind_param($stmt, "ssss", $nome_exame, $grupo_principal, $tipo_documento, $descricao);
                mysqli_stmt_execute($stmt);
                $sucesso = "Exame '$nome_exame' adicionado com sucesso!";

            } elseif ($acao == 'editar' && $exame_id) {
                $sql = "UPDATE exames SET nome_exame = ?, grupo_principal = ?, tipo_documento = ?, descricao = ? WHERE id = ?";
                $stmt = mysqli_prepare($conexao, $sql);
                mysqli_stmt_bind_param($stmt, "ssssi", $nome_exame, $grupo_principal, $tipo_documento, $descricao, $exame_id);
                mysqli_stmt_execute($stmt);
                $sucesso = "Exame '$nome_exame' atualizado com sucesso!";
            }

        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) { // Código para entrada duplicada (UNIQUE KEY)
                $erro = "Erro: Exame com este nome já existe no catálogo.";
            } else {
                $erro = "Erro de SQL: " . $e->getMessage();
            }
        }
    }
}

// Lógica para Excluir
if (isset($_GET['excluir_id'])) {
    $excluir_id = (int)$_GET['excluir_id'];
    try {
        $sql = "DELETE FROM exames WHERE id = ?";
        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, "i", $excluir_id);
        mysqli_stmt_execute($stmt);
        $sucesso = "Exame excluído com sucesso!";
    } catch (mysqli_sql_exception $e) {
        $erro = "Erro ao excluir: " . $e->getMessage();
    }
    // Redireciona para limpar o GET, evitando re-exclusão
    header("Location: gerenciar_exames.php?status=sucesso&msg=" . urlencode($sucesso));
    exit();
}

// Lógica para carregar dados para Edição
if (isset($_GET['editar_id'])) {
    $editar_id = (int)$_GET['editar_id'];
    $sql = "SELECT * FROM exames WHERE id = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, "i", $editar_id);
    mysqli_stmt_execute($stmt);
    $exame_editando = mysqli_stmt_get_result($stmt)->fetch_assoc();
}

// =========================================================
// BUSCA E LISTAGEM
// =========================================================
$sql_listagem = "SELECT * FROM exames ORDER BY grupo_principal, nome_exame";
$resultado_listagem = mysqli_query($conexao, $sql_listagem);

// Define as opções para o select
$tipos_documento = ['Laboratorial', 'Imagem', 'Anatomopatológico'];
?>

<div class="d-flex justify-content-between align-items-center">
    <h2><i class="fas fa-flask me-2"></i> Gestão do Catálogo de Exames</h2>
</div>
<p class="text-muted">Adicione, edite ou remova exames da lista mestra.</p>
<hr>

<?php if ($erro): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
<?php endif; ?>
<?php if ($sucesso): ?>
    <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
<?php endif; ?>

<div class="card mb-4 shadow-sm">
    <div class="card-header bg-primary text-white">
        <?= $exame_editando ? 'Editar Exame: ' . htmlspecialchars($exame_editando['nome_exame']) : 'Adicionar Novo Exame' ?>
    </div>
    <div class="card-body">
        <form method="POST" action="gerenciar_exames.php">
            <input type="hidden" name="acao" value="<?= $exame_editando ? 'editar' : 'adicionar' ?>">
            <?php if ($exame_editando): ?>
                <input type="hidden" name="exame_id" value="<?= $exame_editando['id'] ?>">
            <?php endif; ?>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nome_exame" class="form-label">Nome do Exame:</label>
                    <input type="text" class="form-control" id="nome_exame" name="nome_exame" required 
                           value="<?= htmlspecialchars($exame_editando['nome_exame'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="grupo_principal" class="form-label">Grupo Principal (Ex: Bioquímica, USG, etc.):</label>
                    <input type="text" class="form-control" id="grupo_principal" name="grupo_principal" required 
                           value="<?= htmlspecialchars($exame_editando['grupo_principal'] ?? '') ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="tipo_documento" class="form-label">Tipo de Documento:</label>
                    <select class="form-select" id="tipo_documento" name="tipo_documento" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($tipos_documento as $tipo): ?>
                            <option value="<?= $tipo ?>" 
                                <?= ($exame_editando && $exame_editando['tipo_documento'] == $tipo) ? 'selected' : '' ?>>
                                <?= $tipo ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-8 mb-3">
                    <label for="descricao" class="form-label">Descrição Breve / Notas:</label>
                    <input type="text" class="form-control" id="descricao" name="descricao" 
                           value="<?= htmlspecialchars($exame_editando['descricao'] ?? '') ?>">
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save me-1"></i> <?= $exame_editando ? 'Salvar Alterações' : 'Adicionar Exame' ?>
                </button>
                <?php if ($exame_editando): ?>
                    <a href="gerenciar_exames.php" class="btn btn-warning">Cancelar Edição</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<h3>Lista de Exames Cadastrados</h3>
<table class="table table-striped table-hover">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nome do Exame</th>
            <th>Grupo</th>
            <th>Tipo</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php if (mysqli_num_rows($resultado_listagem) > 0): ?>
            <?php while ($exame = mysqli_fetch_assoc($resultado_listagem)): ?>
                <tr>
                    <td><?= $exame['id'] ?></td>
                    <td><?= htmlspecialchars($exame['nome_exame']) ?></td>
                    <td><?= htmlspecialchars($exame['grupo_principal']) ?></td>
                    <td><?= htmlspecialchars($exame['tipo_documento']) ?></td>
                    <td>
                        <a href="?editar_id=<?= $exame['id'] ?>" class="btn btn-sm btn-info me-2">Editar</a>
                        <a href="?excluir_id=<?= $exame['id'] ?>" class="btn btn-sm btn-danger" 
                           onclick="return confirm('Tem certeza que deseja excluir este exame? A ação é irreversível.');">Excluir</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" class="text-center">Nenhum exame cadastrado.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php require_once '../footer.php'; ?>