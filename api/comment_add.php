<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/reader-session.php';
require_once dirname(__DIR__) . '/conexao.php';

function respond(bool $ok, string $msg = ''): never
{
    echo json_encode(['success'=>$ok,'message'=>$msg], JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$nid     = isset($payload['id'])      ? (int)$payload['id']      : 0;
$comment = isset($payload['comment']) ? trim($payload['comment']): '';

if (!$readerId)                  respond(false, 'Faça login para comentar.');
if ($nid <= 0)                   respond(false, 'Artigo inválido.');
if ($comment === '')             respond(false, 'Comentário vazio.');
if (mb_strlen($comment) > 2000)  respond(false, 'Comentário muito longo.');

$stmt = $conn->prepare(
    'INSERT INTO article_comments (noticia_id, reader_id, comment)
     VALUES (?, ?, ?)'
);
$stmt->bind_param('iis', $nid, $readerId, $comment);

if ($stmt->execute()) {
    respond(true, 'Comentário aguardando moderação.');
}
respond(false, 'Erro ao gravar comentário.');
