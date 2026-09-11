<?php
// Ficheiro: auth.php

// Inicia a sessão APENAS se ela ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Função de segurança para proteger páginas com base no tipo de usuário.
 * * @param array $tipos_permitidos Array contendo os tipos de usuário ('admin', 'paciente', etc.) que podem acessar a página.
 */
function proteger_pagina($tipos_permitidos = []) {
    // 1. Se não há login, manda para a página de login
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: /auth/login.php?erro=' . urlencode('Por favor, faça login.'));
        exit();
    }
    
    // 2. Se o usuário está logado, mas NÃO ESTÁ no tipo permitido
    if (!empty($tipos_permitidos) && !in_array($_SESSION['usuario_tipo'], $tipos_permitidos)) {
        
        // --- NOVO CÓDIGO: INCLUI A TELA CUSTOMIZADA DE ACESSO NEGADO (403) ---
        
        // Inclui a tela de acesso negado.
        // O caminho deve ser relativo ao ficheiro que está a chamar a função proteger_pagina().
        include 'acesso_negado_tela.php'; 
        
        // FINALIZA A EXECUÇÃO para impedir que o conteúdo restrito da página original seja carregado.
        exit(); 
        
        // --- FIM DO NOVO CÓDIGO (Lógica de redirecionamento ANTIGA FOI REMOVIDA) ---
    }
}
?>