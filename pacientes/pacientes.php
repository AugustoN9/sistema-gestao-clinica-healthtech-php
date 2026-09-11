<?php


require_once '../header.php';
require_once '../conexao.php';
require_once '../calendario_vacinal.php';

// Otimização: Busca todas as vacinas aplicadas de uma só vez
$sql_todas_vacinas = "SELECT paciente_id, nome_vacina FROM vacinas";
$resultado_todas_vacinas = mysqli_query($conexao, $sql_todas_vacinas);
$todas_vacinas_aplicadas = [];
if ($resultado_todas_vacinas) {
    while ($vacina = mysqli_fetch_assoc($resultado_todas_vacinas)) {
        $todas_vacinas_aplicadas[$vacina['paciente_id']][] = strtolower($vacina['nome_vacina']);
    }
}

// LÓGICA DE BUSCA E PAGINAÇÃO
$registros_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_atual < 1) $pagina_atual = 1;
$termo_busca = $_GET['busca'] ?? '';
$parametros_url = [];
$where_conditions = [];
if (!empty($termo_busca)) {
    $termo_busca_seguro = mysqli_real_escape_string($conexao, $termo_busca);
    $where_conditions[] = "(nome LIKE '%$termo_busca_seguro%' OR cpf LIKE '%$termo_busca_seguro%')";
    $parametros_url['busca'] = $termo_busca;
}
$where_clause = count($where_conditions) > 0 ? " WHERE " . implode(' AND ', $where_conditions) : '';
$sql_total = "SELECT COUNT(*) AS total FROM pacientes $where_clause";
$resultado_total = mysqli_query($conexao, $sql_total);
$total_registros = mysqli_fetch_assoc($resultado_total)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);
$offset = ($pagina_atual - 1) * $registros_por_pagina;
$sql = "SELECT * FROM pacientes $where_clause ORDER BY nome ASC LIMIT $registros_por_pagina OFFSET $offset";
$resultado = mysqli_query($conexao, $sql);
?>
        
<div class="d-flex justify-content-between align-items-center">
    <h1>Lista de Pacientes</h1>
    <a href="adicionar_paciente.php" class="btn btn-primary"><i class="fas fa-plus"></i> Novo Paciente</a>
</div>
<hr>

<form action="pacientes.php" method="GET" class="mb-4">
    <div class="input-group">
        <input type="text" class="form-control" name="busca" placeholder="Buscar por nome ou CPF..." value="<?= htmlspecialchars($termo_busca); ?>">
        <button class="btn btn-success" type="submit">Buscar</button>
        <a href="pacientes.php" class="btn btn-info" type="button">Limpar Busca</a>
    </div>
</form>

<table class="table table-striped table-hover align-middle">
    <thead class="table-dark">
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Idade</th>
            <th>Perfil do Paciente</th>
            <th class="text-center">Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if ($resultado && mysqli_num_rows($resultado) > 0):
            while ($paciente = mysqli_fetch_assoc($resultado)):
                $tem_vacina_pendente = false;
                $idade = 'N/D';
                $perfil = 'N/D';
                if (!empty($paciente['data_nascimento'])) {
                    $data_nasc = new DateTime($paciente['data_nascimento']);
                    $hoje = new DateTime('now');
                    $idade = $data_nasc->diff($hoje)->y;
                    if ($idade <= 9) { $perfil = 'Criança'; } 
                    elseif ($idade <= 19) { $perfil = 'Adolescente'; } 
                    elseif ($idade <= 24) { $perfil = 'Jovem'; } 
                    elseif ($idade <= 59) { $perfil = 'Adulto'; } 
                    else { $perfil = 'Idoso'; }
                    $calendario_recomendado = getCalendarioVacinal($perfil);
                    $vacinas_deste_paciente = $todas_vacinas_aplicadas[$paciente['id']] ?? [];
                    foreach ($calendario_recomendado as $vacina_recomendada) {
                        if (is_array($vacina_recomendada) && isset($vacina_recomendada['vacina'])) {
                            $encontrou_vacina = false;
                            foreach ($vacinas_deste_paciente as $vacina_aplicada) {
                                if (stripos(strtolower($vacina_recomendada['vacina']), $vacina_aplicada) !== false || stripos($vacina_aplicada, strtolower($vacina_recomendada['vacina'])) !== false) {
                                    $encontrou_vacina = true;
                                    break;
                                }
                            }
                            if (!$encontrou_vacina) {
                                $tem_vacina_pendente = true;
                                break;
                            }
                        }
                    }
                }
        ?>
        <tr>
            <td><?= $paciente['id'] ?></td>
            <td><?= htmlspecialchars($paciente['nome'] ?? '') ?></td>
            <td><?= $idade ?></td>
            <td><?= $perfil ?></td>
            <td class="text-center">
                <?php if ($tem_vacina_pendente): ?>
                    <img src="../assets/images/vacina_pendente.png" alt="Alerta de vacina pendente" title="Existe vacina pendente!" style="width: 24px; height: 24px; vertical-align: middle;" class="me-2">
                <?php else: ?>
                    <span style="display: inline-block; width: 24px;" class="me-2"></span>
                <?php endif; ?>
                <a href="detalhes_paciente.php?id=<?= $paciente['id'] ?>" class="btn btn-secondary btn-sm" title="Ver Detalhes do Paciente"><i class="fas fa-magnifying-glass"></i></a>
                <a href="historico_vacinas.php?paciente_id=<?= $paciente['id'] ?>" class="btn btn-info btn-sm" title="Histórico de Vacinas"><i class="fas fa-syringe"></i></a>
            </td>
        </tr>
        <?php
            endwhile;
        else:
        ?>
        <tr>
            <td colspan="5" class="text-center">
                <?php if ($termo_busca): ?>
                    Nenhum paciente encontrado para a busca "<strong><?= htmlspecialchars($termo_busca); ?></strong>".
                <?php else: ?>
                    Nenhum paciente cadastrado.
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
        <li class="page-item <?= ($pagina_atual <= 1) ? 'disabled' : '' ?>"><a class="page-link" href="?<?= $query_string ?>&pagina=<?= $pagina_atual - 1 ?>">Anterior</a></li>
        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <li class="page-item <?= ($i == $pagina_atual) ? 'active' : '' ?>"><a class="page-link" href="?<?= $query_string ?>&pagina=<?= $i ?>"><?= $i ?></a></li>
        <?php endfor; ?>
        <li class="page-item <?= ($pagina_atual >= $total_paginas) ? 'disabled' : '' ?>"><a class="page-link" href="?<?= $query_string ?>&pagina=<?= $pagina_atual + 1 ?>">Próxima</a></li>
    </ul>
</nav>
<?php endif; ?>

<?php
require_once '../footer.php'; 
?>