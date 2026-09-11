<?php
// Este arquivo contém a função que gera o plano de pré-natal

/**
 * Gera o cronograma de consultas de pré-natal com base na DUM.
 * A lógica é baseada nas recomendações do Min. Saúde (Mensal até 28s,
 * Quinzenal de 28-36s, Semanal de 36-40s).
 */
function gerarPlanoPrenatal($conexao, $paciente_id, $medico_id, $dum_string) {
    
    // PASSO 1: VERIFICAR SE O PLANO JÁ EXISTE
    // (Para evitar duplicatas se a paciente salvar o perfil várias vezes)
    $sql_check = "SELECT id FROM plano_prenatal WHERE paciente_id = ?";
    $stmt_check = mysqli_prepare($conexao, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "i", $paciente_id);
    mysqli_stmt_execute($stmt_check);
    $resultado_check = mysqli_stmt_get_result($stmt_check);
    
    if (mysqli_num_rows($resultado_check) > 0) {
        // O plano já existe. Não faz nada.
        return false;
    }

    // PASSO 2: CALCULAR AS DATAS
    $lista_consultas = [];
    $dum = new DateTime($dum_string);

    // Consulta 1 (inicial) - 4 semanas após a DUM
    $consulta_inicial = clone $dum;
    $consulta_inicial->add(new DateInterval('P4W')); // P4W = Período de 4 Semanas
    $lista_consultas[] = $consulta_inicial->format('Y-m-d');

    // Consultas MENSAIS (a cada 4 semanas) - da 8ª até a 28ª semana
    for ($semana = 8; $semana <= 28; $semana += 4) {
        $data_consulta = clone $dum;
        $data_consulta->add(new DateInterval("P{$semana}W"));
        $lista_consultas[] = $data_consulta->format('Y-m-d');
    }

    // Consultas QUINZENAIS (a cada 2 semanas) - da 30ª até a 36ª semana
    for ($semana = 30; $semana <= 36; $semana += 2) {
        $data_consulta = clone $dum;
        $data_consulta->add(new DateInterval("P{$semana}W"));
        $lista_consultas[] = $data_consulta->format('Y-m-d');
    }
    
    // Consultas SEMANAIS (a cada 1 semana) - da 37ª até a 40ª semana
    for ($semana = 37; $semana <= 40; $semana++) {
        $data_consulta = clone $dum;
        $data_consulta->add(new DateInterval("P{$semana}W"));
        $lista_consultas[] = $data_consulta->format('Y-m-d');
    }

    // PASSO 3: INSERIR AS DATAS NO BANCO
    $sql_insert = "INSERT INTO plano_prenatal (paciente_id, medico_id, data_prevista, status) 
                   VALUES (?, ?, ?, 'Pendente')";
    $stmt_insert = mysqli_prepare($conexao, $sql_insert);
    
    foreach ($lista_consultas as $data_prevista) {
        mysqli_stmt_bind_param($stmt_insert, "iis", $paciente_id, $medico_id, $data_prevista);
        mysqli_stmt_execute($stmt_insert);
    }
    
    return true;
}
?>