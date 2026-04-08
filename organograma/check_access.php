<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Garante que usamos a config do admin para obter $pdo, além da config local
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../admin/config.php';

// Se o usuário não estiver logado especificamente no organograma, redireciona para o login do organograma
if (!isset($_SESSION['org_logged_in']) || $_SESSION['org_logged_in'] !== true) {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/organograma/index.php';
    $loginUrl   = 'login.php?next=' . urlencode($requestUri);
    header('Location: ' . $loginUrl);
    exit;
}

$user_id = $_SESSION['org_user_id'];

// Consulta os acessos do usuário
$sql = "SELECT a.access_name FROM user_access ua
        JOIN accesses a ON ua.access_id = a.id
        WHERE ua.user_id = :user_id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
$user_accesses = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Função para verificar múltiplos acessos (Super Administrador tem acesso total)
if (!function_exists('hasAnyAccess')) {
    function hasAnyAccess($required, $user_accesses) {
        if (in_array('Super Administrador', $user_accesses)) {
            return true;
        }
        if (is_array($required)) {
            return !empty(array_intersect($required, $user_accesses));
        }
        return in_array($required, $user_accesses);
    }
}

// Previne erro se variável não for definida no documento original
if (!isset($requiredAccess)) {
    $requiredAccess = null;
}

// Verifica acesso
if ($requiredAccess && !hasAnyAccess($requiredAccess, $user_accesses)) {
    // Retorna JSON para requisições AJAX, HTML para normais
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Acesso Negado.']);
        exit;
    }
    
    echo "<div style='display:flex; height:100vh; align-items:center; justify-content:center; background:#f8fafc; font-family:sans-serif;'>
            <div style='text-align:center; padding:2rem; background:white; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.1); max-width:400px;'>
                <h3 style='color:#e11d48;'>Acesso Restrito</h3>
                <p style='color:#64748b;'>Você não tem permissão para acessar esta área do Organograma.</p>
                <a href='../index.php' style='display:inline-block; margin-top:1rem; padding:0.5rem 1rem; background:#0ea5e9; color:white; border-radius:6px; text-decoration:none;'>Voltar ao Portal</a>
            </div>
          </div>";
    exit;
}
?>
