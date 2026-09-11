<?php
// 1. Proteção e Conexão
require_once '../auth.php';
proteger_pagina(['admin']); // Apenas Admin pode editar
require_once '../conexao.php';
require_once '../header.php';

// 2. Obter o ID do profissional a ser editado
$id_profissional = $_GET['id'] ?? null;

if (!$id_profissional || !is_numeric($id_profissional)) {
    die('ID do profissional administrativo não fornecido ou inválido.');
}

// 3. Buscar os dados do profissional administrativo (JOIN com a tabela de usuários)
$sql = "SELECT 
            pa.id, 
            pa.nome_completo, 
            pa.cpf, 
            pa.data_nascimento, 
            pa.cargo, 
            u.email,
            u.id AS usuario_id // ID da tabela usuarios
        FROM 
            profissionais_administrativos pa
        LEFT JOIN 
            usuarios u ON pa.id = u.id_referencia AND u.tipo_usuario = pa.cargo
        WHERE 
            pa.id = ?";
            
$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_profissional);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$profissional = mysqli_fetch_assoc($resultado);

if (!$profissional) {
    die('Profissional administrativo não encontrado.');
}

// 4. Mensagens de feedback
$erro = $_GET['erro'] ?? '';
$sucesso = $_GET['sucesso'] ?? '';

// Definir as opções de cargo (pode ser expandido para outros cargos)
$cargos_opcoes = [
    'gerente' => 'Gerente',
    'supervisor' => 'Supervisor',
    'auxiliar_adm' => 'Auxiliar Administrativo'
];

?>

<div class="row">
    <div class="col-md-10 offset-md-1">
        <h2>Editar Profissional Administrativo</h2>
        <p class="text-muted">Ajuste os dados pessoais e de acesso de **<?= htmlspecialchars($profissional['nome_completo']) ?>**.</p>
        <hr>

        <?php if ($erro): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">Dados do Profissional</div>
            <div class="card-body">
                <form action="processa_edicao_admin.php" method="POST">
                    <input type="hidden" name="id_profissional" value="<?= $profissional['id'] ?>">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nome_completo" class="form-label">Nome Completo</label>
                            <input type="text" class="form-control" id="nome_completo" name="nome_completo" value="<?= htmlspecialchars($profissional['nome_completo']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="cargo" class="form-label">Cargo</label>
                            <select class="form-select" id="cargo" name="cargo" required>
                                <?php foreach ($cargos_opcoes as $chave => $valor): ?>
                                    <option value="<?= $chave ?>" <?= ($profissional['cargo'] == $chave) ? 'selected' : '' ?>>
                                        <?= $valor ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="cpf" class="form-label">CPF</label>
                            <input type="text" class="form-control" id="cpf" name="cpf" value="<?= htmlspecialchars($profissional['cpf']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                            <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" value="<?= htmlspecialchars($profissional['data_nascimento']) ?>" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="acao" value="salvar_dados" class="btn btn-primary">Salvar Dados Pessoais</button>
                    <a href="gerenciar_equipe_adm.php" class="btn btn-secondary">Voltar à Lista</a>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">Dados da Conta de Acesso (Login)</div>
            <div class="card-body">
                <form action="processa_edicao_admin.php" method="POST">
                    <input type="hidden" name="id_profissional" value="<?= $profissional['id'] ?>">
                    <input type="hidden" name="usuario_id" value="<?= $profissional['usuario_id'] ?? '' ?>">
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email de Login Atual:</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($profissional['email'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="nova_senha" class="form-label">Nova Senha (deixe vazio para não alterar)</label>
                        <input type="password" class="form-control" id="nova_senha" name="nova_senha" placeholder="Digite a nova senha">
                    </div>
                    
                    <p class="text-muted small">A alteração da senha afeta a capacidade do profissional de iniciar sessão.</p>

                    <button type="submit" name="acao" value="salvar_conta" class="btn btn-warning">Salvar Dados da Conta</button>
                </form>
            </div>
        </div>

    </div>
</div>

<?php require_once '../footer.php'; ?>