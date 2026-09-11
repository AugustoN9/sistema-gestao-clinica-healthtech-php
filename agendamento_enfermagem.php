<?php
// 1. Proteção e Conexão
require_once 'auth.php';
proteger_pagina(['paciente']); // Apenas pacientes podem aceder
require_once 'conexao.php';

// 2. Validar o POST
// Verifica se o formulário foi enviado e se pelo menos uma vacina foi marcada
if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($_POST['vacinas_selecionadas'])) {
    // Se o paciente chegou aqui sem marcar vacinas, redireciona-o de volta
    header("Location: area_paciente.php?erro=" . urlencode("Nenhuma vacina foi selecionada."));
    exit();
}

// 3. Armazena as vacinas selecionadas
$vacinas_a_agendar = $_POST['vacinas_selecionadas'];

require_once 'header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4>Agendamento de Vacinação</h4>
                </div>
                <div class="card-body">
                    <p class="lead">Você está agendando a aplicação das seguintes vacinas:</p>
                    
                    <ul class="list-group list-group-flush mb-4">
                        <?php foreach ($vacinas_a_agendar as $vacina_nome): ?>
                            <li class="list-group-item">
                                <i class="fas fa-syringe me-2 text-success"></i>
                                <strong><?= htmlspecialchars($vacina_nome) ?></strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <p>Por favor, escolha o **dia** e o **turno** de sua preferência. A aplicação será por ordem de chegada dentro do turno selecionado.</p>
                    
                    <form action="salvar_agendamento_vacina.php" method="POST">
                        
                        <?php foreach ($vacinas_a_agendar as $vacina_nome): ?>
                            <input type="hidden" name="vacinas_para_salvar[]" value="<?= htmlspecialchars($vacina_nome) ?>">
                        <?php endforeach; ?>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="data_agendamento" class="form-label">Data Desejada:</label>
                                <input type="date" class="form-control" id="data_agendamento" name="data_agendamento" 
                                       required min="<?= date('Y-m-d') // Impede agendamento em datas passadas ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="turno" class="form-label">Turno:</label>
                                <select class="form-select" id="turno" name="turno" required>
                                    <option value="">Selecione...</option>
                                    <option value="manha">Manhã (08:00 - 12:00)</option>
                                    <option value="tarde">Tarde (13:00 - 17:00)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 mt-3">
                            <button type="submit" class="btn btn-primary btn-lg">Confirmar Agendamento</button>
                            <a href="area_paciente.php" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>