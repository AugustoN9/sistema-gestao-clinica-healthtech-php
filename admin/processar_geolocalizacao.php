<?php
// processar_geolocalizacao.php (Versão PHP Puro usando Nominatim)

// 1. Setup e Proteção
require_once '../auth.php';
proteger_pagina(['admin']); // Apenas administradores podem executar esta tarefa pesada
require_once '../conexao.php';

echo "<h1>🛠️ Processamento de Geolocalização Pendente (Nominatim API)</h1>";
echo "<p>O script irá buscar laboratórios sem coordenadas e geocodificá-los usando PHP puro.</p><hr>";

// Aumenta o tempo limite de execução (se houver muitos registros)
set_time_limit(300); 

// 2. Busca Laboratórios Pendentes
// Critério: Endereço completo (logradouro, cidade) preenchido, mas Lat/Lng são NULL.
$sql_pendentes = "SELECT id, logradouro, numero, bairro, cidade, uf 
                  FROM laboratorios_parceiros 
                  WHERE logradouro IS NOT NULL 
                    AND cidade IS NOT NULL
                    AND latitude IS NULL
                  LIMIT 10"; // Mantemos o limite para ser gentil com a API Nominatim

$resultado_pendentes = mysqli_query($conexao, $sql_pendentes);

if (mysqli_num_rows($resultado_pendentes) == 0) {
    echo "<p class='alert alert-success'>✅ Todos os laboratórios foram geocodificados ou não têm endereço completo.</p>";
    exit();
}

$total_processado = 0;
$total_falhas = 0;

while ($lab = mysqli_fetch_assoc($resultado_pendentes)) {
    $id = $lab['id'];
    
    // 3. Montar o Endereço de Alta Precisão (Passo crucial)
    // O Nominatim se beneficia do número e do país/estado
    $endereco_formatado = trim("{$lab['logradouro']} {$lab['numero']}, {$lab['cidade']}, {$lab['uf']}, Brasil");
    
    // Codifica a URL e define o endpoint do Nominatim
    $query = urlencode($endereco_formatado);
    $url_nominatim = "https://nominatim.openstreetmap.org/search?q={$query}&format=json&limit=1";
    
    // 4. Realizar a Requisição HTTP via PHP
    // Configura um User-Agent para seguir as políticas da API Nominatim
    $context = stream_context_create([
        'http' => [
            // É CRÍTICO para a API Nominatim ter um User-Agent válido
            'header' => "User-Agent: ClinicaHealthTechApp/1.0 (seu_email@exemplo.com)\r\n" 
        ]
    ]);
    
    // @ evita que erros de PHP (como falha de conexão) apareçam
    $resultado_raw = @file_get_contents($url_nominatim, false, $context);
    
    if ($resultado_raw === FALSE) {
        $total_falhas++;
        echo "<p class='text-danger'>ID {$id}: ⚠️ Falha na conexão com a API Nominatim.</p>";
        continue;
    }
    
    $dados_geocodificados = json_decode($resultado_raw, true);

    // 5. Processar o Resultado
    if (!empty($dados_geocodificados) && isset($dados_geocodificados[0]['lat'])) {
        
        // Nominatim retorna um array de objetos, pegamos o primeiro resultado [0]
        $latitude = $dados_geocodificados[0]['lat'];
        $longitude = $dados_geocodificados[0]['lon'];

        // 6. Atualizar o Banco de Dados
        $sql_update = "UPDATE laboratorios_parceiros SET latitude = ?, longitude = ? WHERE id = ?";
        $stmt_update = mysqli_prepare($conexao, $sql_update);
        
        // Tipos: d (double/decimal para lat), d (double/decimal para lng), i (id)
        mysqli_stmt_bind_param($stmt_update, "ddi", $latitude, $longitude, $id);
        
        if (mysqli_stmt_execute($stmt_update)) {
            $total_processado++;
            echo "<p class='text-success'>ID {$id}: ✅ Sucesso! Coordenadas: Lat: {$latitude}, Lng: {$longitude}</p>";
        } else {
            $total_falhas++;
            echo "<p class='text-danger'>ID {$id}: Erro SQL ao salvar coordenadas.</p>";
        }
    } else {
        $total_falhas++;
        echo "<p class='text-warning'>ID {$id}: ⚠️ Endereço não geocodificado. Revise o endereço: {$endereco_formatado}</p>";
    }
}

echo "<hr><h3>Resumo da Execução</h3>";
echo "<p>Total processado com sucesso: {$total_processado}</p>";
echo "<p>Total de falhas: {$total_falhas}</p>";

// Link para continuar o processamento
echo "<p><a href='processar_geolocalizacao.php' class='btn btn-primary mt-3'>Continuar Processamento (Próximos 10)</a></p>";
?>