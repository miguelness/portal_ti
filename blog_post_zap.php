<?php
include('admin/config.php');

// Verificar se o ID da notícia foi passado via GET
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Consulta
    $stmt = $pdo->prepare("SELECT * FROM noticias WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $noticia = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($noticia) {
        $titulo        = htmlspecialchars($noticia['titulo']);
        $conteudo_html = nl2br(htmlspecialchars_decode($noticia['conteudo']));
        $imagem        = trim($noticia['imagem'] ?? '');
        $data_publicacao = date('d \d\e M Y', strtotime($noticia['data_publicacao']));

        // Montar URL absoluta da página
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $url      = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        // Garantir URL absoluta da imagem
        if ($imagem && !preg_match('#^https?://#', $imagem)) {
            $imagem = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/portal/' . ltrim($imagem, '/');
        }

        // Descrição resumida
        $desc_raw  = strip_tags(htmlspecialchars_decode($noticia['conteudo']));
        $descricao = mb_strlen($desc_raw) > 150 ? mb_substr($desc_raw, 0, 147) . '...' : $desc_raw;
    } else {
        $erro = "Notícia não encontrada.";
    }
} else {
    $erro = "Parâmetro ID não fornecido.";
}

// Origem para botão Voltar
$origem = $_GET['from'] ?? 'index';
switch ($origem) {
    case 'blog':          $voltarPara = 'blog.php';           break;
    case 'blog-maxtrade': $voltarPara = 'blog-maxtrade.php';  break;
    default:              $voltarPara = 'index.php';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= $noticia ? $titulo : 'Detalhes da Notícia'; ?></title>

    <!-- Meta descrição padrão -->
    <?php if (isset($noticia)): ?>
        <meta name="description" content="<?= htmlspecialchars($descricao); ?>">
    <?php endif; ?>

    <!-- Canonical -->
    <?php if (isset($noticia)): ?>
        <link rel="canonical" href="<?= $url; ?>">
    <?php endif; ?>

    <!-- Open Graph -->
    <?php if (isset($noticia)): ?>
        <meta property="og:type"        content="article">
        <meta property="og:site_name"   content="Grupo Barão">
        <meta property="og:title"       content="<?= $titulo; ?>">
        <meta property="og:description" content="<?= htmlspecialchars($descricao); ?>">
        <meta property="og:image"        content="<?= $imagem ?>">
        <meta property="og:image:type"  content="image/jpeg"> <!-- ou image/png, se for o caso -->
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:url"         content="<?= $url; ?>">
        <meta property="og:locale"      content="pt_BR">
    <?php endif; ?>

    <!-- Twitter Card -->
    <?php if (isset($noticia)): ?>
        <meta name="twitter:card"        content="summary_large_image">
        <meta name="twitter:title"       content="<?= $titulo; ?>">
        <meta name="twitter:description" content="<?= htmlspecialchars($descricao); ?>">
        <meta name="twitter:image"       content="<?= $imagem; ?>">
    <?php endif; ?>

    <!-- Bootstrap & ícones -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">

    <style>
        body{font-family:'Roboto',sans-serif;background:#f8f9fa}
        .container{max-width:800px;margin:60px auto}
        .btn-back{font-weight:500}
        .card{border:none;transition:.3s}
        .card:hover{transform:translateY(-8px);box-shadow:0 12px 24px rgba(0,0,0,.15)}
        .news-header{position:relative;overflow:hidden;border-top-left-radius:.5rem;border-top-right-radius:.5rem}
        .news-header img{width:100%;height:400px;object-fit:cover}
        .gradient-overlay{position:absolute;bottom:0;left:0;width:100%;height:40%;background:linear-gradient(180deg,rgba(0,0,0,0) 0%,rgba(0,0,0,.7) 100%)}
        .hero-content{position:absolute;bottom:1rem;left:1rem;color:#fff;text-shadow:0 2px 6px rgba(0,0,0,.5)}
        .hero-content h1{font-size:2.5rem;margin:0}
        .hero-content .date-badge{background:rgba(255,255,255,.9);color:#333;font-size:.9rem;font-weight:500;padding:.4rem .8rem;border-radius:.25rem;margin-top:.5rem;display:inline-block}
        .share-icons a{color:#6c757d;font-size:1.4rem;transition:color .2s}
        .share-icons a:hover{color:#495057}
        .news-body{padding:2rem;font-size:1.05rem;line-height:1.7;color:#495057}
    </style>
</head>
<body>
<div class="container">
    <a href="<?= $voltarPara; ?>" class="btn btn-outline-secondary btn-back">
        <i class="bi bi-arrow-left-circle me-1"></i> Voltar
    </a>

    <?php if (isset($erro)): ?>
        <div class="alert alert-danger mt-4"><?= $erro; ?></div>
    <?php else: ?>
        <div class="card shadow-sm mt-4">
            <div class="news-header">
                <img src="<?= $imagem; ?>" alt="Imagem da notícia">
                <div class="gradient-overlay"></div>
                <div class="hero-content">
                    <h1><?= $titulo; ?></h1>
                    <span class="date-badge">Publicado em: <?= $data_publicacao; ?></span>
                </div>
            </div>
            <div class="news-body bg-white">
                <div class="d-flex justify-content-end mb-3 share-icons">
                    <!-- WhatsApp -->
                    <a href="https://wa.me/?text=<?= urlencode($titulo . ' - ' . $url); ?>" target="_blank" title="Compartilhar no WhatsApp">
                        <i class="bi bi-whatsapp"></i>
                    </a>
                    <!-- Compartilhar genérico -->
                    <a href="#" title="Compartilhar"><i class="bi bi-share-fill"></i></a>
                    <a href="#" title="Twitter"><i class="bi bi-twitter"></i></a>
                </div>
                <?= $conteudo_html; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
