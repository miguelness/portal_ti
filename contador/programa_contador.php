<?php
/**
 * Programa Contador - Registra cliques e redireciona para o portal
 * 
 * Este script:
 * 1. Cria a tabela 'contador_cliques' se não existir
 * 2. Registra cada acesso com timestamp e informações do usuário
 * 3. Redireciona para o portal do Grupo Barão
 */

// Definir fuso horário de São Paulo
date_default_timezone_set('America/Sao_Paulo');

// Incluir configuração do sistema
require_once '../config.php';

// URL de redirecionamento
$redirect_url = 'https://ti.grupobarao.com.br/portal/';

// Tenta conectar ao banco de dados
try {
    // Usar conexão do config.php
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Verifica se houve erro na conexão
    if ($conn->connect_error) {
        throw new Exception("Falha na conexão: " . $conn->connect_error);
    }
    
    // Cria a tabela se não existir
    $sql_create_table = "CREATE TABLE IF NOT EXISTS contador_cliques (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        data_hora DATETIME NOT NULL,
        ip VARCHAR(45) NOT NULL,
        user_agent TEXT,
        referer TEXT,
        origem VARCHAR(255)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!$conn->query($sql_create_table)) {
        throw new Exception("Erro ao criar tabela: " . $conn->error);
    }
    
    // Coleta informações do acesso
    $data_hora = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'desconhecido';
    $referer = $_SERVER['HTTP_REFERER'] ?? 'acesso direto';
    $origem = $_GET['origem'] ?? 'não especificada';
    
    // Prepara e executa a inserção
    $stmt = $conn->prepare("INSERT INTO contador_cliques (data_hora, ip, user_agent, referer, origem) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $data_hora, $ip, $user_agent, $referer, $origem);
    
    if (!$stmt->execute()) {
        // Registra o erro mas continua para o redirecionamento
        error_log("Erro ao registrar clique: " . $stmt->error);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    // Registra o erro mas continua para o redirecionamento
    error_log("Erro no contador: " . $e->getMessage());
}

// Redireciona para o portal (mesmo se houver erro no registro)
header("Location: " . $redirect_url);
exit;
?>