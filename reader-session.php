<?php
// reader-session.php

require_once __DIR__ . '/conexao.php';

if (session_status() === PHP_SESSION_NONE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

$readerId = $_SESSION['reader_id'] ?? null;

function requireReaderLogin(): void
{
    global $readerId;
    if (!$readerId) {
        $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        header('Location: reader-login.php');
        exit;
    }
}

function currentReader(): ?array
{
    global $readerId, $conn;
    if (!$readerId) return null;
    $stmt = $conn->prepare('SELECT id, nome, email FROM readers WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $readerId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}
