<?php
// 1. Proteção e Inclusões
require_once '../auth.php';
// Apenas Admin e Gerente podem ver a lista completa
proteger_pagina(['gerente', 'admin']); 
require_once '../conexao.php';
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
$params = [];
$param_types = '';

// Cláusula WHERE para busca (nome, coren, categoria)
if (!empty($termo_busca)) {
    $where_conditions[] = "(e.nome_completo LIKE ? OR e.coren LIKE ? OR e.categoria LIKE ?)";
    $params[] = "%$termo_busca%";
    $params[] = "%$termo_busca%";
    $params[] = "%$termo_busca%";
    $param_types .= 'sss';
    $parametros_url['busca'] = $termo_busca;
}

$where_clause = '';
if (count($where_conditions) > 0) {
    $where_clause = " WHERE " . implode(' AND ', $where_conditions);
}

// 1. Contagem Total para Paginação
$sql_total = "SELECT COUNT(*) AS total FROM enfermagem e $where_clause";
$stmt_total = mysqli_prepare($conexao, $sql_total);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt_total, $param_types, ...$params);
}
mysqli_stmt_execute($stmt_total);
$resultado_total = mysqli_stmt_get_result($stmt_total);
$total_registros = mysqli_fetch_assoc($resultado_total)['total'];
mysqli_stmt_close($stmt_total);

$total_paginas = ceil($total_registros / $registros_por_pagina);
$offset = ($pagina_atual - 1) * $registros_por_pagina;

// 2. Query Principal para Listagem (JOIN com usuarios para obter o email)
$sql = "SELECT 
            e.id, 
            e.nome_completo, 
            e.coren, 
            e.categoria,
            u.email
        FROM 
            enfermagem e
        LEFT JOIN 
            usuarios u ON e.id = u.id_referencia AND u.tipo_usuario = 'enfermagem'
        $where_clause
        ORDER BY 
            e.nome_completo ASC 
        LIMIT ? OFFSET ?";
        
$params_list = array_merge($params, [$registros_por_pagina, $offset]);
$param_types_list = $param_types . 'ii';

$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, $param_types_list, ...$params_list);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
?>

<div class="d-flex justify-content-between align-items-center">
    <h1>Gestão de Profissionais de Enfermagem</h1>
    <a href="adicionar_enfermagem.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Novo Profissional
    </a>
</div>
<hr>

<form method="GET" class="mb-4">
    <div class="input-group">
        <input type="text" class="form-control" name="busca" placeholder="Buscar por nome, COREN ou categoria..." value="<?= htmlspecialchars($termo_busca); ?>">
        <button class="btn btn-success" type="submit">Buscar</button>
        <a href="enfermagem.php" class="btn btn-info" type="button">Limpar Busca</a>
    </div>
</form>

<table class="table table-striped table-hover">
    <thead class="table-dark">
        <tr>
            <th>ID</th>
            <th>Nome Completo</th>
            <th>COREN</th>
            <th>Categoria</th>
            <th>Email de Login</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($resultado && mysqli_num_rows($resultado) > 0): ?>
            <?php while ($enf = mysqli_fetch_assoc($resultado)): ?>
                <tr>
                    <td><?= $enf['id'] ?></td>
                    <td><?= htmlspecialchars($enf['nome_completo'] ?? '') ?></td>
                    <td><?= htmlspecialchars($enf['coren'] ?? '') ?></td>
                    <td><?= htmlspecialchars(ucfirst($enf['categoria'] ?? '')) ?></td>
                    <td><?= htmlspecialchars($enf['email'] ?? 'N/A') ?></td>
                    <td>
                        <a href="editar_enfermagem.php?id=<?= $enf['id'] ?>" class="btn btn-warning btn-sm me-2">Editar</a>
                        <a href="excluir_enfermagem.php?id=<?= $enf['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir este profissional?');">Excluir</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" class="text-center">
                    <?php if ($termo_busca): ?>
                        Nenhum profissional de enfermagem encontrado.
                    <?php else: ?>
                        Nenhum profissional de enfermagem cadastrado.
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