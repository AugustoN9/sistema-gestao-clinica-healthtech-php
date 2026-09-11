<?php
// Arquivo: acesso_negado_tela.php (VERSÃO FINAL)

// Define o código de status HTTP (403 Forbidden)
http_response_code(403); 

$titulo = "Acesso Não Autorizado";
$mensagem = "Desculpe, você não tem permissão para visualizar este conteúdo. Por favor, retorne ao seu painel principal.";
$link_volta = "/"; 

// Variável para o caminho da imagem (usando caminho absoluto)
$caminho_imagem = '/assets/images/ACESSO NEGADO.jpg';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?> - HealthTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa; /* Fundo liso */
        }
        .error-container {
            text-align: center;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            background-color: white; 
            max-width: 600px;
        }
        .error-code {
            font-size: 6rem;
            color: #dc3545;
            line-height: 1;
            margin-bottom: 10px;
        }
        .error-message {
            font-size: 1.5rem;
            color: #495057;
            margin-bottom: 20px;
        }
        .error-illustration {
            width: 100%;
            max-width: 650px; /* Controla o tamanho da imagem */
            height: auto;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <div class="error-container">
        <img src="<?= $caminho_imagem ?>" alt="Acesso Negado Ilustração" class="error-illustration">
        
        <div class="error-code">403</div>
        <div class="error-message">
            <i class="fas fa-lock me-2"></i><?= $titulo ?>
        </div>
        <p><?= $mensagem ?></p>
        <a href="<?= $link_volta ?>" class="btn btn-primary mt-3">
            <i class="fas fa-arrow-left me-2"></i> Voltar ao Painel
        </a>
    </div>

</body>
</html>