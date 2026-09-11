<?php
// Define as configurações de tema com base no perfil do usuário

function getTemaClasses($usuario_tipo) {
    
    // Cores de fundo dos cabeçalhos (Header)
    $cores_header = [
        // Admin: Azul Marinho (Mais escuro para máxima segurança)
        'admin' => '#003366', 
        // Gerente: Verde (Tom que remete a gestão/operação)
        'gerente' => '#188D44', 
        // Padrão (Médico, Paciente, Enfermagem): Azul Primário
        'default' => '#0d6efd' 
    ];

    // Classes de fundo do corpo (Body)
    $cores_body = [
        // Admin: Cinza 25%
        'admin' => 'admin-bg', 
        // Gerente: Cinza 10%
        'gerente' => 'gerente-bg', 
        // Padrão: bg-light (quase branco)
        'default' => 'bg-light' 
    ];

    $classe_header = $cores_header[$usuario_tipo] ?? $cores_header['default'];
    $classe_body = $cores_body[$usuario_tipo] ?? $cores_body['default'];
    
    return [
        'body_class' => $classe_body,
        'header_color' => $classe_header
    ];
}
?>