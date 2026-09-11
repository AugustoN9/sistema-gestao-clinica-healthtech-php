<?php
// 1. Proteção da página
//require_once 'auth.php';
//proteger_pagina(['gerente']); 

// 2. Inclusões necessárias (apenas uma vez)
require_once '../header.php';
require_once '../conexao.php';

// =================================================================
// LÓGICA DE BUSCA E PAGINAÇÃO
// =================================================================

$registros_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_atual < 1) $pagina_atual = 1;

$termo_busca = $_GET['busca'] ?? '';
$parametros_url = [];
$where_conditions = [];

if (!empty($termo_busca)) {
    $termo_busca_seguro = mysqli_real_escape_string($conexao, $termo_busca);
    
    // A busca será feita nas colunas da tabela 'medicos'
    $where_conditions[] = "(m.nome_completo LIKE '%$termo_busca_seguro%' OR m.especialidade LIKE '%$termo_busca_seguro%')";
    
    $parametros_url['busca'] = $termo_busca;
}

$where_clause = '';
if (count($where_conditions) > 0) {
    // Note que adicionamos um 'm.' para referenciar a tabela 'medicos' (m) na cláusula WHERE
    $where_clause = " WHERE " . implode(' AND ', $where_conditions);
}

// -----------------------------------------------------------------
// ATUALIZAÇÃO 1: Query SQL para o TOTAL de Registros
// Usa o JOIN para poder aplicar o filtro (WHERE) corretamente
// -----------------------------------------------------------------
$sql_total = "SELECT 
                COUNT(*) AS total 
              FROM 
                medicos m
              LEFT JOIN 
                usuarios u ON m.id = u.id_referencia AND u.tipo_usuario = 'medico'
              $where_clause";

$resultado_total = mysqli_query($conexao, $sql_total);
$total_registros = mysqli_fetch_assoc($resultado_total)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);
$offset = ($pagina_atual - 1) * $registros_por_pagina;

// -----------------------------------------------------------------
// ATUALIZAÇÃO 2: Query SQL Principal (Seleção dos Dados)
// Usa JOIN para buscar o email e o LIMIT/OFFSET para a paginação
// -----------------------------------------------------------------
$sql = "SELECT 
            m.id, 
            m.nome_completo, 
            m.crm, 
            m.especialidade,
            u.email /* <--- NOVO CAMPO BUSCADO (u.email) */
        FROM 
            medicos m 
        LEFT JOIN 
            usuarios u ON m.id = u.id_referencia AND u.tipo_usuario = 'medico'
        $where_clause 
        ORDER BY 
            m.nome_completo ASC 
        LIMIT $registros_por_pagina OFFSET $offset";

$resultado = mysqli_query($conexao, $sql);
?>

<div class="d-flex justify-content-between align-items-center">
    <h1>Gerenciar Médicos</h1>
    <a href="adicionar_medico.php" class="btn btn-primary"><i class="fas fa-plus"></i> Novo Médico</a>
</div>
<hr>

<form action="medicos.php" method="GET" class="mb-4">
    <div class="input-group">
        <input type="text" class="form-control" name="busca" placeholder="Buscar por nome ou especialidade..." value="<?= htmlspecialchars($termo_busca); ?>">
        <button class="btn btn-success" type="submit">Buscar</button>
        <a href="medicos.php" class="btn btn-info" type="button">Limpar Busca</a>
    </div>
</form>

<table class="table table-striped table-hover">
    <thead class="table-dark">
        <tr>
            <th>ID</th>
            <th>Nome Completo</th>
            <th>CRM</th>
            <th>Especialidade</th>
            <th>Email</th> <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if ($resultado && mysqli_num_rows($resultado) > 0):
            while ($medico = mysqli_fetch_assoc($resultado)):
        ?>
        <tr>
            <td><?= $medico['id'] ?></td>
            <td><?= htmlspecialchars($medico['nome_completo'] ?? '') ?></td>
            <td><?= htmlspecialchars($medico['crm'] ?? '') ?></td>
            <td><?= htmlspecialchars($medico['especialidade'] ?? '') ?></td>
            <td><?= htmlspecialchars($medico['email'] ?? 'N/A') ?></td> <td>
                <a href="editar_medico.php?id=<?= $medico['id'] ?>" class="btn btn-warning btn-sm me-2">Editar</a>
                <a href="excluir_medico.php?id=<?= $medico['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir este médico?');">Excluir</a>
            </td>
        </tr>
        <?php
            endwhile;
        else:
        ?>
        <tr>
            <td colspan="6" class="text-center"> <?php if ($termo_busca): ?>
                    Nenhum médico encontrado para a busca "<strong><?= htmlspecialchars($termo_busca); ?></strong>".
                <?php else: ?>
                    Nenhum médico cadastrado.
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

<?php
require_once '../footer.php'; 
?>