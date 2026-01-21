<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/reader-session.php';

$user = currentReader();

if ($user) {
    echo json_encode([
        'logged_in' => true,
        'user' => [
            'id' => $user['id'],
            'nome' => $user['nome'],
            'email' => $user['email']
        ]
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'logged_in' => false,
        'user' => null
    ], JSON_UNESCAPED_UNICODE);
}
?>