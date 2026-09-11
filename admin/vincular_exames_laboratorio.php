<?php
// Inclui os arquivos essenciais
require_once '../auth.php';
proteger_pagina(['admin']); // Acesso apenas para Admin
require_once '../conexao.php';
require_once '../header.php';

// Variáveis de Controle
$erro = null;
$sucesso = null;
$laboratorio_id_selecionado = $_GET['laboratorio_id'] ?? null;

// =========================================================
// LÓGICA DE PROCESSAMENTO (VINCULAÇÃO)
// =========================================================

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $laboratorio_id = (int)($_POST['laboratorio_id'] ?? 0);
    $exames_vinculados = $_POST['exames'] ?? []; // Array associativo: [exame_id => codigo_exame_lab]
    $laboratorio_id_selecionado = $laboratorio_id; // Mantém o ID no formulário após POST

    if ($laboratorio_id === 0) {
        $erro = "Por favor, selecione um Laboratório para vincular os exames.";
    } else {
        mysqli_begin_transaction($conexao);
        try {
            // 1. Limpa TODOS os vínculos anteriores DESTE laboratório
            $sql_delete = "DELETE FROM exame_laboratorio WHERE laboratorio_id = ?";
            $stmt_delete = mysqli_prepare($conexao, $sql_delete);
            mysqli_stmt_bind_param($stmt_delete, "i", $laboratorio_id);
            mysqli_stmt_execute($stmt_delete);

            // 2. Insere os NOVOS vínculos
            if (!empty($exames_vinculados)) {
                $sql_insert = "INSERT INTO exame_laboratorio (laboratorio_id, exame_id, codigo_exame_lab) VALUES (?, ?, ?)";
                $stmt_insert = mysqli_prepare($conexao, $sql_insert);

                foreach ($exames_vinculados as $exame_id => $codigo) {
                    $exame_id_int = (int)$exame_id;
                    $codigo_clean = trim($codigo);
                    
                    // Só insere se o código do exame for fornecido (indicando que o exame foi selecionado)
                    if (!empty($codigo_clean)) {
                        mysqli_stmt_bind_param($stmt_insert, "iis", $laboratorio_id, $exame_id_int, $codigo_clean);
                        if (!mysqli_stmt_execute($stmt_insert)) {
                            throw new Exception("Erro ao vincular o exame ID $exame_id.");
                        }
                    }
                }
            }

            mysqli_commit($conexao);
            $sucesso = "Vínculos de exames para o Laboratório atualizados com sucesso!";

        } catch (Exception $e) {
            mysqli_rollback($conexao);
            $erro = "Falha ao vincular exames: " . $e->getMessage();
        }
    }
}

// =========================================================
// CARREGAMENTO DE DADOS (SELECTs e LISTAGEM)
// =========================================================

// A. Carregar todos os Laboratórios (para o primeiro select)
$sql_labs = "SELECT id, nome_empresa FROM laboratorios_parceiros ORDER BY nome_empresa";
$resultado_labs = mysqli_query($conexao, $sql_labs);

// B. Carregar todos os Exames (agrupados por tipo)
$sql_exames = "SELECT id, nome_exame, grupo_principal, tipo_documento FROM exames ORDER BY tipo_documento, grupo_principal, nome_exame";
$resultado_exames = mysqli_query($conexao, $sql_exames);

$exames_agrupados = [];
while ($exame = mysqli_fetch_assoc($resultado_exames)) {
    $chave_grupo = $exame['tipo_documento'] . ' - ' . $exame['grupo_principal'];
    if (!isset($exames_agrupados[$chave_grupo])) {
        $exames_agrupados[$chave_grupo] = [];
    }
    $exames_agrupados[$chave_grupo][] = $exame;
}

// C. Carregar vínculos existentes para o laboratório selecionado
$vinculos_atuais = [];
if ($laboratorio_id_selecionado) {
    $sql_vinculos = "SELECT exame_id, codigo_exame_lab FROM exame_laboratorio WHERE laboratorio_id = ?";
    $stmt_vinculos = mysqli_prepare($conexao, $sql_vinculos);
    mysqli_stmt_bind_param($stmt_vinculos, "i", $laboratorio_id_selecionado);
    mysqli_stmt_execute($stmt_vinculos);
    $resultado_vinculos = mysqli_stmt_get_result($stmt_vinculos);
    
    while ($vinculo = mysqli_fetch_assoc($resultado_vinculos)) {
        // Mapeia por ID do exame para fácil verificação no loop
        $vinculos_atuais[$vinculo['exame_id']] = $vinculo['codigo_exame_lab'];
    }
}

?>

<div class="d-flex justify-content-between align-items-center">
    <h2><i class="fas fa-link me-2"></i> Vincular Exames a Laboratórios</h2>
</div>
<p class="text-muted">Associe exames do catálogo mestre a um laboratório parceiro e defina o código interno.</p>
<hr>

<?php if ($erro): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
<?php endif; ?>
<?php if ($sucesso): ?>
    <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
<?php endif; ?>

<div class="card mb-4 shadow-sm">
    <div class="card-header bg-secondary text-white">
        Selecionar Laboratório
    </div>
    <div class="card-body">
        <form method="GET" action="vincular_exames_laboratorio.php" class="row">
            <div class="col-md-8 mb-3">
                <label for="laboratorio_id_select" class="form-label">Laboratório:</label>
                <select class="form-select" id="laboratorio_id_select" name="laboratorio_id" required>
                    <option value="">-- Selecione um Laboratório --</option>
                    <?php while ($lab = mysqli_fetch_assoc($resultado_labs)): ?>
                        <option value="<?= $lab['id'] ?>" 
                            <?= ($laboratorio_id_selecionado == $lab['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($lab['nome_empresa']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end mb-3">
                <button type="submit" class="btn btn-info w-100"><i class="fas fa-search me-1"></i> Carregar Exames</button>
            </div>
        </form>
    </div>
</div>

<?php if ($laboratorio_id_selecionado): ?>
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white">
            Exames para Vínculo (Defina o Código Interno)
        </div>
        <div class="card-body">
            <form method="POST" action="vincular_exames_laboratorio.php">
                <input type="hidden" name="laboratorio_id" value="<?= $laboratorio_id_selecionado ?>">
                
                <p class="text-muted small">Para vincular um exame, digite o código que o laboratório utiliza para ele. Deixe o campo vazio para desvincular/ignorar.</p>

                <div class="row">
                    <?php foreach ($exames_agrupados as $chave_grupo => $exames): ?>
                        <div class="col-lg-6 mb-4">
                            <h5 class="border-bottom pb-1 text-primary"><?= htmlspecialchars($chave_grupo) ?></h5>
                            <?php foreach ($exames as $exame): 
                                $vinculo_existente = $vinculos_atuais[$exame['id']] ?? '';
                            ?>
                                <div class="row mb-2 align-items-center">
                                    <div class="col-sm-7">
                                        <label for="codigo_<?= $exame['id'] ?>" class="form-label mb-0">
                                            <?= htmlspecialchars($exame['nome_exame']) ?>
                                        </label>
                                    </div>
                                    <div class="col-sm-5">
                                        <input type="text" class="form-control form-control-sm" 
                                               id="codigo_<?= $exame['id'] ?>" 
                                               name="exames[<?= $exame['id'] ?>]" 
                                               placeholder="Código Interno"
                                               value="<?= htmlspecialchars($vinculo_existente) ?>">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-4 text-center">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-sync-alt me-1"></i> Atualizar Vínculos do Laboratório
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require_once '../footer.php'; ?>