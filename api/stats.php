<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/reader-session.php';
require_once dirname(__DIR__) . '/conexao.php';

$nid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($nid <= 0) {
    echo json_encode([], JSON_NUMERIC_CHECK);
    exit;
}

$stmt = $conn->prepare('SELECT COUNT(*) FROM article_likes WHERE noticia_id = ?');
$stmt->bind_param('i', $nid);
$stmt->execute();
$likes = (int)$stmt->get_result()->fetch_column();

$stmt = $conn->prepare('SELECT COUNT(*) FROM article_views WHERE noticia_id = ?');
$stmt->bind_param('i', $nid);
$stmt->execute();
$views = (int)$stmt->get_result()->fetch_column();

$liked = false;
if ($readerId) {
    $stmt = $conn->prepare('SELECT 1 FROM article_likes WHERE noticia_id = ? AND reader_id = ?');
    $stmt->bind_param('ii', $nid, $readerId);
    $stmt->execute();
    $liked = (bool)$stmt->get_result()->fetch_column();
}

echo json_encode(['likes'=>$likes,'views'=>$views,'liked'=>$liked], JSON_NUMERIC_CHECK);
