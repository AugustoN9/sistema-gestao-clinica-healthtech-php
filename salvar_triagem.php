<?php
// 1. Proteção e Conexão
require_once 'auth.php';
proteger_pagina(['enfermagem', 'gerente', 'admin']); 
require_once 'conexao.php';

// 2. Validar o POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: enfermagem_fila_triagem.php");
    exit();
}

// 3. Obter os dados do formulário
$consulta_id = (int)$_POST['consulta_id'];
$paciente_id = (int)$_POST['paciente_id'];
$enfermagem_usuario_id = (int)$_SESSION['usuario_id']; 

// Tratamento de campos que podem ser NULL (mantidos como string para o bind)
$pressao_arterial = trim($_POST['pressao_arterial']);
$temperatura_c_str = !empty($_POST['temperatura_c']) ? (string)$_POST['temperatura_c'] : NULL;
$frequencia_cardiaca_str = !empty($_POST['frequencia_cardiaca']) ? (string)$_POST['frequencia_cardiaca'] : NULL;
$peso_kg_str = !empty($_POST['peso_kg']) ? (string)$_POST['peso_kg'] : NULL;
$altura_cm_str = !empty($_POST['altura_cm']) ? (string)$_POST['altura_cm'] : NULL;

// Dados do IMC (sempre vêm como string ou NULL)
$imc_calculado_str = !empty($_POST['imc_calculado_hidden']) ? (string)$_POST['imc_calculado_hidden'] : NULL; 
$imc_classificacao = !empty($_POST['imc_classificacao_hidden']) ? trim($_POST['imc_classificacao_hidden']) : NULL; 

$nivel_dor = (int)$_POST['nivel_dor'];
$local_dor = trim($_POST['local_dor']);
$observacoes_triagem = trim($_POST['observacoes_triagem']);
$data_triagem = date('Y-m-d H:i:s'); 

// Campos do Responsável (para triagem de menores)
$responsavel_nome_triagem = trim($_POST['responsavel_nome_triagem'] ?? 'N/A (Maior de idade)');
$responsavel_parentesco_triagem = trim($_POST['responsavel_parentesco_triagem'] ?? 'N/A');


// 4. Inserir Triagem e Atualizar Paciente (INICIA TRANSAÇÃO)
mysqli_begin_transaction($conexao);

try {
    // A. Inserir na tabela 'triagem' (o registro completo)
    $sql_insert_triagem = "INSERT INTO triagem 
                (consulta_id, paciente_id, enfermagem_usuario_id, data_triagem, 
                 pressao_arterial, temperatura_c, frequencia_cardiaca, 
                 peso_kg, altura_cm, imc_calculado, 
                 nivel_dor, local_dor, observacoes_triagem, 
                 responsavel_nome, responsavel_parentesco)
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_triagem = mysqli_prepare($conexao, $sql_insert_triagem);
    
    // CORREÇÃO: Usando 's' (string) para todos os valores numéricos opcionais, exceto IDs e nível de dor (que é int)
    // iiis (4) s s s s s (5) i s s s s (5) -> Total: 15
    mysqli_stmt_bind_param($stmt_triagem, "iiisssssssissss", 
        $consulta_id, $paciente_id, $enfermagem_usuario_id, $data_triagem,
        $pressao_arterial, $temperatura_c_str, $frequencia_cardiaca_str,
        $peso_kg_str, $altura_cm_str, $imc_calculado_str, 
        $nivel_dor, $local_dor, $observacoes_triagem,
        $responsavel_nome_triagem, $responsavel_parentesco_triagem
    );
    
    if (!mysqli_stmt_execute($stmt_triagem)) {
         if (mysqli_errno($conexao) == 1062) { 
             throw new Exception("Este paciente já passou pela triagem para esta consulta.");
        }
         throw new Exception("Erro ao salvar triagem: " . mysqli_error($conexao));
    }
    
    // B. Atualizar a tabela 'pacientes' com o último IMC classificado
    // O UPDATE SÓ OCORRE se a enfermeira inseriu peso/altura e o IMC foi calculado
    if ($imc_calculado_str !== null && $imc_classificacao !== null) {
        $sql_update_paciente = "UPDATE pacientes SET 
                                    ultimo_imc_data = CURDATE(), 
                                    ultimo_imc_valor = ?, 
                                    ultimo_imc_classificacao = ?
                                WHERE id = ?";
        $stmt_paciente = mysqli_prepare($conexao, $sql_update_paciente);
        
        // CORREÇÃO CRÍTICA AQUI: Mudança para 'ssi' (String, String, Int)
        // Isso força o MySQL a converter a string do PHP para DECIMAL/VARCHAR corretamente,
        // corrigindo a falha do bind_param 'd' quando a variável é uma string.
        mysqli_stmt_bind_param($stmt_paciente, "ssi", $imc_calculado_str, $imc_classificacao, $paciente_id);
        
        if (!mysqli_stmt_execute($stmt_paciente)) {
             throw new Exception("Erro ao atualizar o perfil do paciente (IMC).");
        }
    }
    
    // C. (Opcional) Atualizar o status da consulta para 'Aguardando Médico'
    // C. ATUALIZAR STATUS NA TABELA CONSULTAS (CORREÇÃO CHAVE)
    $status_principal = 'Aguardando Médico';
    $status_triagem_concluida = 1; // Valor: 1 (tinyint) para Triagem Concluída

    $sql_update_consulta = "UPDATE consultas SET 
                                status = ?, 
                                status_triagem = ? 
                            WHERE id = ?";
    $stmt_consulta = mysqli_prepare($conexao, $sql_update_consulta);

    // Tipos: s i i (String, Integer, Integer)
    mysqli_stmt_bind_param($stmt_consulta, "sii", $status_principal, $status_triagem_concluida, $consulta_id);

    if (!mysqli_stmt_execute($stmt_consulta)) {
         throw new Exception("Erro ao atualizar status da consulta.");
    }

    // 5. Sucesso!
    mysqli_commit($conexao);
    
    header("Location: enfermagem_fila_triagem.php?sucesso=" . urlencode("Triagem do paciente ID $paciente_id salva."));
    exit();

} catch (Exception $e) {
    // 6. Erro!
    mysqli_rollback($conexao);
    
    // Garante que a mensagem de erro seja visível para o enfermeiro
    header("Location: enfermagem_fila_triagem.php?erro=" . urlencode("Erro ao registar triagem: " . $e->getMessage()));
    exit();
}
?>
