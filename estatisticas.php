<?php
// estatisticas.php

function calcularEstatisticas($conexao) {
    
    // ==========================================================
    // CORREÇÃO CRÍTICA: INCLUSÃO DA LÓGICA DO CALENDÁRIO
    // ==========================================================
    require_once 'calendario_vacinal.php'; // Adicionado para definir getCalendarioVacinal()
    // ==========================================================


    // --- PASSO 1: BUSCAR DADOS ---
    $pacientes_resultado = mysqli_query($conexao, "SELECT id, data_nascimento FROM pacientes");
    if (!$pacientes_resultado) { die("Erro ao buscar pacientes: " . mysqli_error($conexao)); }
    $todos_pacientes = [];
    while ($paciente = mysqli_fetch_assoc($pacientes_resultado)) {
        $todos_pacientes[] = $paciente;
    }
    
    $vacinas_resultado = mysqli_query($conexao, "SELECT paciente_id, nome_vacina FROM vacinas");
    if (!$vacinas_resultado) { die("Erro ao buscar vacinas: " . mysqli_error($conexao)); }
    $todas_vacinas_aplicadas = [];
    while ($vacina = mysqli_fetch_assoc($vacinas_resultado)) {
        // Armazena o nome da vacina em letras minúsculas para comparação case-insensitive
        $todas_vacinas_aplicadas[$vacina['paciente_id']][] = strtolower($vacina['nome_vacina']);
    }

    // --- PASSO 2: INICIALIZAR VARIÁVEIS ---
    $total_pacientes = count($todos_pacientes);
    $stats = [
        'total_pacientes' => $total_pacientes,
        'calendario_completo' => 0,
        'calendario_incompleto' => 0,
        'nao_vacinados' => 0,
        'status_por_perfil' => [
            'Criança' => ['total' => 0, 'completo' => 0, 'incompleto' => 0, 'nao_vacinado' => 0],
            'Adolescente' => ['total' => 0, 'completo' => 0, 'incompleto' => 0, 'nao_vacinado' => 0],
            'Adulto' => ['total' => 0, 'completo' => 0, 'incompleto' => 0, 'nao_vacinado' => 0],
            'Idoso' => ['total' => 0, 'completo' => 0, 'incompleto' => 0, 'nao_vacinado' => 0],
            'N/D' => ['total' => 0, 'completo' => 0, 'incompleto' => 0, 'nao_vacinado' => 0],
        ],
        'vacinas_por_perfil' => [
            'Criança' => [], 'Adolescente' => [], 'Adulto' => [], 'Idoso' => [], 'N/D' => [],
        ]
    ];

    if ($total_pacientes == 0) { 
        $stats['total_vacinas_aplicadas'] = []; // Garante que a chave existe
        return $stats; 
    }

    // --- PASSO 3: PROCESSAR CADA PACIENTE ---
    foreach ($todos_pacientes as $paciente) {
        $paciente_id = $paciente['id'];
        $perfil = 'N/D';
        
        // 3a. Determinação de Perfil (Corrigida e Padronizada com o Calendário)
        if (!empty($paciente['data_nascimento'])) {
             $data_nasc = new DateTime($paciente['data_nascimento']);
             $hoje = new DateTime('now');
             $idade = $data_nasc->diff($hoje)->y;
             
             if ($idade <= 9) { $perfil = 'Criança'; } 
             elseif ($idade <= 19) { $perfil = 'Adolescente'; } 
             elseif ($idade <= 59) { $perfil = 'Adulto'; } 
             else { $perfil = 'Idoso'; }
        }

        // Verifica se o perfil existe no array (para evitar erro com perfis como 'Jovem' se ainda estiverem no DB)
        if (!isset($stats['status_por_perfil'][$perfil])) {
            $perfil = 'N/D';
        }

        $stats['status_por_perfil'][$perfil]['total']++;
        $vacinas_deste_paciente = $todas_vacinas_aplicadas[$paciente_id] ?? [];

        // 3b. Contagem de Aplicações por Vacina e Perfil (para o Gráfico Top 3)
        foreach($vacinas_deste_paciente as $nome_vacina) {
            if (!isset($stats['vacinas_por_perfil'][$perfil][$nome_vacina])) {
                $stats['vacinas_por_perfil'][$perfil][$nome_vacina] = 0;
            }
            $stats['vacinas_por_perfil'][$perfil][$nome_vacina]++;
        }

        // 3c. Classificação do Status (Completo, Incompleto, Não Vacinado)
        if (empty($vacinas_deste_paciente)) {
            $stats['status_por_perfil'][$perfil]['nao_vacinado']++;
        } else {
            
            $calendario_recomendado = getCalendarioVacinal($perfil); 
            $calendario_esta_completo = true;
            
            // Lógica de Comparação Simples (Vacina Aplicada vs. Vacina Recomendada)
            if (!empty($calendario_recomendado)) {
                foreach ($calendario_recomendado as $vacina_recomendada) {
                    $encontrou_vacina = false;
                    foreach ($vacinas_deste_paciente as $vacina_aplicada) {
                        // Faz uma verificação case-insensitive, parcial (ex: 'dt' em 'dtpa')
                        if (stripos(strtolower($vacina_recomendada['vacina']), $vacina_aplicada) !== false || stripos($vacina_aplicada, strtolower($vacina_recomendada['vacina'])) !== false) {
                            $encontrou_vacina = true;
                            break;
                        }
                    }
                    if (!$encontrou_vacina) {
                        $calendario_esta_completo = false;
                        break;
                    }
                }
            }
            
            if ($calendario_esta_completo) {
                $stats['status_por_perfil'][$perfil]['completo']++;
            } else {
                $stats['status_por_perfil'][$perfil]['incompleto']++;
            }
        }
    }
    
    // --- PASSO 4: CALCULAR TOTAIS E CHAVES DE SAÍDA ---
    $stats['calendario_completo'] = array_sum(array_column($stats['status_por_perfil'], 'completo'));
    $stats['calendario_incompleto'] = array_sum(array_column($stats['status_por_perfil'], 'incompleto'));
    $stats['nao_vacinados'] = array_sum(array_column($stats['status_por_perfil'], 'nao_vacinado'));

    // Contagem Total de Vacinas Aplicadas (para o gráfico Top 3 Geral)
    $stats['total_vacinas_aplicadas'] = [];
    if (!empty($todas_vacinas_aplicadas)) {
        $contagem_vacinas_flat = [];
        foreach ($todas_vacinas_aplicadas as $paciente_vacinas) {
            $contagem_vacinas_flat = array_merge($contagem_vacinas_flat, $paciente_vacinas);
        }
        
        if (is_array($contagem_vacinas_flat)) {
             $stats['total_vacinas_aplicadas'] = array_count_values($contagem_vacinas_flat);
        }
    }
    
    return $stats;
}
?>