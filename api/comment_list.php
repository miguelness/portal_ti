<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/conexao.php';

$nid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($nid <= 0) {
    echo json_encode(['html'=>''], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $conn->prepare(
    'SELECT c.comment, c.created_at, r.nome
       FROM article_comments c
       JOIN readers r ON r.id = c.reader_id
      WHERE c.noticia_id = ? AND c.status = "aprovado"
      ORDER BY c.created_at ASC'
);
$stmt->bind_param('i', $nid);
$stmt->execute();
$res = $stmt->get_result();

ob_start();
while ($row = $res->fetch_assoc()): ?>
  <div class="d-flex mb-3">
    <div class="me-2">
      <span class="comment-avatar bg-secondary d-inline-block"></span>
    </div>
    <div>
      <div class="fw-semibold"><?= htmlspecialchars($row['nome']) ?></div>
      <div class="small text-muted"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></div>
      <p class="mb-0"><?= nl2br(htmlspecialchars($row['comment'])) ?></p>
    </div>
  </div>
<?php endwhile;
$html = ob_get_clean();

echo json_encode(['html'=>$html], JSON_UNESCAPED_UNICODE);
