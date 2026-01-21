<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/reader-session.php';
require_once dirname(__DIR__) . '/conexao.php';

function respond(bool $ok, string $msg = '', array $extra = []): never
{
    echo json_encode(['success'=>$ok,'message'=>$msg] + $extra, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$nid     = isset($payload['id']) ? (int)$payload['id'] : 0;

if (!$readerId)        respond(false, 'É necessário entrar para curtir.');
if ($nid <= 0)         respond(false, 'Artigo inválido.');

// verifica se já curtiu
$stmt = $conn->prepare('SELECT 1 FROM article_likes WHERE noticia_id = ? AND reader_id = ?');
$stmt->bind_param('ii', $nid, $readerId);
$stmt->execute();
$ja = (bool)$stmt->get_result()->fetch_column();

if ($ja) {
    $stmt = $conn->prepare('DELETE FROM article_likes WHERE noticia_id = ? AND reader_id = ?');
    $stmt->bind_param('ii', $nid, $readerId);
    $stmt->execute();
    $liked = false;
} else {
    $stmt = $conn->prepare('INSERT INTO article_likes (noticia_id, reader_id) VALUES (?, ?)');
    $stmt->bind_param('ii', $nid, $readerId);
    $stmt->execute();
    $liked = true;
}

// total de likes
$stmt = $conn->prepare('SELECT COUNT(*) FROM article_likes WHERE noticia_id = ?');
$stmt->bind_param('i', $nid);
$stmt->execute();
$total = (int)$stmt->get_result()->fetch_column();

respond(true, '', ['liked'=>$liked, 'likes'=>$total]);
