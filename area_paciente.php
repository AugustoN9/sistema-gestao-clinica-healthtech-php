<?php

// area_paciente.php

// 1. Proteção e Inclusões
require_once 'auth.php';
proteger_pagina(['paciente']); // Apenas pacientes podem aceder
require_once 'conexao.php';
require_once 'header.php'; 
require_once 'calendario_vacinal.php'; // Contém a lógica de perfil e plano

$paciente_id = $_SESSION['id_referencia'];
$paciente_nome = $_SESSION['usuario_nome'];

// =================================================================
// NOVO BLOCO: CAMPANHAS DE CONSCIENTIZAÇÃO DO MÊS
// =================================================================
$campanhas_saude = [
    1 => ['nome' => 'Janeiro Branco', 'cor' => 'info', 'mensagem' => 'O Janeiro Branco é dedicado ao Combate à <strong>Depressão e Saúde Mental</strong>. Não adie o cuidado psicológico.', 'target' => 'A'],
    2 => ['nome' => 'Fevereiro Roxo/Laranja', 'cor' => 'secondary', 'mensagem' => 'Fevereiro é o mês de combate ao **Alzheimer** (Roxo) e à **Leucemia** (Laranja). Conheça os sinais.', 'target' => 'A'],
    3 => ['nome' => 'Março Lilás/Azul Marinho', 'cor' => 'primary', 'mensagem' => 'O Março é dedicado ao combate ao **Câncer de Colo do Útero** (Lilás) e ao **Câncer Colorretal** (Azul Marinho).', 'target' => 'F'],
    4 => ['nome' => 'Abril Verde', 'cor' => 'success', 'mensagem' => 'O Abril Verde foca na **Prevenção de Acidentes de Trabalho**. Sua segurança é a nossa prioridade.', 'target' => 'A'],
    5 => ['nome' => 'Maio Roxo/Amarelo', 'cor' => 'warning', 'mensagem' => 'Maio promove o combate às **Doenças Inflamatórias Intestinais** (Roxo) e a **Prevenção de Acidentes de Trânsito** (Amarelo).', 'target' => 'A'],
    6 => ['nome' => 'Junho Vermelho', 'cor' => 'danger', 'mensagem' => 'Junho Vermelho: **Incentivo à Doação de Sangue**. Um ato simples pode salvar até quatro vidas.', 'target' => 'A'],
    7 => ['nome' => 'Julho Amarelo', 'cor' => 'warning', 'mensagem' => 'O Julho Amarelo conscientiza sobre o combate às **Hepatites Virais**. Mantenha sua vacinação em dia.', 'target' => 'A'],
    8 => ['nome' => 'Agosto Laranja/Dourado', 'cor' => 'secondary', 'mensagem' => 'Agosto foca na **Esclerose Múltipla** (Laranja) e no **Aleitamento Materno** (Dourado). Informação é saúde.', 'target' => 'A'],
    9 => ['nome' => 'Setembro Amarelo/Verde/Vermelho', 'cor' => 'warning', 'mensagem' => 'Setembro: **Prevenção ao Suicídio** (Amarelo), **Doação de Órgãos** (Verde) e **Doença do Coração** (Vermelho). Procure ajuda, doe vida.', 'target' => 'A'],
    10 => ['nome' => 'Outubro Rosa', 'cor' => 'danger', 'mensagem' => 'Outubro Rosa: **Conscientização sobre o Câncer de Mama**. Faça seu autoexame e mamografia anualmente.', 'target' => 'F'],
    11 => ['nome' => 'Novembro Azul', 'cor' => 'primary', 'mensagem' => 'Novembro Azul: <strong>Mês Mundial de Combate ao Câncer de Próstata</strong>. O diagnóstico precoce salva vidas. Agende seu exame.', 'target' => 'M'],
    12 => ['nome' => 'Dezembro Vermelho/Laranja', 'cor' => 'danger', 'mensagem' => 'Dezembro foca na prevenção ao <strong>HIV (Aids)</strong> (Vermelho) e <strong>Câncer de Pele</strong> (Laranja). Use protetor solar e preserve sua saúde.', 'target' => 'A'],
];
$mes_atual = (int)date('n'); // Pega o número do mês (1 a 12)
$alerta_campanha = $campanhas_saude[$mes_atual] ?? null;
// =================================================================


// =================================================================
// FRASES DE ACOLHIMENTO PARA GESTANTES (20 Frases)
// =================================================================
$frases_acolhimento = [
    "Parabéns! Sua jornada de amor e transformação começou. Conte com a gente para cada passo.",
    "Lembre-se: cuidar de si mesma é cuidar do seu bebê. Reserve um momento para o seu bem-estar hoje.",
    "Cada dia é uma nova descoberta. Você está fazendo um trabalho incrível, mamãe!",
    "Sua força é admirável. Respire fundo e aproveite a magia deste momento único.",
    "Bebês sentem tudo. Que sua jornada seja repleta de paz, alegria e muito carinho.",
    "A hidratação é essencial! Lembre-se de beber água e manter-se nutrida para você e seu bebê.",
    "Priorize o seu descanso. Seu corpo está trabalhando duro para gerar uma nova vida.",
    "Não hesite em partilhar suas dúvidas e sentimentos. Estamos aqui para apoiar você.",
    "Seu corpo é perfeito e está em constante adaptação. Celebre essa transformação!",
    "Prepare-se para o maior amor da sua vida! O HealthTech está com você.",
    "Sua saúde é nossa prioridade. Mantenha os exames e consultas em dia.",
    "Uma alimentação equilibrada faz toda a diferença para o seu bem-estar e o do bebê.",
    "Movimente-se! Exercícios leves são ótimos aliados da gestação (sob orientação médica).",
    "Você é mais forte do que imagina. A maternidade é uma prova de força e resiliência.",
    "Tire um momento para conversar com o seu bebê. O vínculo começa agora.",
    "Comunicação é chave. Fale com seu parceiro(a) e família sobre como se sente.",
    "Cada ultrassom é uma janela para o seu futuro. Aproveite o momento!",
    "Lembre-se de tomar seus suplementos. Eles são vitais para o desenvolvimento do seu bebê.",
    "A ansiedade é normal. Use técnicas de relaxamento e meditação.",
    "Você é uma gestante maravilhosa e inspiradora! Receba nosso carinho."
];

// 2. BUSCA DO PERFIL PARA EXIBIÇÃO DA FRASE E PLANO VACINAL
$perfil_paciente = [];
// START: QUERY ATUALIZADA PARA INCLUIR NOVAS VIROSES
$sql_perfil = "SELECT 
                    data_nascimento, genero, is_gestante, medico_prenatal_id, data_ultima_menstruacao,
                    tipo_sanguineo, alergias_conhecidas, is_diabetico, is_cardiaco, is_hipertenso,
                    is_asma, is_bronquite_cronica, is_dpoc, is_rinite_sinusite, is_fumante,
                    is_dengue, is_chikungunya, is_zika, is_covid19, /* NOVAS VIROSES */
                    ultimo_imc_valor, ultimo_imc_classificacao
                FROM pacientes WHERE id = ?";
// END: QUERY ATUALIZADA
$stmt_perfil = mysqli_prepare($conexao, $sql_perfil);
mysqli_stmt_bind_param($stmt_perfil, "i", $paciente_id);
mysqli_stmt_execute($stmt_perfil);
$paciente = mysqli_stmt_get_result($stmt_perfil)->fetch_assoc();
mysqli_stmt_close($stmt_perfil);

// Define o gênero do paciente para a filtragem da campanha
$genero_paciente = substr($paciente['genero'] ?? 'Outro', 0, 1); // Pega 'M', 'F' ou 'O'

// Lógica Pré-Natal (Se for gestante)
$proxima_consulta_plano = null; 
if ($paciente && $paciente['is_gestante'] == 1) {
    // Se for gestante, seleciona uma frase aleatória
    $frase_do_dia = $frases_acolhimento[array_rand($frases_acolhimento)];
    // Assumindo que esta função existe no calendario_vacinal.php
    // $proxima_consulta_plano = calcular_proxima_consulta_plano($conexao, $paciente_id);
}


// 3. BUSCA DE CONSULTAS AGENDADAS
$consultas_agendadas = [];
$sql_consultas = "SELECT c.id, c.data_consulta, c.horario_consulta, c.especialidade, c.status, m.nome_completo as nome_medico 
                  FROM consultas c
                  JOIN medicos m ON c.medico_id = m.id
                  -- FILTRO PRINCIPAL: Todas as consultas de 2 meses atrás até o futuro
                  WHERE c.paciente_id = ? 
                  AND c.data_consulta >= DATE_SUB(CURDATE(), INTERVAL 2 MONTH)
                  -- STATUS: Inclui Agendadas (futuras), Concluídas e Canceladas (recentes)
                  AND c.status IN ('Agendada', 'Aguardando Médico', 'Concluída', 'Cancelada')
                  -- ALTERAÇÃO AQUI: De ASC para DESC
                  ORDER BY c.data_consulta DESC, c.horario_consulta DESC
                  LIMIT 10";

$stmt_consultas = mysqli_prepare($conexao, $sql_consultas);
mysqli_stmt_bind_param($stmt_consultas, "i", $paciente_id);
mysqli_stmt_execute($stmt_consultas);
$resultado_consultas = mysqli_stmt_get_result($stmt_consultas);

while ($row = mysqli_fetch_assoc($resultado_consultas)) {
    $consultas_agendadas[] = $row;
}
mysqli_stmt_close($stmt_consultas);


// 3.1. BUSCA DE EXAMES SOLICITADOS (A REALIZAR)
$exames_solicitados = [];
$sql_exames_solicitados = "SELECT 
                            pe.id, 
                            pe.status, 
                            pe.data_pedido, 
                            pe.data_agendamento_paciente, 
                            pe.turno_agendamento_paciente, 
                            e.nome_exame, 
                            m.nome_completo AS nome_medico,
                            lp.nome_empresa AS nome_laboratorio,
                            lp.telefone AS lab_telefone, 
                            lp.endereco_completo AS lab_endereco,
                            lp.latitude AS lab_latitude, 
                            lp.longitude AS lab_longitude 
                         FROM pedidos_exames pe
                         JOIN exames e ON pe.exame_id = e.id
                         JOIN medicos m ON pe.medico_id = m.id
                         JOIN laboratorios_parceiros lp ON pe.laboratorio_direcionado_id = lp.id
                         WHERE pe.paciente_id = ? AND (pe.status = 'Solicitado' OR pe.data_agendamento_paciente IS NOT NULL)
                         ORDER BY pe.data_pedido DESC";

$stmt_exames_solicitados = mysqli_prepare($conexao, $sql_exames_solicitados);
mysqli_stmt_bind_param($stmt_exames_solicitados, "i", $paciente_id);
mysqli_stmt_execute($stmt_exames_solicitados);
$resultado_exames_solicitados = mysqli_stmt_get_result($stmt_exames_solicitados);

while ($row = mysqli_fetch_assoc($resultado_exames_solicitados)) {
    $exames_solicitados[] = $row;
}
mysqli_stmt_close($stmt_exames_solicitados);


// 3.2. NOVO: BUSCA DE HISTÓRICO DE VACINAS APLICADAS
$vacinas_aplicadas_historico = [];
$sql_aplicadas = "SELECT data_aplicacao, nome_vacina, dose, marca, lote 
                  FROM vacinas 
                  WHERE paciente_id = ? 
                  ORDER BY data_aplicacao DESC";
$stmt_aplicadas = mysqli_prepare($conexao, $sql_aplicadas);
mysqli_stmt_bind_param($stmt_aplicadas, "i", $paciente_id);
mysqli_stmt_execute($stmt_aplicadas);
$resultado_aplicadas = mysqli_stmt_get_result($stmt_aplicadas);

while ($row = mysqli_fetch_assoc($resultado_aplicadas)) {
    $vacinas_aplicadas_historico[] = $row;
}
mysqli_stmt_close($stmt_aplicadas);


// 4. LÓGICA DO CALENDÁRIO VACINAL
$dados_vacinais = calcular_perfil_vacinal($conexao, $paciente, $paciente_id); 
$perfil = $dados_vacinais['perfil'];

$vacinas_para_tabela = array_merge(
    $dados_vacinais['vacinas_pendentes'], 
    $dados_vacinais['vacinas_agendadas_para_exibir']
);


// =================================================================
// BLOCO: GERAÇÃO DO ALERTA DE IMUNIZAÇÃO CONTEXTUAL
// =================================================================
$alerta_imunizacao = '';
$vacina_prioritaria = null;
foreach ($dados_vacinais['vacinas_pendentes'] as $chave => $vacina) {
    $vacina_prioritaria = $vacina;
    break; 
}
$qtd_pendentes = $dados_vacinais['total_pendentes'] ?? 0;
$idade_paciente = $idade ?? 0;
$paciente_nome_formatado = htmlspecialchars($paciente_nome);

if ($vacina_prioritaria) {
    $nome_vacina = htmlspecialchars($vacina_prioritaria['vacina']);
    $idade_sugerida = htmlspecialchars($vacina_prioritaria['idade']);
    $doencas_protege = htmlspecialchars($vacina_prioritaria['doencas']);
    $alerta_tipo = 'info'; 
    $titulo = '';
    $mensagem = '';
    
    // Usar SWITCH baseado no perfil é mais robusto
    switch ($perfil) {
        case 'Gestante':
            $titulo = '<i class="fas fa-exclamation-circle me-2"></i>PRIORIDADE MÁXIMA: Proteção Dupla!';
            // CORREÇÃO: Usando <strong> diretamente
            $mensagem = "O agendamento da vacina <strong>{$nome_vacina}</strong> é crucial para proteger <strong>você e o seu bebê</strong>. A imunização passa anticorpos vitais para o feto. Não adie esta proteção!";
            $alerta_tipo = 'danger';
            break;
            
        case 'Idoso':
            $titulo = '<i class="fas fa-medkit me-2"></i>Cuide da sua Longevidade!';
            // CORREÇÃO: Usando <strong> diretamente
            $mensagem = "Vimos que a vacina <strong>{$nome_vacina}</strong> está pendente. Ela é essencial para prevenir complicações graves de saúde comuns na sua faixa etária. Agende sua visita de proteção.";
            $alerta_tipo = 'primary';
            break;
            
        case 'Criança':
        case 'Adolescente':
            $titulo = '<i class="fas fa-child me-2"></i>Mensagem para os Responsáveis';
            // CORREÇÃO: Usando <strong> diretamente
            $mensagem = "A vacina <strong>{$nome_vacina}</strong> está pendente no calendário de <strong>{$idade_sugerida}</strong> do(a) <strong>{$paciente_nome_formatado}</strong>. A proteção infantil é urgente para garantir o desenvolvimento saudável e a segurança.";
            $alerta_tipo = 'info';
            break;
            
        case 'Adulto':
            if ($qtd_pendentes > 3) {
                // Adulto Alta Urgência
                $titulo = '<i class="fas fa-exclamation-triangle me-2"></i>ALERTA: Múltiplas Doses Pendentes!';
                // CORREÇÃO: Usando <strong> diretamente
                $mensagem = "Com <strong>{$qtd_pendentes} doses pendentes</strong>, sua imunidade está seriamente comprometida. A vacina <strong>{$nome_vacina}</strong> é a próxima prioridade para mitigar os riscos.";
                $alerta_tipo = 'danger';
            } else {
                // Adulto Baixa Urgência
                $titulo = '<i class="fas fa-check-circle me-2"></i>Mantenha sua Proteção em Dia';
                // CORREÇÃO: Usando <strong> diretamente
                $mensagem = "A vacina <strong>{$nome_vacina}</strong> está pendente no calendário de <strong>{$idade_sugerida}</strong>. É uma dose rápida, mas essencial para manter sua imunidade completa. Agende hoje mesmo!";
                $alerta_tipo = 'info';
            }
            break;
    }

    if (isset($titulo) && !empty($titulo)) {
         $alerta_imunizacao = "
            <div class='alert alert-{$alerta_tipo} mt-3'>
                <h5 class='alert-heading'>{$titulo}</h5>
                <p>{$mensagem}</p>
                <p class='small mt-2'><strong>Vantagem:</strong> Proteção contra {$doencas_protege}. Nossas vacinas são seguras e certificadas.</p>
            </div>
         ";
    }
}
// =================================================================

// =================================================================
// NOVO BLOCO: GERAÇÃO DO HUB DE LEMBRETES (MÉTRICAS)
// =================================================================
$lembretes = [];

// 1. LEMBRETE: CONSULTAS A VENCER (Próximos 7 dias)
$hoje_dt = new DateTime();
$proxima_semana_dt = new DateTime('+7 days');

$sql_prox_consultas = "SELECT 
                        c.data_consulta, c.horario_consulta, c.especialidade, m.nome_completo as nome_medico 
                      FROM consultas c
                      JOIN medicos m ON c.medico_id = m.id
                      WHERE c.paciente_id = ? AND c.status IN ('Agendada', 'Aguardando Médico')
                      AND c.data_consulta BETWEEN ? AND ?
                      ORDER BY c.data_consulta ASC";

$stmt_prox = mysqli_prepare($conexao, $sql_prox_consultas);
$hoje_str = $hoje_dt->format('Y-m-d');
$proxima_semana_str = $proxima_semana_dt->format('Y-m-d');
mysqli_stmt_bind_param($stmt_prox, "iss", $paciente_id, $hoje_str, $proxima_semana_str);
mysqli_stmt_execute($stmt_prox);
$resultado_prox = mysqli_stmt_get_result($stmt_prox);
while ($c = mysqli_fetch_assoc($resultado_prox)) {
    $data_formatada = date('d/m', strtotime($c['data_consulta']));
    $hora_formatada = date('H:i', strtotime($c['horario_consulta']));
    // CORREÇÃO: Usando <strong> diretamente
    $lembretes[] = [
        'type' => 'Consulta',
        'urgency' => 'high',
        'message' => "Você tem uma consulta agendada para <strong>{$data_formatada}</strong> às <strong>{$hora_formatada}</strong> com Dr(a). {$c['nome_medico']} ({$c['especialidade']})."
    ];
}
mysqli_stmt_close($stmt_prox);


// 2. LEMBRETE: EXAMES PENDENTES DE AGENDAMENTO
$exames_a_agendar = array_filter($exames_solicitados, function($exame) {
    return empty($exame['data_agendamento_paciente']);
});
$qtd_exames_agendar = count($exames_a_agendar);

if ($qtd_exames_agendar > 0) {
    $primeiro_exame = reset($exames_a_agendar);
    $nome_exame = htmlspecialchars($primeiro_exame['nome_exame']);
    $urgency_class = ($qtd_exames_agendar > 1) ? 'danger' : 'warning';
    
    // CORREÇÃO: Usando <strong> diretamente
    $lembretes[] = [
        'type' => 'Exame',
        'urgency' => $urgency_class,
        'message' => "Você tem <strong>{$qtd_exames_agendar} exame(s) solicitado(s)</strong> ({$nome_exame} e mais) aguardando agendamento. Agende na seção 'Exames a Realizar'."
    ];
}


// 3. LEMBRETE: VACINAS PENDENTES (Total Consolidado)
if ($qtd_pendentes > 0) {
    $singular = ($qtd_pendentes === 1) ? 'vacina' : 'vacinas';
    // CORREÇÃO: Usando <strong> diretamente
    $lembretes[] = [
        'type' => 'Vacina',
        'urgency' => 'info',
        'message' => "Você tem <strong>{$qtd_pendentes} {$singular}</strong> que ainda requerem atenção (pendentes de agendamento ou aplicação)."
    ];
}

// 4. Se não houver lembretes ativos, adiciona o status de sucesso.
if (empty($lembretes)) {
    $lembretes[] = [
        'type' => 'Sucesso',
        'urgency' => 'success',
        'message' => "Parabéns! Nenhuma ação imediata necessária nesta semana."
    ];
}
// =================================================================


// =================================================================
// ESTRUTURA HTML COM LAYOUT REVERTIDO
// =================================================================
require_once 'header.php';

?>

<div class="container mt-5">
    <h1>Bem-vindo(a), <?= htmlspecialchars($paciente_nome) ?>!</h1>
    <p class="lead">Sua Área de Paciente: Acompanhamento de Saúde.</p>

    <?php 
    if (isset($_GET['sucesso'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-1"></i> Agendamento realizado com sucesso! A clínica confirmará em breve.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['erro'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-1"></i> Erro ao agendar: <?= htmlspecialchars($_GET['erro']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <hr>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100 border-info">
                <div class="card-header bg-info text-white"><i class="fas fa-user me-2"></i>Seu Perfil de Saúde</div>
                
                <div class="card-body">
                    <?php 
                        $data_nasc_str = $paciente['data_nascimento'] ?? date('Y-m-d');
                        $idade = (new DateTime($data_nasc_str))->diff(new DateTime())->y;
                    ?>
                    <p><strong>Idade:</strong> <?= htmlspecialchars($idade ?? 'N/D') ?></p>
                    <p><strong>Gênero:</strong> <?= htmlspecialchars($paciente['genero'] ?? 'N/D') ?></p>
                    <p><strong>Tipo Sanguíneo:</strong> <span class="badge bg-secondary"><?= htmlspecialchars($paciente['tipo_sanguineo'] ?? 'N/D') ?></span></p>
                    <p><strong>IMC:</strong> <?= htmlspecialchars($paciente['ultimo_imc_valor'] ?? 'N/D') ?> <span class="badge bg-warning text-dark"><?= htmlspecialchars($paciente['ultimo_imc_classificacao'] ?? 'N/D') ?></span></p>
                    
                    <hr>
                    <p><strong>Perfil Vacinal:</strong> <span class="badge bg-primary"><?= htmlspecialchars($perfil ?? 'N/D') ?></span></p>
                    
                    <h6>Histórico e Alergias:</h6>
                    
                    <?php
                        $condicoes_cardio = [];
                        $condicoes_respiratorias = [];
                        $condicoes_habito = [];
                        $condicoes_viroses = []; // NOVO ARRAY PARA VIROSES
                        $todas_condicoes = [];

                        // 1. Metabólicas e Cardiovasculares
                        if (($paciente['is_diabetico'] ?? 0) == 1) $condicoes_cardio[] = 'Diabético';
                        if (($paciente['is_cardiaco'] ?? 0) == 1) $condicoes_cardio[] = 'Cardíaco';
                        if (($paciente['is_hipertenso'] ?? 0) == 1) $condicoes_cardio[] = 'Hipertenso';

                        // 2. Patologias Respiratórias (NOVAS)
                        if (($paciente['is_asma'] ?? 0) == 1) $condicoes_respiratorias[] = 'Asma';
                        if (($paciente['is_bronquite_cronica'] ?? 0) == 1) $condicoes_respiratorias[] = 'Bronquite Crônica';
                        if (($paciente['is_dpoc'] ?? 0) == 1) $condicoes_respiratorias[] = 'DPOC/Enfisema';
                        if (($paciente['is_rinite_sinusite'] ?? 0) == 1) $condicoes_respiratorias[] = 'Rinite/Sinusite';

                        // 3. Hábito (Fumante)
                        if (($paciente['is_fumante'] ?? 0) == 1) $condicoes_habito[] = 'Fumante';

                        // START: 4. NOVO BLOCO - VIROSES
                        if (($paciente['is_dengue'] ?? 0) == 1) $condicoes_viroses[] = 'Dengue';
                        if (($paciente['is_chikungunya'] ?? 0) == 1) $condicoes_viroses[] = 'Chikungunya';
                        if (($paciente['is_zika'] ?? 0) == 1) $condicoes_viroses[] = 'Zika';
                        if (($paciente['is_covid19'] ?? 0) == 1) $condicoes_viroses[] = 'COVID-19';
                        // END: 4. NOVO BLOCO - VIROSES

                        // UNIFICAR TODAS AS CONDIÇÕES PARA CHECAGEM
                        $todas_condicoes = array_merge($condicoes_cardio, $condicoes_respiratorias, $condicoes_habito, $condicoes_viroses);
                    ?>
                    
                    <?php if (empty($todas_condicoes)): ?>
                        <div class="mb-2"><span class="text-success small">Nenhuma Crônica ou Histórico de Virose Registrada.</span></div>
                    <?php else: ?>
                        <?php if (!empty($condicoes_cardio)): ?>
                            <p class="small mb-1 mt-1"><strong>Metabólicas/Cardio:</strong>
                            <?php foreach($condicoes_cardio as $c): ?>
                                <span class="badge bg-danger me-1"><?= $c ?></span>
                            <?php endforeach; ?></p>
                        <?php endif; ?>

                        <?php if (!empty($condicoes_respiratorias)): ?>
                            <p class="small mb-1"><strong>Respiratórias:</strong>
                            <?php foreach($condicoes_respiratorias as $c): ?>
                                <span class="badge bg-warning text-dark me-1"><?= $c ?></span>
                            <?php endforeach; ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($condicoes_habito)): ?>
                            <p class="small mb-1"><strong>Hábito:</strong>
                            <?php foreach($condicoes_habito as $c): ?>
                                <span class="badge bg-secondary me-1"><?= $c ?></span>
                            <?php endforeach; ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($condicoes_viroses)): ?>
                            <p class="small mb-1"><strong>Já foi acometido por:</strong>
                            <?php foreach($condicoes_viroses as $c): ?>
                                <span class="badge bg-danger me-1"><?= $c ?></span>
                            <?php endforeach; ?></p>
                        <?php endif; ?>

                    <?php endif; ?>
                    
                    <p class="small text-muted mb-0">
                        <strong>Alergias:</strong> 
                        <?= !empty($paciente['alergias_conhecidas']) ? htmlspecialchars($paciente['alergias_conhecidas']) : 'Não Informada' ?>
                    </p>
                </div>
                </div>
        </div>

        <div class="col-md-8 mb-4">
            <?php if ($paciente && $paciente['is_gestante'] == 1): 
                // Lógica de Pré-Natal
                $data_dum_str = $paciente['data_ultima_menstruacao'] ?? date('Y-m-d');
                $medico_id_prenatal = $paciente['medico_prenatal_id'] ?? 0;

                $data_dum = new DateTime($data_dum_str);
                $hoje_dt = new DateTime();
                $diferenca_dias = $hoje_dt->diff($data_dum)->days;
                $idade_gestacional_semanas = floor($diferenca_dias / 7);

                $dpp_dt = new DateTime($data_dum_str);
                $dpp_dt->modify('+280 days'); 
                $dpp_formatada = $dpp_dt->format('d/m/Y');
                
                $url_prenatal = "consultas/agendar_consulta.php?especialidade=" . urlencode("Obstetrícia");
                if ($medico_id_prenatal > 0) {
                    $url_prenatal .= "&medico_id=" . $medico_id_prenatal;
                }
            ?>
                <div class="card shadow-sm h-100 border-danger">
                    <div class="card-header bg-danger text-white"><i class="fas fa-baby me-2"></i>Atenção: Controle Pré-Natal!</div>
                    <div class="card-body">
                        
                        <p class="lead mb-3 fst-italic">"<?= $frase_do_dia ?>"</p>
                        <p><strong>Idade Gestacional:</strong> <?= $idade_gestacional_semanas ?> semanas</p>
                        <p><strong>DPP (Previsão de Parto):</strong> <?= $dpp_formatada ?></p>
                        
                        <?php if ($proxima_consulta_plano): ?>
                            <p class="text-danger">Sua data **PREVISTA** para a próxima consulta é: <strong><?= date('d/m/Y', strtotime($proxima_consulta_plano)) ?></strong></p>
                        <?php endif; ?>
                        
                        <a href="<?= $url_prenatal ?>" class="btn btn-warning mt-2">Agendar Consulta Agora</a>
                        
                        <hr class="my-4">
                        
                        <h6><i class="fas fa-list-ul me-2"></i>Pendências Gerais</h6>
                        <ul class="list-group list-group-flush border-0">
                            <?php foreach ($lembretes as $lembrete): 
                                // Exclui o item de "Sucesso" se houver alertas reais
                                if ($lembrete['type'] === 'Sucesso' && count($lembretes) > 1) continue;
                                
                                $class = ($lembrete['urgency'] == 'high') ? 'list-group-item-danger' : (($lembrete['urgency'] == 'warning') ? 'list-group-item-warning' : 'list-group-item-light');
                                $icon = ($lembrete['type'] == 'Consulta') ? 'fas fa-calendar-day' : (($lembrete['type'] == 'Exame') ? 'fas fa-flask' : 'fas fa-syringe');
                            ?>
                                <li class="list-group-item <?= $class ?>">
                                    <i class="<?= $icon ?> me-2"></i>
                                    <?= $lembrete['message'] ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                    </div>
                </div>
            <?php else: // NOVO HUB DE LEMBRETES (SUBSTITUINDO O CARD STATUS GERAL) ?>
                <div class="card shadow-sm h-100 border-primary">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-bell me-2"></i>Seus Lembretes Importantes</h4>
                    </div>
                    <div class="card-body">
                       <?php
                        // LÓGICA DE FILTRAGEM DA CAMPANHA POR GÊNERO
                        $exibir_campanha = false;
                        if ($alerta_campanha) {
                            $target = $alerta_campanha['target'];
                            // Verifica se o alvo é Ambos ('A') OU se o alvo bate com o gênero do paciente ('M' ou 'F')
                            if ($target == 'A' || $target == $genero_paciente) {
                                $exibir_campanha = true;
                            }
                        }
                        ?>

                        <?php if ($exibir_campanha): ?>
                        <div class="alert alert-<?= $alerta_campanha['cor'] ?> mb-4" role="alert">
                            <h5 class="alert-heading mb-1"><i class="fas fa-ribbon me-2"></i><?= htmlspecialchars($alerta_campanha['nome']) ?></h5>
                            <p class="mb-0"><?= $alerta_campanha['mensagem'] ?></p>
                        </div>
                        <?php endif; ?>                        <ul class="list-group list-group-flush">
                            <?php foreach ($lembretes as $lembrete): 
                                $class = ($lembrete['urgency'] == 'high') ? 'list-group-item-danger' : (($lembrete['urgency'] == 'warning') ? 'list-group-item-warning' : 'list-group-item-light');
                                $icon = ($lembrete['type'] == 'Consulta') ? 'fas fa-calendar-day' : (($lembrete['type'] == 'Exame') ? 'fas fa-flask' : 'fas fa-syringe');
                            ?>
                                <li class="list-group-item <?= $class ?>">
                                    <i class="<?= $icon ?> me-2"></i>
                                    <?= $lembrete['message'] ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="text-end mt-3 mb-0">
                            <a href="#consultas" class="btn btn-sm btn-info me-2">Ver Consultas</a>
                            <a href="#vacinas" class="btn btn-sm btn-success">Ver Vacinas</a>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div id="consultas" class="card-header bg-secondary text-white">
            <h4 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Próximas Consultas Agendadas</h4>
        </div>
        <div class="card-body">
            <?php if (!empty($consultas_agendadas)): ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($consultas_agendadas as $consulta): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-primary me-2"><?= htmlspecialchars($consulta['especialidade']) ?></span>
                                <span class="fw-bold"><?= date('d/m/Y', strtotime($consulta['data_consulta'])) ?> às <?= date('H:i', strtotime($consulta['horario_consulta'])) ?></span> com Dr(a). 
                                <?= htmlspecialchars($consulta['nome_medico']) ?>
                            </div>
                           <?php 
                                $status_db = $consulta['status'];
                                $data_consulta = $consulta['data_consulta'];
                                $hoje = date('Y-m-d');
                                
                                $badge_class = '';
                                $badge_text = '';

                                // LÓGICA DE STATUS VENCIDO (PRIORIDADE MÁXIMA)
                                if (
                                    $data_consulta < $hoje && 
                                    $status_db != 'Concluída' && 
                                    $status_db != 'Cancelada'
                                ) {
                                    // Status VENCIDO / NÃO CONCLUÍDA
                                    $badge_class = 'bg-danger';
                                    $badge_text = 'Não Concluída (Vencida)';
                                } else {
                                    // STATUS ATIVOS E FUTUROS
                                    switch ($status_db) {
                                        case 'Agendada':
                                        case 'Pendente': // Usamos 'Pendente' para novos agendamentos
                                            $badge_class = 'bg-primary';
                                            $badge_text = 'Agendada';
                                            break;
                                        case 'Aguardando Médico':
                                            $badge_class = 'bg-success';
                                            $badge_text = 'Aguardando Atendimento';
                                            break;
                                        case 'Concluída':
                                            $badge_class = 'bg-dark';
                                            $badge_text = 'Concluída';
                                            break;
                                        case 'Cancelada':
                                            $badge_class = 'bg-warning text-dark';
                                            $badge_text = 'Cancelada';
                                            break;
                                        default:
                                            $badge_class = 'bg-secondary';
                                            $badge_text = 'Status Desconhecido';
                                    }
                                }
                            ?>
                            <span class="badge <?= $badge_class ?>"><?= $badge_text ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="alert alert-light">Não há consultas agendadas para o futuro.</div>
            <?php endif; ?>
            <a href="consultas/agendar_consulta.php" class="btn btn-info mt-3"><i class="fas fa-plus me-1"></i> Agendar Nova Consulta</a>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0"><i class="fas fa-history me-2"></i>Histórico de Vacinas Aplicadas</h4>
        </div>
        <div class="card-body">
            <?php if (!empty($vacinas_aplicadas_historico)): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Data Aplicação</th>
                                <th>Vacina</th>
                                <th>Dose</th>
                                <th>Marca</th>
                                <th>Lote</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vacinas_aplicadas_historico as $vacina_hist): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($vacina_hist['data_aplicacao'])) ?></td>
                                    <td><strong><?= htmlspecialchars($vacina_hist['nome_vacina']) ?></strong></td>
                                    <td><?= htmlspecialchars($vacina_hist['dose']) ?></td>
                                    <td><?= htmlspecialchars($vacina_hist['marca']) ?></td>
                                    <td><?= htmlspecialchars($vacina_hist['lote']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    Nenhuma vacina aplicada encontrada em seu histórico.
                </div>
            <?php endif; ?>
        </div>
    </div>


    <div id="vacinas">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-syringe me-2"></i>Acompanhamento Vacinal: Recomendações</h4>
            </div>
            <div class="card-body">
                
                <?= $alerta_imunizacao ?>
                
                <?php if (!empty($vacinas_para_tabela) && is_array($vacinas_para_tabela)): ?>
                    <p class="text-muted">Selecione as vacinas pendentes que deseja agendar. As datas de doses futuras serão calculadas automaticamente após a aplicação.</p>

                    <form action="salvar_agendamento_vacina.php" method="POST" id="formVacinas">
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Vacina</th>
                                        <th>Dose</th>
                                        <th>Status</th>
                                        <th>Data Sugerida / Idade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vacinas_para_tabela as $vacina): 
                                        $is_agendada = isset($vacina['data_agendamento']);
                                    ?>
                                        <tr>
                                            <td>
                                                <?php if (!$is_agendada): ?>
                                                    <input class="form-check-input vacina-checkbox" type="checkbox" value="<?= htmlspecialchars($vacina['vacina'] . '|' . $vacina['dose']) ?>" id="vacina_<?= $vacina['vacina'] ?>">
                                                <?php else: ?>
                                                    <i class="fas fa-calendar-check text-info"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($vacina['vacina']) ?></td>
                                            <td><?= htmlspecialchars($vacina['dose']) ?></td>
                                            <td>
                                                <?php if ($is_agendada): ?>
                                                    <span class="badge bg-info text-dark">Agendada</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Pendente</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($is_agendada): ?>
                                                    <span class="text-primary fw-bold"><?= date('d/m/Y', strtotime($vacina['data_agendamento'])) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted"><?= htmlspecialchars($vacina['idade']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="text-end mt-3">
                            <button type="button" class="btn btn-success" onclick="abrirModalAgendamento()">
                                <i class="fas fa-calendar-alt me-1"></i> Agendar Vacinas Selecionadas
                            </button>
                        </div>
                        
                        <input type="hidden" name="paciente_id" value="<?= $paciente_id ?>">
                    </form>

                <?php else: ?>
                    <div class="alert alert-success mt-3">
                        <i class="fas fa-check-circle me-1"></i> **Parabéns!** Não há mais vacinas pendentes de atenção para o seu perfil e idade, ou o sistema de cálculo está em manutenção.
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-1"></i> **Dúvidas?** Caso acredite que esta informação está incorreta, por favor, entre em contato com a gerência da clínica.
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
    
    <div class="card shadow mb-4 border-primary">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="fas fa-list-check me-2"></i>Exames a Realizar</h4>
        </div>
        <div class="card-body">
            <?php if (!empty($exames_solicitados)): ?>
                <p class="text-muted small">
                    Você tem <strong><?= count($exames_solicitados) ?></strong> pedidos médicos pendentes. 
                    Clique para expandir os detalhes e entrar em contato com o laboratório para agendar a coleta.
                </p>
                <div class="accordion" id="accordionExames">
                    <?php foreach ($exames_solicitados as $index => $exame): 
                        $is_agendado = !empty($exame['data_agendamento_paciente']);
                    ?>
                    <div class="accordion-item mb-3 shadow-sm">
                        <h2 class="accordion-header" id="headingExame<?= $index ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExame<?= $index ?>" aria-expanded="false" aria-controls="collapseExame<?= $index ?>">
                                <i class="fas fa-vial me-2 text-primary"></i> 
                                <strong class="text-primary"><?= htmlspecialchars($exame['nome_exame']) ?></strong> <?php if ($is_agendado): ?>
                                     <span class="badge bg-success ms-3">Exame Agendado</span>
                                <?php else: ?>
                                     <span class="badge bg-warning text-dark ms-3">Aguardando Agendamento</span>
                                <?php endif; ?>
                            </button>
                        </h2>
                        <div id="collapseExame<?= $index ?>" class="accordion-collapse collapse" aria-labelledby="headingExame<?= $index ?>" data-bs-parent="#accordionExames">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-md-6 border-end">
                                        <h6><i class="fas fa-user-md me-1"></i> Detalhes do Pedido</h6>
                                        <p class="mb-1"><strong>Solicitado em:</strong> <?= date('d/m/Y', strtotime($exame['data_pedido'])) ?></p>
                                        <p class="mb-0"><strong>Médico:</strong> Dr(a). <?= htmlspecialchars($exame['nome_medico']) ?></p>
                                        
                                        <?php if ($is_agendado): ?>
                                            <div class="alert alert-success mt-3 p-2">
                                                <strong>Agendado para:</strong> <?= date('d/m/Y', strtotime($exame['data_agendamento_paciente'])) ?>
                                                <br><strong>Turno:</strong> <?= htmlspecialchars($exame['turno_agendamento_paciente']) ?>
                                            </div>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-success btn-sm mt-3" 
                                                    onclick="abrirModalAgendamentoExame(<?= $exame['id'] ?>, '<?= htmlspecialchars($exame['nome_exame']) ?>')">
                                                <i class="fas fa-calendar-plus me-1"></i> Agendar Exame
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-map-marker-alt me-1"></i> Laboratório Direcionado</h6>
                                        <p class="mb-1 fw-bold"><?= htmlspecialchars($exame['nome_laboratorio']) ?></p>
                                        <?php 
                                        $coordenadas_validas = !is_null($exame['lab_latitude']) && trim($exame['lab_latitude']) !== '';
                                        if ($coordenadas_validas): 
                                        ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary ms-2" 
                                                    onclick="exibirMapa('<?= htmlspecialchars($exame['nome_laboratorio']) ?>', '<?= $exame['lab_latitude'] ?>', '<?= $exame['lab_longitude'] ?>')">
                                                <i class="fas fa-map-marker-alt"></i> Mapa
                                            </button>
                                        <?php endif; ?>
                                        <p class="mb-1 small">
                                            <i class="fas fa-phone me-1"></i> Tel: <?= htmlspecialchars($exame['lab_telefone'] ?? 'N/D') ?>
                                        </p>
                                        <p class="mb-0 small">
                                            <i class="fas fa-location-dot me-1"></i> End: <?= htmlspecialchars($exame['lab_endereco'] ?? 'N/D') ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-1"></i> Não há exames pendentes de realização.
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    
</div>

<div class="modal fade" id="mapaViewModal" tabindex="-1" aria-labelledby="mapaViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="mapaViewModalLabel">Localização do Laboratório</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h4 id="labNomeNoMapa" class="mb-3 text-primary"></h4>
                <div id="mapaView" style="height: 450px; width: 100%;">
                    Carregando mapa...
                </div>
                <p class="text-muted mt-2 small">Coordenadas: Lat: <span id="latDisplay"></span>, Lng: <span id="lngDisplay"></span></p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAgendarExame" tabindex="-1" aria-labelledby="modalAgendarExameLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="consultas/salvar_agendamento_exame.php" method="POST" id="formAgendarExame">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalAgendarExameLabel">Agendar Exame: <span id="nomeExameDisplay" class="fw-bold"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Confirme a data e o turno de sua preferência para a realização deste exame. Esta informação será enviada ao laboratório para confirmação.</p>
                    
                    <input type="hidden" name="pedido_id" id="pedidoIdInput">
                    
                    <div class="mb-3">
                        <label for="dataAgendamentoExame" class="form-label">Data de Realização:</label>
                        <input type="date" class="form-control" id="dataAgendamentoExame" name="data_agendamento" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Turno Preferencial:</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="turno" id="turno_manha_exame" value="Manhã" required>
                            <label class="form-check-label" for="turno_manha_exame">Manhã (08:00h - 12:00h)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="turno" id="turno_tarde_exame" value="Tarde" required>
                            <label class="form-check-label" for="turno_tarde_exame">Tarde (13:00h - 17:00h)</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Confirmar Agendamento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="modalAgendamentoVacina" tabindex="-1" aria-labelledby="modalAgendamentoVacinaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="salvar_agendamento_vacina.php" method="POST" id="formAgendamentoVacina">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalAgendamentoVacinaLabel">Confirmação de Agendamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Você está prestes a agendar a aplicação das vacinas selecionadas.</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-1"></i> Este agendamento é apenas um registro de sua intenção no sistema. As datas são agendadas por <strong>turno (Manhã/Tarde)</strong> e o atendimento será por <strong>ordem de chegada</strong> dentro do turno escolhido.
                    </div>
                    
                    <div class="mb-3">
                        <label for="dataAgendamento" class="form-label">Data Prevista para Aplicação:</label>
                        <input type="date" class="form-control" id="dataAgendamento" name="data_agendamento" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Turno Preferencial:</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="turno" id="turno_manha" value="manha" required>
                            <label class="form-check-label" for="turno_manha">Manhã (08:00h - 12:00h)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="turno" id="turno_tarde" value="tarde" required>
                            <label class="form-check-label" for="turno_tarde">Tarde (13:00h - 17:00h)</label>
                        </div>
                    </div>
                    
                    <div id="vacinasHiddenInputs">
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Salvar Agendamento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let mapaViewInstance = null;
    
    function exibirMapa(nome, lat, lng) {
        const modal = new bootstrap.Modal(document.getElementById('mapaViewModal'));
        const latFloat = parseFloat(lat);
        const lngFloat = parseFloat(lng);

        if (isNaN(latFloat) || isNaN(lngFloat)) {
            alert("Coordenadas geográficas inválidas para este laboratório.");
            return;
        }

        document.getElementById('labNomeNoMapa').textContent = nome;
        document.getElementById('latDisplay').textContent = latFloat.toFixed(6);
        document.getElementById('lngDisplay').textContent = lngFloat.toFixed(6);

        modal.show();

        document.getElementById('mapaViewModal').addEventListener('shown.bs.modal', function() {
            
            if (mapaViewInstance) {
                mapaViewInstance.remove();
                mapaViewInstance = null;
            }

            mapaViewInstance = L.map('mapaView').setView([latFloat, lngFloat], 16); 

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(mapaViewInstance);

            L.marker([latFloat, lngFloat]).addTo(mapaViewInstance)
                .bindPopup(`<b>${nome}</b>`)
                .openPopup();
            
            mapaViewInstance.invalidateSize(); 
        }, {once: true}); 
    }
    
    // NOVA FUNÇÃO JS PARA O AGENDAMENTO DE EXAMES
    function abrirModalAgendamentoExame(pedidoId, nomeExame) {
        document.getElementById('pedidoIdInput').value = pedidoId;
        document.getElementById('nomeExameDisplay').textContent = nomeExame;

        // O modal de agendamento de exame deve ser aberto aqui
        const agendamentoExameModal = new bootstrap.Modal(document.getElementById('modalAgendarExame'));
        agendamentoExameModal.show();
    }

    // Função para abrir o modal de agendamento de vacinas
    function abrirModalAgendamento() {
        const checkboxes = document.querySelectorAll('.vacina-checkbox:checked');
        const modalBody = document.getElementById('vacinasHiddenInputs');
        modalBody.innerHTML = ''; // Limpa inputs anteriores
        
        if (checkboxes.length === 0) {
            alert("Selecione pelo menos uma vacina para agendar.");
            return;
        }

        // 1. Coleta os dados e preenche o modal com inputs ocultos
        checkboxes.forEach(checkbox => {
            const inputHidden = document.createElement('input');
            inputHidden.type = 'hidden';
            inputHidden.name = 'vacinas_para_salvar[]'; // O nome que o salvar_agendamento_vacina.php espera
            inputHidden.value = checkbox.value;
            modalBody.appendChild(inputHidden);
        });

        // 2. Abre o modal
        const agendamentoModal = new bootstrap.Modal(document.getElementById('modalAgendamentoVacina'));
        agendamentoModal.show();
    }
    
    // A única lógica de submissão está no modal e fará o POST para salvar_agendamento_vacina.php
    document.getElementById('formAgendamentoVacina').addEventListener('submit', function(e) {
        // Nada de especial aqui, o formulário submete.
    });
</script>


<?php require_once 'footer.php'; ?>

