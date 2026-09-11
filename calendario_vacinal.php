<?php
// Arquivo: calendario_vacinal.php (AGORA COM LÓGICA DE SEPARAÇÃO)

// =================================================================
// DADOS DO CALENDÁRIO NACIONAL DE VACINAÇÃO (SIMPLIFICADO)
// =================================================================
function getCalendarioVacinal($perfil) {
    $calendarios = [
        'Criança' => [
            ['idade' => 'Ao Nascer', 'vacina' => 'BCG', 'dose' => 'Dose única', 'doencas' => 'Formas graves de tuberculose', 'descricao_simplificada' => 'Protege contra tuberculose'],
            ['idade' => 'Ao Nascer', 'vacina' => 'Hepatite B', 'dose' => '1ª dose', 'doencas' => 'Hepatite B', 'descricao_simplificada' => 'Protege contra Hepatite B'],
            // Adicione as outras vacinas de criança aqui...
            ['idade' => '1 ano e 3 meses', 'vacina' => 'DTP', 'dose' => '2º Reforço', 'doencas' => 'Difteria, Tétano e Coqueluche', 'descricao_simplificada' => 'Reforço contra Difteria, Tétano e Coqueluche'],
        ],
        'Adolescente' => [
            ['idade' => '10 a 19 anos', 'vacina' => 'HPV', 'dose' => '2 doses (meninas e meninos)', 'doencas' => 'Câncer de colo de útero, verrugas genitais', 'descricao_simplificada' => 'Prevenção de HPV'],
            ['idade' => '10 a 19 anos', 'vacina' => 'Meningocócica ACWY', 'dose' => 'Dose única/reforço', 'doencas' => 'Doenças meningocócicas', 'descricao_simplificada' => 'Protege contra meningite'],
            // Adicione as outras vacinas de adolescente aqui...
        ],
        'Adulto' => [
            ['idade' => '20 a 59 anos', 'vacina' => 'Hepatite B', 'dose' => '3 doses (conforme histórico vacinal)', 'doencas' => 'Hepatite B', 'descricao_simplificada' => 'Protege contra Hepatite B'],
            ['idade' => '20 a 59 anos', 'vacina' => 'dT', 'dose' => 'Reforço a cada 10 anos', 'doencas' => 'Difteria, tétano', 'descricao_simplificada' => 'Protege contra Difteria e Tétano'],
            ['idade' => '25 a 59 anos', 'vacina' => 'Febre Amarela', 'dose' => '1 dose (conforme histórico vacinal)', 'doencas' => 'Febre amarela', 'descricao_simplificada' => 'Protege contra Febre Amarela'],
            ['idade' => '25 a 59 anos', 'vacina' => 'Tríplice viral (SCR)', 'dose' => 'Até 29 anos: 2 doses. Entre 30 e 59 anos: 1 dose.', 'doencas' => 'Sarampo, caxumba, rubéola', 'descricao_simplificada' => 'Protege contra Sarampo, Caxumba, Rubéola'],
        ],
        'Gestante' => [
            // Note que as gestantes também devem ter o perfil Adulto/Idoso como base
            ['idade' => 'Gestação', 'vacina' => 'dTpa (Tríplice bacteriana acelular)', 'dose' => '1 dose a partir da 20ª semana', 'doencas' => 'Difteria, tétano e coqueluche (protege o bebê)', 'descricao_simplificada' => 'Protege o recém-nascido contra Coqueluche'],
            ['idade' => 'Gestação', 'vacina' => 'Hepatite B', 'dose' => '3 doses (conforme histórico vacinal)', 'doencas' => 'Hepatite B', 'descricao_simplificada' => 'Protege contra Hepatite B'],
            ['idade' => 'Gestação', 'vacina' => 'Influenza', 'dose' => '1 dose anual', 'doencas' => 'Gripe (Influenza)', 'descricao_simplificada' => 'Protege contra a gripe sazonal'],
        ],
        'Idoso' => [
            ['idade' => 'A partir de 60 anos', 'vacina' => 'Influenza', 'dose' => '1 dose anual', 'doencas' => 'Gripe (Influenza)', 'descricao_simplificada' => 'Protege contra a gripe sazonal'],
            ['idade' => 'A partir de 60 anos', 'vacina' => 'Pneumocócica 13', 'dose' => '1 dose', 'doencas' => 'Doenças Pneumocócicas', 'descricao_simplificada' => 'Protege contra pneumonia'],
            // Adicione as outras vacinas de idoso aqui...
        ],
    ];

    // Retorna as vacinas do perfil mais adequado.
    return $calendarios[$perfil] ?? [];
}

// =================================================================
// LÓGICA DE PRÉ-NATAL (MANTIDA)
// =================================================================
function calcular_proxima_consulta_plano($conexao, $paciente_id) {
    // Busca a última consulta de Obstetrícia Finalizada
    $sql_ultima = "SELECT data_consulta FROM consultas 
                   WHERE paciente_id = ? AND especialidade = 'Obstetrícia' AND status = 'Finalizada'
                   ORDER BY data_consulta DESC LIMIT 1";

    $stmt = mysqli_prepare($conexao, $sql_ultima);
    mysqli_stmt_bind_param($stmt, "i", $paciente_id);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $ultima_consulta = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);

    // Se houver última consulta, a próxima é em 4 semanas (28 dias)
    if ($ultima_consulta) {
        $data_base = new DateTime($ultima_consulta['data_consulta']);
        $data_base->add(new DateInterval('P28D')); 
        
        // Verifica se o paciente já tem consulta agendada para Obstetrícia após a data planeada
        $sql_agendada = "SELECT COUNT(*) FROM consultas 
                         WHERE paciente_id = ? AND especialidade = 'Obstetrícia' AND status = 'Agendada' 
                         AND data_consulta >= ?";
        $stmt_agendada = mysqli_prepare($conexao, $sql_agendada);
        $data_planeada_str = $data_base->format('Y-m-d');
        mysqli_stmt_bind_param($stmt_agendada, "is", $paciente_id, $data_planeada_str);
        mysqli_stmt_execute($stmt_agendada);
        $count_agendada = mysqli_stmt_get_result($stmt_agendada)->fetch_row()[0];
        mysqli_stmt_close($stmt_agendada);
        
        if ($count_agendada > 0) {
             return null;
        }

        return $data_base->format('Y-m-d');
    }
    
    // Se não houver consultas finalizadas, alerta para agendar a primeira.
    return date('Y-m-d'); // Retorna a data atual como alerta
}


// =================================================================
// FUNÇÃO DE FILTRO: BUSCA VACINAS JÁ AGENDADAS (NOVA VERSÃO QUE BUSCA DETALHES)
// =================================================================
function getVacinasAgendadasAguardandoDetalhes($conexao, $paciente_id) {
    $agendadas = [];
    // SQL ATUALIZADO para buscar data_agendamento e turno do agendamento principal
    $sql = "SELECT 
                avi.nome_vacina, 
                avi.dose_recomendada, 
                av.data_agendamento, 
                av.turno
            FROM 
                agendamento_vacinacao av
            JOIN 
                agendamento_vacinas_itens avi ON av.id = avi.agendamento_id
            WHERE 
                av.paciente_id = ? AND av.status = 'Aguardando'";
    
    $stmt = mysqli_prepare($conexao, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $paciente_id);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($resultado)) {
            // Usa a chave única 'Nome da Vacina|Dose' para fácil comparação e merge
            $chave_unica = $row['nome_vacina'] . '|' . ($row['dose_recomendada'] ?? 'Dose Única');
            $agendadas[$chave_unica] = $row;
        }
        mysqli_stmt_close($stmt);
    }
    return $agendadas;
}


// =================================================================
// FUNÇÃO PRINCIPAL: CALCULA PERFIL VACINAL E PENDÊNCIAS (MODIFICADA)
// =================================================================
function calcular_perfil_vacinal($conexao, $paciente, $paciente_id) {
    
    // 1. Determinar Idade e Perfil
    $data_nasc = new DateTime($paciente['data_nascimento']);
    $hoje = new DateTime();
    $diferenca = $hoje->diff($data_nasc);
    $anos = $diferenca->y;

    if ($paciente['is_gestante'] ?? false) {
        $perfil = 'Gestante';
    } elseif ($anos < 10) {
        $perfil = 'Criança';
    } elseif ($anos < 20) {
        $perfil = 'Adolescente';
    } elseif ($anos < 60) {
        $perfil = 'Adulto';
    } else {
        $perfil = 'Idoso';
    }
    
    $idade_str = ($anos == 0) ? ($diferenca->m . ' meses') : ($anos . ' anos');
    
    // 2. Buscar Vacinas JÁ TOMADAS (Simplificado: só busca o nome)
    $vacinas_tomadas = [];
    $sql_tomadas = "SELECT DISTINCT nome_vacina FROM vacinas WHERE paciente_id = ?";
    $stmt_tomadas = mysqli_prepare($conexao, $sql_tomadas);
    mysqli_stmt_bind_param($stmt_tomadas, "i", $paciente_id);
    mysqli_stmt_execute($stmt_tomadas);
    $resultado_tomadas = mysqli_stmt_get_result($stmt_tomadas);
    while ($row = mysqli_fetch_assoc($resultado_tomadas)) {
        $vacinas_tomadas[] = $row['nome_vacina'];
    }
    mysqli_stmt_close($stmt_tomadas);
    
    // 3. Buscar Vacinas RECOMENDADAS para o perfil
    $calendario = getCalendarioVacinal($perfil);
    
    // 4. Buscar Vacinas JÁ AGENDADAS (com detalhes de data/turno)
    $vacinas_agendadas_detalhes = getVacinasAgendadasAguardandoDetalhes($conexao, $paciente_id);
    
    $vacinas_pendentes_filtradas = []; // Vacinas para NOVO AGENDAMENTO
    $vacinas_agendadas_para_exibir = []; // Vacinas com agendamento ativo

    // 5. LÓGICA DE FILTRAGEM E SEPARAÇÃO
    foreach ($calendario as $item) {
        // Cria a chave única ('Nome da Vacina|Dose')
        $chave_dose = $item['vacina'] . '|' . ($item['dose'] ?? 'Dose Única');
        
        // Verifica se a vacina já está AGENDADA (status 'Aguardando')
        if (isset($vacinas_agendadas_detalhes[$chave_dose])) {
            
            // Adiciona a vacina ao array de exibição de agendadas, mesclando os detalhes do agendamento
            $vacinas_agendadas_para_exibir[$chave_dose] = array_merge($item, [
                'data_agendamento' => $vacinas_agendadas_detalhes[$chave_dose]['data_agendamento'],
                'turno' => $vacinas_agendadas_detalhes[$chave_dose]['turno']
            ]);
        } 
        // Verifica se a vacina NÃO foi tomada E NÃO está agendada
        elseif (!in_array($item['vacina'], $vacinas_tomadas)) { 
            $vacinas_pendentes_filtradas[$chave_dose] = $item;
        }
    }
    
    // 6. Retorno
    
    $total_pendentes = count($vacinas_pendentes_filtradas) + count($vacinas_agendadas_para_exibir);

    return [
        'idade' => $idade_str,
        'perfil' => $perfil,
        'vacinas_pendentes' => $vacinas_pendentes_filtradas, // Apenas as NÃO AGENDADAS
        'vacinas_agendadas_para_exibir' => $vacinas_agendadas_para_exibir, // NOVO!
        'total_nao_agendadas' => count($vacinas_pendentes_filtradas),
        'total_pendentes' => $total_pendentes, // Contagem total (agendadas + para agendar)
    ];
}
?>