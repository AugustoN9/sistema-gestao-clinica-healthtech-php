<?php
/*
==============================================================================
ARQUIVO DE CONEXÃO COM O BANCO DE DADOS
==============================================================================
*/
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Utiliza variáveis de ambiente se existirem (para o Render), 
// caso contrário, usa os valores padrão para desenvolvimento local.
$servidor = getenv('DB_HOST') ?: 'localhost';    
$usuario  = getenv('DB_USER') ?: 'root';     
$senha    = getenv('DB_PASS') ?: '';           
$banco    = getenv('DB_NAME') ?: 'clinica';     

// Tenta estabelecer a conexão
$conexao = mysqli_connect($servidor, $usuario, $senha, $banco);

// Verifica se a conexão falhou
if (!$conexao) {
    die("ERRO FATAL: Falha na conexão. Detalhes: " . mysqli_connect_error());
}

// Define o conjunto de caracteres da conexão para utf8mb4
mysqli_set_charset($conexao, "utf8mb4");
?>