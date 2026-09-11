<?php
// Este script não deve ser acessado diretamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    die('Acesso negado.');
}

function calcularEstatisticasConsultas($conexao) {
    
    // --- 1. KPIs (Cards do Topo) ---
    // (Lógica dos KPIs e Tendência não muda... já está completa)
    $stats = [];
    $hoje = date('Y-m-d');
    $mes_atual = date('m');
    $ano_atual = date('Y');
    $stats['kpis']['total_hoje'] = 0; //... (etc)
    $sql_hoje = "SELECT COUNT(*) as total FROM consultas WHERE data_consulta = ?";
    $stmt_hoje = mysqli_prepare($conexao, $sql_hoje);
    mysqli_stmt_bind_param($stmt_hoje, "s", $hoje);
    mysqli_stmt_execute($stmt_hoje);
    $stats['kpis']['total_hoje'] = mysqli_stmt_get_result($stmt_hoje)->fetch_assoc()['total'] ?? 0;
    $sql_mes = "SELECT COUNT(*) as total FROM consultas WHERE MONTH(data_consulta) = ? AND YEAR(data_consulta) = ?";
    $stmt_mes = mysqli_prepare($conexao, $sql_mes);
    mysqli_stmt_bind_param($stmt_mes, "ii", $mes_atual, $ano_atual);
    mysqli_stmt_execute($stmt_mes);
    $stats['kpis']['total_mes'] = mysqli_stmt_get_result($stmt_mes)->fetch_assoc()['total'] ?? 0;
    $sql_ano = "SELECT COUNT(*) as total FROM consultas WHERE YEAR(data_consulta) = ?";
    $stmt_ano = mysqli_prepare($conexao, $sql_ano);
    mysqli_stmt_bind_param($stmt_ano, "i", $ano_atual);
    mysqli_stmt_execute($stmt_ano);
    $stats['kpis']['total_ano'] = mysqli_stmt_get_result($stmt_ano)->fetch_assoc()['total'] ?? 0;
    $sql_disponivel = "SELECT COUNT(*) as total FROM disponibilidade_medicos WHERE data_disponivel >= ?";
    $stmt_disponivel = mysqli_prepare($conexao, $sql_disponivel);
    mysqli_stmt_bind_param($stmt_disponivel, "s", $hoje);
    mysqli_stmt_execute($stmt_disponivel);
    $total_slots_abertos = mysqli_stmt_get_result($stmt_disponivel)->fetch_assoc()['total'] ?? 0;
    $sql_agendadas = "SELECT COUNT(*) as total FROM consultas WHERE data_consulta >= ?";
    $stmt_agendadas = mysqli_prepare($conexao, $sql_agendadas);
    mysqli_stmt_bind_param($stmt_agendadas, "s", $hoje);
    mysqli_stmt_execute($stmt_agendadas);
    $total_slots_agendados = mysqli_stmt_get_result($stmt_agendadas)->fetch_assoc()['total'] ?? 0;
    if ($total_slots_abertos > 0) { $stats['kpis']['taxa_ocupacao'] = round(($total_slots_agendados / $total_slots_abertos) * 100, 1); }
    else { $stats['kpis']['taxa_ocupacao'] = 0; }
    $sql_espera = "SELECT AVG(DATEDIFF(data_consulta, DATE(criado_em))) as media_dias FROM consultas WHERE criado_em >= CURDATE() - INTERVAL 90 DAY AND data_consulta > criado_em";
    $resultado_espera = mysqli_query($conexao, $sql_espera);
    $media = mysqli_fetch_assoc($resultado_espera)['media_dias'] ?? 0;
    $stats['kpis']['tempo_medio_espera'] = round($media);
    $sql_tendencia = "SELECT data_consulta, COUNT(*) as total FROM consultas WHERE data_consulta BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE() GROUP BY data_consulta ORDER BY data_consulta ASC";
    $resultado_tendencia = mysqli_query($conexao, $sql_tendencia);
    $stats['kpis']['ultimos_30_dias'] = [];
    $dados_tendencia_brutos = [];
    if($resultado_tendencia) {
        while($row = mysqli_fetch_assoc($resultado_tendencia)) {
            $stats['kpis']['ultimos_30_dias'][] = $row;
            $dados_tendencia_brutos[$row['data_consulta']] = $row['total'];
        }
    }
    $data_15_dias_atras = date('Y-m-d', strtotime('-15 days'));
    $soma_recente = 0; $soma_antiga = 0;
    foreach ($dados_tendencia_brutos as $data => $total) {
        if ($data >= $data_15_dias_atras) { $soma_recente += $total; }
        else { $soma_antiga += $total; }
    }
    if ($soma_recente == 0 && $soma_antiga == 0) { $analise = "Não houve consultas nos últimos 30 dias para análise."; }
    elseif ($soma_recente > $soma_antiga) { $perc = $soma_antiga > 0 ? round((($soma_recente - $soma_antiga) / $soma_antiga) * 100) : 100; $analise = "<strong>Análise:</strong> A procura por consultas apresenta <strong>crescimento de {$perc}%</strong> nos últimos 15 dias. <br><strong>Ação:</strong> Continue a monitorar a taxa de ocupação dos médicos para evitar sobrecarga."; }
    elseif ($soma_recente < $soma_antiga) { $perc = round((($soma_antiga - $soma_recente) / $soma_antiga) * 100); $analise = "<strong>Análise:</strong> A procura por consultas apresenta <strong>queda de {$perc}%</strong> nos últimos 15 dias. <br><strong>Ação:</strong> Considere rever os horários disponíveis ou iniciar ações de marketing para especialidades com baixa procura."; }
    else { $analise = "<strong>Análise:</strong> A procura por consultas está <strong>estável</strong> em comparação com os 15 dias anteriores. <br><strong>Ação:</strong> Foco em otimizar a alocação de consultórios e horários de pico."; }
    $stats['kpis']['tendencia_analise'] = $analise;

    
    // --- 2. Inicialização dos Arrays de Gráficos Dinâmicos ---
    // Removendo 'Jovem' para padronizar com a lógica de idade (linha 85)
    $perfis = ['Geral', 'Gestante', 'Criança', 'Adolescente', 'Adulto', 'Idoso']; 
    $stats_brutas = []; 

    foreach ($perfis as $perfil) {
        $stats_brutas[$perfil] = [
            'consultas_por_especialidade' => [],
            'consultas_por_faixa_etaria' => [],
            'consultas_por_medico' => []
        ];
    }
    
    // --- 3. A "Giga-Query" ---
    $sql_mega_query = "SELECT 
                            c.especialidade, 
                            p.data_nascimento, 
                            p.genero, 
                            p.is_gestante,
                            m.nome_completo AS nome_medico
                       FROM consultas c
                       JOIN pacientes p ON c.paciente_id = p.id
                       JOIN medicos m ON c.medico_id = m.id
                       WHERE c.especialidade IS NOT NULL 
                         AND c.especialidade != ''
                         AND p.data_nascimento IS NOT NULL";
    
    $resultado_mega_query = mysqli_query($conexao, $sql_mega_query);
    if (!$resultado_mega_query) {
        die("Erro na consulta de estatísticas: " . mysqli_error($conexao));
    }

    // --- 4. Processamento dos Dados em PHP (ATUALIZADO) ---
    $hoje_dt = new DateTime('now');
    while ($consulta = mysqli_fetch_assoc($resultado_mega_query)) {
        // (Lógica de perfil e faixa etária existente)
        $data_nasc = new DateTime($consulta['data_nascimento']);
        if ($data_nasc->format('Y') <= 0 || $data_nasc->format('Y') > date('Y')) {
            continue; 
        }
        $idade = $data_nasc->diff($hoje_dt)->y;
        $especialidade = $consulta['especialidade'];
        $nome_medico = $consulta['nome_medico']; 
        
        // --- 4a. Perfil Principal (CORRIGIDO) ---
        $perfil_principal = 'N/D';
        if ($consulta['genero'] == 'Feminino' && ($consulta['is_gestante'] ?? 0) == 1) { $perfil_principal = 'Gestante'; }
        elseif ($idade <= 9) { $perfil_principal = 'Criança'; } 
        elseif ($idade <= 19) { $perfil_principal = 'Adolescente'; } 
        elseif ($idade <= 59) { $perfil_principal = 'Adulto'; } // Faixa 20-59 (Antigo Jovem/Adulto)
        elseif ($idade >= 60) { $perfil_principal = 'Idoso'; }
        if ($perfil_principal == 'N/D') continue;

        // --- 4b. Subfaixa Etária (CORRIGIDO) ---
        $faixa_etaria_sub = 'N/D';
        if ($perfil_principal == 'Criança') {
            if ($idade <= 3) { $faixa_etaria_sub = '0-3 anos'; }
            elseif ($idade <= 6) { $faixa_etaria_sub = '4-6 anos'; }
            else { $faixa_etaria_sub = '7-9 anos'; }
        } elseif ($perfil_principal == 'Adolescente') {
            if ($idade <= 14) { $faixa_etaria_sub = '10-14 anos'; }
            else { $faixa_etaria_sub = '15-19 anos'; }
        } 
        // Lógica de Adulto/Gestante agora começa em 20-29 anos (absorvendo a antiga faixa 'Jovem')
        elseif ($perfil_principal == 'Adulto' || $perfil_principal == 'Gestante') {
            if ($idade <= 29) { $faixa_etaria_sub = '20-29 anos'; }
            elseif ($idade <= 39) { $faixa_etaria_sub = '30-39 anos'; }
            elseif ($idade <= 49) { $faixa_etaria_sub = '40-49 anos'; }
            else { $faixa_etaria_sub = '50-59 anos'; }
        } elseif ($perfil_principal == 'Idoso') {
            if ($idade <= 69) { $faixa_etaria_sub = '60-69 anos'; }
            elseif ($idade <= 79) { $faixa_etaria_sub = '70-79 anos'; }
            else { $faixa_etaria_sub = '80+ anos'; }
        }

        // --- 4c. Incrementar os contadores ---
        
        // (Inicializa os contadores)
        if (!isset($stats_brutas['Geral']['consultas_por_especialidade'][$especialidade])) $stats_brutas['Geral']['consultas_por_especialidade'][$especialidade] = 0;
        if (!isset($stats_brutas['Geral']['consultas_por_faixa_etaria'][$perfil_principal])) $stats_brutas['Geral']['consultas_por_faixa_etaria'][$perfil_principal] = 0;
        if (!isset($stats_brutas['Geral']['consultas_por_medico'][$nome_medico])) $stats_brutas['Geral']['consultas_por_medico'][$nome_medico] = 0; 
        
        if (!isset($stats_brutas[$perfil_principal]['consultas_por_especialidade'][$especialidade])) $stats_brutas[$perfil_principal]['consultas_por_especialidade'][$especialidade] = 0;
        if (!isset($stats_brutas[$perfil_principal]['consultas_por_faixa_etaria'][$faixa_etaria_sub])) $stats_brutas[$perfil_principal]['consultas_por_faixa_etaria'][$faixa_etaria_sub] = 0;
        if (!isset($stats_brutas[$perfil_principal]['consultas_por_medico'][$nome_medico])) $stats_brutas[$perfil_principal]['consultas_por_medico'][$nome_medico] = 0; 

        // (Incrementa)
        $stats_brutas['Geral']['consultas_por_especialidade'][$especialidade]++;
        $stats_brutas['Geral']['consultas_por_faixa_etaria'][$perfil_principal]++; 
        $stats_brutas['Geral']['consultas_por_medico'][$nome_medico]++; 
        
        $stats_brutas[$perfil_principal]['consultas_por_especialidade'][$especialidade]++;
        $stats_brutas[$perfil_principal]['consultas_por_faixa_etaria'][$faixa_etaria_sub]++; 
        $stats_brutas[$perfil_principal]['consultas_por_medico'][$nome_medico]++; 
    }
    
    // --- 5. Processamento Final (Ordenar, Pegar Top 5, Formatar) ---
    $stats['graficos_dinamicos'] = [];
    
    foreach ($perfis as $perfil) {
        // (Top 5 Especialidades - existente)
        $esp = $stats_brutas[$perfil]['consultas_por_especialidade'];
        arsort($esp);
        $top5_especialidades = array_slice($esp, 0, 5, true); 
        
        // (Faixas Etárias - existente)
        $faixas = $stats_brutas[$perfil]['consultas_por_faixa_etaria'];
        ksort($faixas);
        
        // (Top 5 Médicos - NOVO)
        $meds = $stats_brutas[$perfil]['consultas_por_medico'];
        arsort($meds);
        $top5_medicos = array_slice($meds, 0, 5, true); 
        
        // (Adiciona os dados ao JSON final)
        $stats['graficos_dinamicos'][$perfil] = [
            'especialidade' => [
                'labels' => array_keys($top5_especialidades),
                'data' => array_values($top5_especialidades)
            ],
            'faixa_etaria' => [
                'labels' => array_keys($faixas),
                'data' => array_values($faixas)
            ],
            'medicos' => [ // <-- SEÇÃO DE MÉDICOS
                'labels' => array_keys($top5_medicos),
                'data' => array_values($top5_medicos)
            ]
        ];
    }
    
    return $stats;
}
?>