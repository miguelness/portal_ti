<?php
// admin/noticia_alterar_status.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    echo 'forbidden';
    exit;
}

// Verifica se os parâmetros foram enviados
if (!isset($_GET['id'], $_GET['status'])) {
    http_response_code(400);
    echo 'missing_parameters';
    exit;
}

$id = (int) $_GET['id'];
$status = $_GET['status'] === 'ativo' ? 'ativo' : 'inativo'; // só aceita valores válidos

try {
    $stmt = $pdo->prepare("UPDATE noticias SET status = :status WHERE id = :id");
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount()) {
        echo 'success';
    } else {
        echo 'no_change';
    }
} catch (Exception $e) {
    http_response_code(500);
    echo 'error';
}
?>
