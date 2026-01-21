<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Garante que usamos a config do admin para obter $pdo
include_once __DIR__ . '/config.php';

// Se o usuário não estiver logado, redireciona para o login do portal preservando a URL de destino
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/index.php';
    // Usa login relativo ao módulo atual (admin/ ou organograma/), preservando a URL completa
    $loginUrl   = 'login.php?next=' . urlencode($requestUri);
    header('Location: ' . $loginUrl);
    exit;
}

$user_id = $_SESSION['user_id'];

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

// Previne erro se variável não for definida
if (!isset($requiredAccess)) {
    $requiredAccess = null;
}

// Verifica acesso
if ($requiredAccess && !hasAnyAccess($requiredAccess, $user_accesses)) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Você não tem permissão para acessar esta área.</div></div>";
    exit;
}
?>
