<?php
// 1. Proteção e Conexão
require_once '../auth.php';
proteger_pagina(['medico']);
require_once '../conexao.php';

$medico_id_logado = $_SESSION['id_referencia'];
$data_selecionada = $_GET['data'] ?? date('Y-m-d');

// 2. Definir os horários (Regra de Negócio)
$horarios_manha = [];
$horarios_tarde = [];
// Manhã: 08:00 (início) até 12:00 (início da última) - 9 slots
for ($i = 0; $i < 9; $i++) {
    $horarios_manha[] = date('H:i:s', strtotime("08:00 + " . ($i * 30) . " minutes"));
}
// Tarde: 13:00 (início) até 17:00 (início da última) - 9 slots
for ($i = 0; $i < 9; $i++) {
    $horarios_tarde[] = date('H:i:s', strtotime("13:00 + " . ($i * 30) . " minutes"));
}

// 3. Buscar TODA a disponibilidade para o dia selecionado
$sql = "SELECT medico_id, horario_disponivel, consultorio FROM disponibilidade_medicos 
        WHERE data_disponivel = ?";
$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, "s", $data_selecionada);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

// 4. Mapear os slots ocupados para fácil verificação
$slots_ocupados = [];
$meus_slots_neste_dia = 0;
while ($row = mysqli_fetch_assoc($resultado)) {
    // Cria uma chave única: "consultorio-horario"
    $chave = $row['consultorio'] . '-' . $row['horario_disponivel'];
    $slots_ocupados[$chave] = (int)$row['medico_id'];
    if ((int)$row['medico_id'] === $medico_id_logado) {
        $meus_slots_neste_dia++;
    }
}

// 5. Gerar o HTML da grade (AGORA USANDO ACORDEÃO)
?>

<style>
/* Estilos mantidos e aprimorados */
.slot-grid { 
    display: flex;
    flex-wrap: wrap; 
    gap: 5px; /* Espaço entre os botões */
}
.slot-horario { 
    flex: 0 0 calc(33.333% - 5px); /* 3 itens por linha, com espaçamento */
    max-width: calc(33.333% - 5px);
}
.slot-horario .btn-check + .btn {
    width: 100%;
    border-color: #ced4da;
    color: #0d6efd;
    background-color: #fff;
}
.slot-horario .btn-check:checked + .btn {
    border-color: #0d6efd;
    background-color: #0d6efd;
    color: #fff;
}
.slot-horario .btn-check:disabled + .btn {
    border-color: #f0f0f0;
    background-color: #f8f9fa;
    color: #adb5bd;
    text-decoration: line-through;
    cursor: not-allowed;
}
.slot-horario .btn-check.slot-meu:checked + .btn {
    border-color: #198754;
    background-color: #198754;
    color: #fff;
}
/* Novo estilo para o header do turno dentro do acordeão */
.turno-header {
    background-color: #f8f9fa;
    padding: 10px;
    border-bottom: 1px solid #dee2e6;
    margin-bottom: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
</style>

<div class="alert alert-info">
    Selecione os horários desejados (Máximo 9 por dia).
    Seus horários já salvos estão marcados em verde.
</div>
<p class="text-center fw-bold">Horários selecionados para este dia: <span id="contador-slots" class="fs-5"><?= $meus_slots_neste_dia ?></span> / 9</p>
<hr>

<div class="accordion" id="accordionDisponibilidade">
    <?php for ($c = 1; $c <= 6; $c++): // Loop dos 6 consultórios ?>
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingConsultorio<?= $c ?>">
            <button class="accordion-button <?= ($c > 1) ? 'collapsed' : '' ?>" type="button" 
                    data-bs-toggle="collapse" data-bs-target="#collapseConsultorio<?= $c ?>" 
                    aria-expanded="<?= ($c == 1) ? 'true' : 'false' ?>" aria-controls="collapseConsultorio<?= $c ?>">
                Consultório <?= $c ?>
            </button>
        </h2>
        <div id="collapseConsultorio<?= $c ?>" class="accordion-collapse collapse <?= ($c == 1) ? 'show' : '' ?>" 
             aria-labelledby="headingConsultorio<?= $c ?>" data-bs-parent="#accordionDisponibilidade">
            <div class="accordion-body">
                
                <div class="card mb-4 border-info">
                    <div class="turno-header">
                        <h6 class="mb-0 text-info">Manhã (08:00 - 12:00)</h6>
                        <button type="button" class="btn btn-outline-info btn-sm btn-selecionar-turno" data-turno="manha" data-consultorio="<?= $c ?>">Selecionar/Desselecionar Turno</button>
                    </div>
                    
                    <div class="card-body slot-grid" id="consultorio-<?= $c ?>-manha">
                        <?php foreach ($horarios_manha as $horario):
                            $chave = $c . '-' . $horario;
                            $slot_id = "slot-{$c}-{$horario}";
                            $disabled = '';
                            $checked = '';
                            $classe_extra = '';

                            if (isset($slots_ocupados[$chave])) {
                                if ($slots_ocupados[$chave] === $medico_id_logado) {
                                    $checked = 'checked';
                                    $classe_extra = 'slot-meu';
                                } else {
                                    $disabled = 'disabled';
                                }
                            }
                        ?>
                        <div class="slot-horario">
                            <input type="checkbox" class="btn-check slot-checkbox" id="<?= $slot_id ?>" 
                                data-consultorio="<?= $c ?>" 
                                data-horario="<?= $horario ?>"
                                <?= $disabled ?> <?= $checked ?> class="<?= $classe_extra ?>">
                            <label class="btn btn-sm" for="<?= $slot_id ?>">
                                <?= date('H:i', strtotime($horario)) ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card border-primary">
                    <div class="turno-header">
                        <h6 class="mb-0 text-primary">Tarde (13:00 - 17:00)</h6>
                        <button type="button" class="btn btn-outline-primary btn-sm btn-selecionar-turno" data-turno="tarde" data-consultorio="<?= $c ?>">Selecionar/Desselecionar Turno</button>
                    </div>
                    
                    <div class="card-body slot-grid" id="consultorio-<?= $c ?>-tarde">
                        <?php foreach ($horarios_tarde as $horario):
                            $chave = $c . '-' . $horario;
                            $slot_id = "slot-{$c}-{$horario}";
                            $disabled = '';
                            $checked = '';
                            $classe_extra = '';

                            if (isset($slots_ocupados[$chave])) {
                                if ($slots_ocupados[$chave] === $medico_id_logado) {
                                    $checked = 'checked';
                                    $classe_extra = 'slot-meu';
                                } else {
                                    $disabled = 'disabled';
                                }
                            }
                        ?>
                        <div class="slot-horario">
                            <input type="checkbox" class="btn-check slot-checkbox" id="<?= $slot_id ?>" 
                                data-consultorio="<?= $c ?>" 
                                data-horario="<?= $horario ?>"
                                <?= $disabled ?> <?= $checked ?> class="<?= $classe_extra ?>">
                            <label class="btn btn-sm" for="<?= $slot_id ?>">
                                <?= date('H:i', strtotime($horario)) ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    <?php endfor; // Fim do loop de consultórios ?>
</div>