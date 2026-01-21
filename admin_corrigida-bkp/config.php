<?php
/**
 * Configuração do Sistema Administrativo - Grupo Barão
 * Sistema reconstruído do zero com base no Tabler
 */

// Configuração do banco de dados
$host = 'localhost';
$dbname = 'portal';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao conectar com o banco de dados: " . $e->getMessage());
}

// Inicia sessão se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Função para verificar se usuário está logado
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Função para verificar acessos do usuário
function getUserAccesses($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT a.access_name 
        FROM user_access ua
        JOIN accesses a ON ua.access_id = a.id
        WHERE ua.user_id = :user_id
    ");
    $stmt->execute([':user_id' => $user_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Função para verificar se usuário tem acesso específico
function hasAccess($required_access, $user_accesses) {
    // Super Administrador tem acesso a tudo
    if (in_array('Super Administrador', $user_accesses)) {
        return true;
    }
    
    // Verifica se tem o acesso específico
    if (is_array($required_access)) {
        return !empty(array_intersect($required_access, $user_accesses));
    }
    
    return in_array($required_access, $user_accesses);
}

// Carrega acessos do usuário logado
$user_accesses = [];
if (isLoggedIn()) {
    $user_accesses = getUserAccesses($pdo, $_SESSION['user_id']);
}

// Função para redirecionar se não tiver acesso
function requireAccess($required_access, $user_accesses) {
    if (!hasAccess($required_access, $user_accesses)) {
        header('Location: index.php?error=access_denied');
        exit;
    }
}

// Função para redirecionar se não estiver logado
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Configurações gerais do sistema
define('SYSTEM_NAME', 'Portal Administrativo - Grupo Barão');
define('SYSTEM_VERSION', '2.0.0');
define('UPLOAD_PATH', '../uploads/');
define('UPLOAD_ALERTS_PATH', '../uploads_alertas/');
define('UPLOAD_RH_PATH', '../uploads_rh/');

// Timezone
date_default_timezone_set('America/Sao_Paulo');
?>