<?php
// require_login.php – protege páginas e redireciona para /login.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLogged = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
if (!$isLogged) {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/index.php';
    $loginUrl   = '/login.php?next=' . urlencode($requestUri);
    header('Location: ' . $loginUrl);
    exit;
}

