<?php
// 1. Proteção e Conexão
require_once '../auth.php';
proteger_pagina(['admin']); // Apenas Admin pode gerenciar a equipe
require_once '../conexao.php';

// 2. Inclusões necessárias
require_once '../header.php';

// =================================================================
// LÓGICA DE BUSCA E PAGINAÇÃO
// =================================================================

$registros_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_atual < 1) $pagina_atual = 1;

$termo_busca = $_GET['busca'] ?? '';
$parametros_url = [];
$where_conditions = [];

// Cláusula WHERE para busca (pode buscar por nome, cargo ou CPF)
if (!empty($termo_busca)) {
    $termo_busca_seguro = mysqli_real_escape_string($conexao, $termo_busca);
    $where_conditions[] = "pa.nome_completo LIKE '%$termo_busca_seguro%' OR pa.cargo LIKE '%$termo_busca_seguro%' OR pa.cpf LIKE '%$termo_busca_seguro%'";
    $parametros_url['busca'] = $termo_busca;
}

$where_clause = '';
if (count($where_conditions) > 0) {
    $where_clause = " WHERE " . implode(' AND ', $where_conditions);
}

// 3. Contagem Total para Paginação
$sql_total = "SELECT COUNT(*) AS total FROM profissionais_administrativos pa $where_clause";
$resultado_total = mysqli_query($conexao, $sql_total);
$total_registros = mysqli_fetch_assoc($resultado_total)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// 4. Limites de Offset
$offset = ($pagina_atual - 1) * $registros_por_pagina;

// 5. Query Principal (com JOIN para pegar o email do login)
$sql = "SELECT 
            pa.id, 
            pa.nome_completo, 
            pa.cargo, 
            pa.cpf,
            u.email
        FROM 
            profissionais_administrativos pa
        LEFT JOIN 
            usuarios u ON pa.id = u.id_referencia AND u.tipo_usuario = pa.cargo
        $where_clause
        ORDER BY 
            pa.nome_completo 
        LIMIT 
            $registros_por_pagina 
        OFFSET 
            $offset";

$resultado = mysqli_query($conexao, $sql);
?>

<div class="row">
    <div class="col-md-12">
        <h2>Gestão da Equipe Administrativa</h2>
        <p class="text-muted">Lista e gerencia Gerentes, Supervisores e outros cargos administrativos.</p>
        <hr>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-8">
        <form method="GET" class="d-flex">
            <input type="text" name="busca" class="form-control me-2" placeholder="Buscar por Nome, Cargo ou CPF..." value="<?= htmlspecialchars($termo_busca); ?>">
            <button class="btn btn-outline-secondary" type="submit">Buscar</button>
        </form>
    </div>
    <div class="col-md-4 text-end">
        <a href="../auth/registro.php?tipo=gerente" class="btn btn-primary">
            <i class="fas fa-plus"></i> Novo Colaborador
        </a>
    </div>
</div>

<table class="table table-striped table-hover">
    <thead>
        <tr>
            <th>Nome</th>
            <th>Cargo</th>
            <th>CPF</th>
            <th>Email de Login</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        if (mysqli_num_rows($resultado) > 0): 
            while ($profissional = mysqli_fetch_assoc($resultado)):
        ?>
        <tr>
            <td><?= htmlspecialchars($profissional['nome_completo']); ?></td>
            <td><?= htmlspecialchars(ucfirst($profissional['cargo'])); ?></td>
            <td><?= htmlspecialchars($profissional['cpf']); ?></td>
            <td>
                <?= htmlspecialchars($profissional['email'] ?? 'N/A'); ?>
            </td>
            <td>
                <a href="editar_admin.php?id=<?= $profissional['id'] ?>" class="btn btn-sm btn-info me-2">Editar</a>
                <a href="excluir_admin.php?id=<?= $profissional['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este profissional e a sua conta de login?');">Excluir</a>
            </td>
        </tr>
        <?php
            endwhile;
        else:
        ?>
        <tr>
            <td colspan="5" class="text-center">
                <?php if ($termo_busca): ?>
                    Nenhum profissional encontrado para a busca "<strong><?= htmlspecialchars($termo_busca); ?></strong>".
                <?php else: ?>
                    Nenhum profissional administrativo cadastrado.
                <?php endif; ?>
            </td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php if ($total_paginas > 1): ?>
<nav>
    <ul class="pagination justify-content-center">
        <?php $query_string = http_build_query($parametros_url); ?>
        <li class="page-item <?= ($pagina_atual <= 1) ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= $query_string ?>&pagina=<?= $pagina_atual - 1 ?>">Anterior</a>
        </li>
        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <li class="page-item <?= ($i == $pagina_atual) ? 'active' : '' ?>">
                <a class="page-link" href="?<?= $query_string ?>&pagina=<?= $i ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?= ($pagina_atual >= $total_paginas) ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= $query_string ?>&pagina=<?= $pagina_atual + 1 ?>">Próxima</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<?php require_once '../footer.php'; ?>