<?php
/**
 * Contador Reset - Zerar contador de cliques
 * 
 * Este script limpa todos os registros da tabela contador_cliques
 */

// Verificar autenticação (usando o mesmo sistema do portal)
// require_once '../admin/check_access.php';

// Verificar se o usuário confirmou a ação
if (!isset($_POST['confirm']) || $_POST['confirm'] !== 'yes') {
    header('Location: contador_stats.php?error=not_confirmed');
    exit;
}

// Incluir configuração do sistema
require_once '../config.php';

// Conectar ao banco de dados
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Verificar se a tabela existe
$tableExists = $conn->query("SHOW TABLES LIKE 'contador_cliques'")->num_rows > 0;
if (!$tableExists) {
    header('Location: contador_stats.php?error=no_table');
    exit;
}

// Executar o truncate para limpar a tabela
$result = $conn->query("TRUNCATE TABLE contador_cliques");

if ($result === false) {
    header('Location: contador_stats.php?error=reset_failed&message=' . urlencode($conn->error));
    exit;
}

// Redirecionar de volta para a página de estatísticas com mensagem de sucesso
header('Location: contador_stats.php?success=reset_complete');
exit;