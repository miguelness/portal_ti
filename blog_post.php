<?php
require_once 'conexao.php';
require_once 'reader-session.php';

/* ——— BUSCA DA NOTÍCIA —————————————————————— */
$nid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($nid <= 0) {
    http_response_code(404);
    exit('Notícia não encontrada.');
}

$stmt = $conn->prepare('SELECT * FROM noticias WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $nid);
$stmt->execute();
$noticia = $stmt->get_result()->fetch_assoc() ?: null;
if (!$noticia) {
    http_response_code(404);
    exit('Notícia não encontrada.');
}

$titulo          = htmlspecialchars($noticia['titulo']);
$conteudo_html   = nl2br(htmlspecialchars_decode($noticia['conteudo']));
$imagem          = trim($noticia['imagem'] ?? '');
$data_publicacao = date('d \d\e M Y', strtotime($noticia['data_publicacao']));

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$url      = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
if ($imagem && !preg_match('#^https?://#', $imagem)) {
    $imagem = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/portal/' . ltrim($imagem, '/');
}

$desc_raw  = strip_tags(htmlspecialchars_decode($noticia['conteudo']));
$descricao = mb_strlen($desc_raw) > 150 ? mb_substr($desc_raw, 0, 147) . '…' : $desc_raw;

/* ——— CRIA VARIÁVEL $article PARA COMPATIBILIDADE —————————————————————————— */
// Obter contagem de visualizações da tabela article_views
$stmt = $conn->prepare('SELECT COUNT(*) FROM article_views WHERE noticia_id = ?');
$stmt->bind_param('i', $nid);
$stmt->execute();
$views_count = (int)$stmt->get_result()->fetch_column();
$stmt->close();

$article = [
    'id' => $noticia['id'],
    'title' => $noticia['titulo'],
    'content' => $conteudo_html,
    'image' => $noticia['imagem'], // Valor original para uso local
    'image_url' => $imagem, // URL completa para meta tags
    'created_at' => $noticia['data_publicacao'],
    'views' => $views_count,
    'subtitle' => $noticia['subtitulo'] ?? ''
];

/* ——— CONTROLES OPCIONAIS DE ENQUADRAMENTO VIA QUERY ———————————————— */
$focus = $_GET['focus'] ?? ''; // valores aceitos: top, center, bottom
$fit   = $_GET['fit']   ?? ''; // valores aceitos: cover, contain
$bgPos = match (strtolower($focus)) {
    'top'    => 'center top',
    'bottom' => 'center bottom',
    'center' => 'center 30%',
    default  => 'center 30%',
};
$bgSize = strtolower($fit) === 'contain' ? 'contain' : 'cover';

/* ——— REGISTRA VIEW —————————————————————————— */
if (!isset($_SESSION["viewed_$nid"])) {
    $rid = $readerId ?? null;
    $ip  = $_SERVER['REMOTE_ADDR']      ?? '';
    $ua  = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250);
    $stmt = $conn->prepare(
        'INSERT INTO article_views (noticia_id, reader_id, ip, user_agent)
         VALUES (?,?,?,?)'
    );
    $stmt->bind_param('iiss', $nid, $rid, $ip, $ua);
    $stmt->execute();
    
    // Nota: O campo visualizacoes não existe na tabela noticias
    // As visualizações são registradas apenas na tabela article_views
    
    $_SESSION["viewed_$nid"] = true;
}

$origem = $_GET['from'] ?? 'index';
$voltarPara = match ($origem) {
    'blog'          => 'blog.php',
    'blog-maxtrade' => 'blog-maxtrade.php',
    default         => 'index.php',
};
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['title']); ?> - Portal de Notícias</title>
    <meta name="description" content="<?php echo htmlspecialchars(substr(strip_tags($article['content']), 0, 160)); ?>">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($article['title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars(substr(strip_tags($article['content']), 0, 160)); ?>">
    <meta property="og:image" content="<?php echo $article['image_url'] ?: 'assets/default-article.jpg'; ?>">
    <meta property="og:url" content="<?php echo $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:type" content="article">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($article['title']); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars(substr(strip_tags($article['content']), 0, 160)); ?>">
    <meta name="twitter:image" content="<?php echo $article['image_url'] ?: 'assets/default-article.jpg'; ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Merriweather:wght@300;400;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --accent-color: #f59e0b;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --bg-light: #f8fafc;
            --bg-white: #ffffff;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --page-gradient-start: #eef2ff; /* Indigo-50 */
            --page-gradient-end: #e2e8f0;   /* Slate-200 */
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.7;
            color: var(--text-primary);
            background:
              radial-gradient(1200px 600px at 10% 0%, rgba(37, 99, 235, 0.06), transparent 60%),
              linear-gradient(135deg, var(--page-gradient-start) 0%, var(--page-gradient-end) 100%);
            margin: 0;
            padding: 0;
        }

        /* Typography */
        .article-title {
            font-family: 'Merriweather', Georgia, serif;
            font-size: clamp(2rem, 5vw, 3.5rem);
            font-weight: 700;
            line-height: 1.2;
            color: white;
            margin-bottom: 1.5rem;
            letter-spacing: -0.02em;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
        }

        .article-subtitle {
            font-size: 1.25rem;
            color: var(--text-secondary);
            font-weight: 400;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .article-content {
            font-family: 'Merriweather', Georgia, serif;
            font-size: 1.125rem;
            line-height: 1.6; /* ritmo mais próximo do editor */
            color: var(--text-primary);
        }

        .article-content p {
            margin-bottom: 0.75rem; /* reduzir espaço entre parágrafos */
        }

        /* Ritmo de espaçamento consistente sem expandir demais */
        .article-content > :first-child { margin-top: 0 !important; }
        .article-content > :last-child { margin-bottom: 0 !important; }
        .article-content hr { margin: 1rem 0; border-color: var(--border-color); }

        .article-content ul,
        .article-content ol {
            margin: 0.5rem 0; /* compacto como no editor */
            padding-left: 1.25rem; /* leve redução de indentação */
            list-style-position: outside;
            list-style-type: disc; /* garantir bullets padronizados */
        }

        .article-content li {
            margin: 0.15rem 0; /* reduzir espaço entre itens */
            line-height: 1.5; /* bullets mais concentrados */
        }

        /* ajustar margens entre parágrafos e listas adjacentes */
        .article-content p + ul,
        .article-content p + ol {
            margin-top: 0.25rem;
        }

        .article-content ul + p,
        .article-content ol + p {
            margin-top: 0.5rem;
        }

        .article-content li::marker {
            color: var(--text-secondary);
        }

        /* Remove margens internas quando editores geram <li><p>… */
        .article-content li p { margin: 0; }

        .article-content h2,
        .article-content h3,
        .article-content h4 {
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            margin-top: 1.5rem; /* títulos menos afastados */
            margin-bottom: 0.75rem;
            color: var(--text-primary);
        }

        .article-content h2 {
            font-size: 1.875rem;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
        }

        .article-content h3 {
            font-size: 1.5rem;
        }

        .article-content h4 {
            font-size: 1.25rem;
        }

        /* Layout */
        .article-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .article-header {
            <?php 
            // Verificar se existe imagem e determinar o caminho correto
            if (!empty($article['image'])) {
                // Se já contém uploads/, usar diretamente, senão adicionar
                $image_path = (strpos($article['image'], 'uploads/') === 0) ? $article['image'] : 'uploads/' . $article['image'];
                $has_image = file_exists($image_path);
            } else {
                $has_image = false;
                $image_path = '';
            }
            
            if ($has_image): ?>
            background: linear-gradient(rgba(0, 0, 0, 0.55), rgba(0, 0, 0, 0.55)), url('<?php echo htmlspecialchars($image_path); ?>');
            background-size: <?php echo $bgSize; ?>;
            background-position: <?php echo $bgPos; ?>;
            background-repeat: no-repeat;
            background-attachment: scroll;
            <?php else: ?>
            background: linear-gradient(135deg, var(--primary-color) 0%, #3b82f6 100%);
            <?php endif; ?>
            color: white;
            padding: 3.5rem 0 2.5rem;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
            min-height: 420px;
            display: flex;
            align-items: center;
        }

        .article-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            <?php if ($has_image): ?>
            background: linear-gradient(180deg, rgba(0,0,0,0.55) 0%, rgba(0,0,0,0.35) 60%, rgba(0,0,0,0.25) 100%);
            <?php else: ?>
            background: linear-gradient(180deg, rgba(0,0,0,0.25) 0%, rgba(0,0,0,0.15) 60%, rgba(0,0,0,0.1) 100%);
            <?php endif; ?>
            opacity: 1;
        }

        .article-header .container {
            position: relative;
            z-index: 2;
        }

        /* Painel para melhorar legibilidade do título/metadados */
        .hero-panel {
            background: rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(6px);
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            max-width: 900px;
            margin: 0 auto;
            box-shadow: 0 10px 25px rgba(0,0,0,0.25);
        }

        .article-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            align-items: center;
            margin-bottom: 2rem;
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.9);
            position: relative;
            z-index: 1;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .meta-item i {
            font-size: 1.1rem;
        }

        .reading-time {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .article-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            margin: 2rem 0;
        }

        .article-body {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            padding: 2rem; /* reduzir padding para compactar conteúdo */
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            margin-bottom: 2rem; /* reduzir espaço inferior */
        }

        /* Social Sharing */
        .social-sharing {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            margin-bottom: 3rem;
            text-align: center;
        }

        .social-sharing h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
        }

        .social-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .social-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: var(--transition);
            border: none;
            cursor: pointer;
        }

        .social-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .social-btn.facebook {
            background: #1877f2;
            color: white;
        }

        .social-btn.twitter {
            background: #1da1f2;
            color: white;
        }

        .social-btn.linkedin {
            background: #0a66c2;
            color: white;
        }

        .social-btn.whatsapp {
            background: #25d366;
            color: white;
        }

        .social-btn.copy {
            background: var(--text-secondary);
            color: white;
        }

        /* Comments Section */
        .comments-section {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            padding: 3rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            margin-bottom: 3rem;
        }

        .comments-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }

        .comments-header h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            color: var(--text-primary);
        }

        .comments-count {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .comment-form {
            background: var(--bg-light);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        /* Comment styles for API loaded comments */
        #comments-list .d-flex {
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem 0;
        }

        #comments-list .comment-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1rem;
        }

        #comments-list .fw-semibold {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        #comments-list .small.text-muted {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        #comments-list p {
            color: var(--text-primary);
            line-height: 1.6;
            margin-bottom: 0;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
            background: var(--bg-white);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.875rem 2rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        /* Quote Styling */
        .article-content blockquote {
            background: linear-gradient(135deg, var(--bg-light) 0%, #f1f5f9 100%);
            border-left: 4px solid var(--primary-color);
            margin: 1.25rem 0; /* reduzir margem do destaque */
            padding: 1.25rem;   /* reduzir padding do destaque */
            border-radius: 0 var(--border-radius) var(--border-radius) 0;
            font-style: italic;
            font-size: 1.25rem;
            color: var(--text-secondary);
            position: relative;
        }

        .article-content blockquote::before {
            content: '"';
            font-size: 4rem;
            color: var(--primary-color);
            position: absolute;
            top: -0.5rem;
            left: 1rem;
            font-family: Georgia, serif;
            opacity: 0.3;
        }

        /* Image Styling */
        .article-content img {
            width: 100%;
            height: auto;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            margin: 1.25rem 0; /* reduzir espaço ao redor de imagens */
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .article-container {
                padding: 0 0.75rem;
            }

            .article-header {
                padding: 2rem 0 1.5rem;
                margin-bottom: 2rem;
            }

            .article-body {
                padding: 2rem 1.5rem;
            }

            .social-sharing,
            .comments-section {
                padding: 2rem 1.5rem;
            }

            .article-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .social-buttons {
                flex-direction: column;
                align-items: center;
            }

            .social-btn {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .article-body {
                padding: 1.5rem 1rem;
            }

            .social-sharing,
            .comments-section {
                padding: 1.5rem 1rem;
            }

            .comment-form {
                padding: 1.5rem;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Success/Error Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            border: 1px solid transparent;
        }

        .alert-success {
            background: #dcfce7;
            border-color: #bbf7d0;
            color: #166534;
        }

        .alert-error {
            background: #fef2f2;
            border-color: #fecaca;
            color: #dc2626;
        }

        /* Related Posts */
        .related-posts {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            padding: 3rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            margin-bottom: 3rem;
        }

        .related-posts h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 2rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .related-posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .related-post-card {
            background: var(--bg-light);
            border-radius: var(--border-radius);
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid var(--border-color);
            text-decoration: none;
            color: inherit;
        }

        .related-post-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            text-decoration: none;
            color: inherit;
        }

        .related-post-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: linear-gradient(135deg, var(--primary-color) 0%, #3b82f6 100%);
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            transition: transform 0.3s ease;
        }

        .related-post-card:hover .related-post-image {
            transform: scale(1.05);
        }

        .related-post-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: opacity 0.3s ease;
        }

        .related-post-image.loading {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .related-post-content {
            padding: 1.5rem;
        }

        .related-post-title {
            font-size: 1.125rem;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .related-post-excerpt {
            font-size: 0.95rem;
            color: var(--text-secondary);
            line-height: 1.5;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .related-post-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .related-post-meta i {
            font-size: 1rem;
        }

        /* Responsive optimizations */
        @media (max-width: 768px) {
            .article-header {
                padding: 2.5rem 0 1.75rem;
                min-height: 320px;
                background-attachment: scroll; /* Better performance on mobile */
                background-position: center 25%;
            }
            .hero-panel {
                padding: 1rem 1.25rem;
                border-radius: 14px;
                max-width: 95%;
            }

            .article-container {
                padding: 0 0.75rem;
            }

            .related-posts {
                padding: 2rem 1.5rem;
            }

            .related-posts-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .related-post-image {
                height: 180px;
            }
        }

        @media (max-width: 480px) {
            .article-header {
                padding: 2rem 0 1.5rem;
                min-height: 280px;
                background-position: center 20%;
            }
            .hero-panel {
                padding: 0.875rem 1rem;
                border-radius: 12px;
                max-width: 96%;
            }

            .article-meta {
                gap: 1rem;
                font-size: 0.875rem;
            }

            .reading-time {
                padding: 0.4rem 0.8rem;
                font-size: 0.8rem;
            }

            .related-posts {
                padding: 1.5rem 1rem;
            }

            .related-post-image {
                height: 160px;
            }

            .related-post-content {
                padding: 1rem;
            }
        }

        /* High DPI displays optimization */
        @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
            .article-header {
                background-size: cover;
                image-rendering: -webkit-optimize-contrast;
                image-rendering: crisp-edges;
            }
        }

        /* Prefers reduced motion */
        @media (prefers-reduced-motion: reduce) {
            .related-post-image,
            .related-post-card:hover .related-post-image {
                transition: none;
                transform: none;
            }

            .loading {
                animation: none;
            }
        }

        /* Parallax background (minimal, elegante) */
        :root { --scrollY: 0; }

        .parallax-scene {
            position: fixed;
            inset: 0;
            z-index: -1;
            pointer-events: none;
            overflow: hidden;
        }

        .parallax-layer {
            position: absolute;
            width: 140vw;
            height: 140vh;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            will-change: top, opacity;
        }

        .parallax-layer.l1 {
            /* suave indigo glow */
            background: radial-gradient(600px 400px at 20% 15%, rgba(37, 99, 235, 0.08), transparent 60%);
            top: calc(50% + (var(--scrollY) * -0.05));
        }

        .parallax-layer.l2 {
            /* suave amber glow */
            background: radial-gradient(800px 500px at 80% 10%, rgba(245, 158, 11, 0.07), transparent 65%);
            top: calc(50% + (var(--scrollY) * -0.10));
        }

        @media (prefers-reduced-motion: reduce) {
            .parallax-layer { top: 50% !important; }
        }
    </style>
</head>
<body>
    <!-- Parallax Scene (decorativo, minimalista) -->
    <div class="parallax-scene" aria-hidden="true">
        <div class="parallax-layer l1"></div>
        <div class="parallax-layer l2"></div>
    </div>
    <article class="article-container">
        <!-- Article Header -->
        <header class="article-header">
            <div class="container">
                <div class="hero-panel">
                <div class="article-meta">
                    <div class="meta-item">
                        <i class="bi bi-calendar3"></i>
                        <span><?php echo date('d/m/Y', strtotime($article['created_at'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="bi bi-person"></i>
                        <span>Portal de Notícias</span>
                    </div>
                    <div class="meta-item">
                        <i class="bi bi-eye"></i>
                        <span><?php echo number_format($article['views']); ?> visualizações</span>
                    </div>
                    <div class="reading-time">
                        <i class="bi bi-clock"></i>
                        <span id="reading-time">Calculando...</span>
                    </div>
                </div>
                <h1 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h1>
                <?php if (!empty($article['subtitle'])): ?>
                    <p class="article-subtitle"><?php echo htmlspecialchars($article['subtitle']); ?></p>
                <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Article image is now used as header background -->

        <!-- Article Content -->
        <div class="article-body">
            <div class="article-content" id="article-content">
                <?php echo $article['content']; ?>
            </div>
        </div>

        <!-- Social Sharing -->
        <div class="social-sharing">
            <h3><i class="bi bi-share"></i> Compartilhar este artigo</h3>
            <div class="social-buttons">
                <a href="#" class="social-btn facebook" onclick="shareOnFacebook()" title="Compartilhar no Facebook">
                    <i class="bi bi-facebook"></i>
                    <span>Facebook</span>
                </a>
                <a href="#" class="social-btn twitter" onclick="shareOnTwitter()" title="Compartilhar no Twitter">
                    <i class="bi bi-twitter"></i>
                    <span>Twitter</span>
                </a>
                <a href="#" class="social-btn linkedin" onclick="shareOnLinkedIn()" title="Compartilhar no LinkedIn">
                    <i class="bi bi-linkedin"></i>
                    <span>LinkedIn</span>
                </a>
                <a href="#" class="social-btn whatsapp" onclick="shareOnWhatsApp()" title="Compartilhar no WhatsApp">
                    <i class="bi bi-whatsapp"></i>
                    <span>WhatsApp</span>
                </a>
                <button class="social-btn copy" onclick="copyLink()" title="Copiar link">
                    <i class="bi bi-link-45deg"></i>
                    <span>Copiar Link</span>
                </button>
            </div>
        </div>

        <!-- Comments Section -->
        <section class="comments-section">
            <div class="comments-header">
                <h3><i class="bi bi-chat-dots"></i> Comentários</h3>
                <span class="comments-count" id="comments-count">0</span>
            </div>

            <!-- Comment Form -->
            <div id="comment-form-container">
                <!-- Form will be loaded here based on login status -->
            </div>

            <!-- Comments List -->
            <div id="comments-list">
                <!-- Comments will be loaded here -->
            </div>
        </section>

        <!-- Related Posts Section -->
        <section class="related-posts">
            <h3><i class="bi bi-newspaper"></i> Artigos Relacionados</h3>
            <div class="related-posts-grid" id="related-posts">
                <!-- Related posts will be loaded here -->
            </div>
        </section>
    </article>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Calculate reading time
        function calculateReadingTime() {
            const content = document.getElementById('article-content');
            const text = content.textContent || content.innerText;
            const wordsPerMinute = 200;
            const words = text.trim().split(/\s+/).length;
            const readingTime = Math.ceil(words / wordsPerMinute);
            
            document.getElementById('reading-time').textContent = `${readingTime} min de leitura`;
        }

        // Social sharing functions
        function shareOnFacebook() {
            const url = encodeURIComponent(window.location.href);
            const title = encodeURIComponent(document.title);
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank', 'width=600,height=400');
        }

        function shareOnTwitter() {
            const url = encodeURIComponent(window.location.href);
            const title = encodeURIComponent(document.title);
            window.open(`https://twitter.com/intent/tweet?url=${url}&text=${title}`, '_blank', 'width=600,height=400');
        }

        function shareOnLinkedIn() {
            const url = encodeURIComponent(window.location.href);
            const title = encodeURIComponent(document.title);
            window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${url}`, '_blank', 'width=600,height=400');
        }

        function shareOnWhatsApp() {
            const url = encodeURIComponent(window.location.href);
            const title = encodeURIComponent(document.title);
            window.open(`https://wa.me/?text=${title} ${url}`, '_blank');
        }

        function copyLink() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                const btn = event.target.closest('.social-btn');
                const originalText = btn.querySelector('span').textContent;
                btn.querySelector('span').textContent = 'Copiado!';
                btn.style.background = '#10b981';
                
                setTimeout(() => {
                    btn.querySelector('span').textContent = originalText;
                    btn.style.background = '';
                }, 2000);
            });
        }

        // Comments functionality
        async function initializeComments() {
            // Load comment form based on login status
            await loadCommentForm();
            // Load existing comments
            await loadComments();
        }

        async function loadCommentForm() {
            const container = document.getElementById('comment-form-container');
            
            // Check if user is logged in by trying to get current user info
            try {
                const response = await fetch('api/current-user.php');
                const user = await response.json();
                
                if (user.logged_in) {
                    // User is logged in, show comment form
                    container.innerHTML = `
                        <form class="comment-form" id="comment-form">
                            <div class="form-group">
                                <label for="comment-text" class="form-label">Comentário *</label>
                                <textarea id="comment-text" name="comment" class="form-control" rows="4" required placeholder="Deixe seu comentário..."></textarea>
                            </div>
                            <button type="submit" class="btn-primary">
                                <span class="btn-text">Enviar Comentário</span>
                                <span class="loading" style="display: none;"></span>
                            </button>
                        </form>
                    `;
                    
                    // Add event listener to the form
                    document.getElementById('comment-form').addEventListener('submit', handleCommentSubmit);
                } else {
                    // User not logged in, show login prompt
                    container.innerHTML = `
                        <div style="text-align: center; padding: 2rem; background: var(--bg-secondary); border-radius: 8px; border: 1px solid var(--border-color);">
                            <p style="margin-bottom: 1rem; color: var(--text-muted);">Para comentar, você precisa estar logado.</p>
                            <a href="reader-login.php" class="btn-primary" style="text-decoration: none;">Fazer Login</a>
                        </div>
                    `;
                }
            } catch (error) {
                // Fallback: show login prompt
                container.innerHTML = `
                    <div style="text-align: center; padding: 2rem; background: var(--bg-secondary); border-radius: 8px; border: 1px solid var(--border-color);">
                        <p style="margin-bottom: 1rem; color: var(--text-muted);">Para comentar, você precisa estar logado.</p>
                        <a href="reader-login.php" class="btn-primary" style="text-decoration: none;">Fazer Login</a>
                    </div>
                `;
            }
        }

        async function handleCommentSubmit(e) {
            e.preventDefault();
            
            const comment = document.getElementById('comment-text').value.trim();
            
            if (!comment) {
                showAlert('Por favor, digite um comentário.', 'error');
                return;
            }
            
            const submitBtn = this.querySelector('.btn-primary');
            const btnText = submitBtn.querySelector('.btn-text');
            const loading = submitBtn.querySelector('.loading');
            
            // Show loading state
            btnText.style.display = 'none';
            loading.style.display = 'inline-block';
            submitBtn.disabled = true;
            
            try {
                const response = await fetch('api/comment_add.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: <?php echo $article['id']; ?>,
                        comment: comment
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert(result.message || 'Comentário enviado com sucesso! Aguardando moderação.', 'success');
                    this.reset();
                    // Don't reload comments immediately since they need moderation
                } else {
                    showAlert(result.message || 'Erro ao enviar comentário', 'error');
                }
            } catch (error) {
                showAlert('Erro ao enviar comentário', 'error');
            } finally {
                // Hide loading state
                btnText.style.display = 'inline';
                loading.style.display = 'none';
                submitBtn.disabled = false;
            }
        }

        // Load comments
        async function loadComments() {
            try {
                const response = await fetch(`api/comment_list.php?id=<?php echo $article['id']; ?>`);
                const result = await response.json();
                
                const commentsList = document.getElementById('comments-list');
                const commentsCount = document.getElementById('comments-count');
                
                if (result.html && result.html.trim()) {
                    commentsList.innerHTML = result.html;
                    // Count comments by counting comment divs
                    const commentDivs = commentsList.querySelectorAll('.d-flex.mb-3');
                    commentsCount.textContent = commentDivs.length;
                } else {
                    commentsList.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 2rem;">Seja o primeiro a comentar!</p>';
                    commentsCount.textContent = '0';
                }
            } catch (error) {
                console.error('Erro ao carregar comentários:', error);
                document.getElementById('comments-list').innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 2rem;">Erro ao carregar comentários.</p>';
            }
        }

        // Show alert messages
        function showAlert(message, type) {
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            
            const form = document.getElementById('comment-form');
            form.parentNode.insertBefore(alert, form);
            
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }

        // Load related posts
        async function loadRelatedPosts() {
            try {
                const response = await fetch(`api/related_posts.php?article_id=<?php echo $article['id']; ?>`);
                const posts = await response.json();
                
                const relatedPostsGrid = document.getElementById('related-posts');
                
                if (posts.length === 0) {
                    relatedPostsGrid.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 2rem;">Nenhum artigo relacionado encontrado.</p>';
                    return;
                }
                
                relatedPostsGrid.innerHTML = posts.map(post => {
                    // Determinar o caminho correto da imagem
                    const imagePath = post.image ? (post.image.startsWith('uploads/') ? post.image : 'uploads/' + post.image) : '';
                    
                    return `
                    <a href="blog_post.php?id=${post.id}" class="related-post-card">
                        ${post.image ? 
                            `<div class="related-post-image loading">
                                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='1' height='1'%3E%3C/svg%3E" 
                                     data-src="${imagePath}" 
                                     alt="${post.title}" 
                                     loading="lazy"
                                     onload="this.parentElement.classList.remove('loading'); this.style.opacity='1';"
                                     onerror="this.parentElement.innerHTML='<div style=\\'display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem;\\'><i class=\\'bi bi-newspaper\\'></i></div>';"
                                     style="opacity: 0;">
                             </div>` :
                            `<div class="related-post-image" style="display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem;"><i class="bi bi-newspaper"></i></div>`
                        }
                        <div class="related-post-content">
                            <h4 class="related-post-title">${post.title}</h4>
                            <p class="related-post-excerpt">${post.excerpt}</p>
                            <div class="related-post-meta">
                                <span><i class="bi bi-calendar3"></i> ${new Date(post.created_at).toLocaleDateString('pt-BR')}</span>
                                <span><i class="bi bi-eye"></i> ${post.views} visualizações</span>
                            </div>
                        </div>
                    </a>
                    `;
                }).join('');
                
                // Implement lazy loading for images
                const images = relatedPostsGrid.querySelectorAll('img[data-src]');
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                            observer.unobserve(img);
                        }
                    });
                });
                
                images.forEach(img => imageObserver.observe(img));
            } catch (error) {
                console.error('Erro ao carregar posts relacionados:', error);
                document.getElementById('related-posts').innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 2rem;">Erro ao carregar artigos relacionados.</p>';
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            sanitizeEditorArtifacts();
            normalizeBullets();
            calculateReadingTime();
            initializeComments();
            loadRelatedPosts();
        });
    </script>
    <!-- Parallax scroll driver (leve, respeita prefers-reduced-motion) -->
    <script>
        // Remove nós vazios e <br> redundantes vindos do editor
        function sanitizeEditorArtifacts() {
          const content = document.getElementById('article-content');
          if (!content) return;
          const removableTags = new Set(['BR']);
          const candidates = new Set(['P','DIV','SPAN']);

          const isEmptyText = (t) => !t || /^([\s\u00A0])+$/m.test(t);
          const getText = (node) => (node && (node.textContent || '')).trim();

          const nodes = Array.from(content.childNodes);
          for (const node of nodes) {
            if (node.nodeType === Node.ELEMENT_NODE) {
              if (removableTags.has(node.tagName)) {
                node.remove();
                continue;
              }
              if (candidates.has(node.tagName) && isEmptyText(getText(node))) {
                node.remove();
              }
            } else if (node.nodeType === Node.TEXT_NODE) {
              if (isEmptyText(node.textContent)) node.remove();
            }
          }
        }

        // Normaliza bullets vindos do editor: "• texto" ou linhas separadas "•" + "texto"
        function normalizeBullets() {
          const content = document.getElementById('article-content');
          if (!content) return;

          const isBulletPrefix = (t) => /^(•|\-|–|—|\*)(?:\s|\n|\r|\u00A0)+/.test(t);
          const isBulletOnly = (t) => /^(•|\-|–|—|\*)$/.test(t);
          const isEmpty = (t) => !t || /^([\s\u00A0])+$/m.test(t);

          const candidates = ['P','DIV','SPAN'];
          const getText = (node) => (node && (node.textContent || '')).trim();
          const isSkippable = (node) => (
            !node ||
            (node.nodeType === Node.TEXT_NODE && isEmpty(node.textContent)) ||
            (node.nodeType === Node.ELEMENT_NODE && (
              node.tagName === 'BR' || (candidates.includes(node.tagName) && isEmpty(getText(node)))
            ))
          );

          // Trabalhar sobre uma cópia dos nós para evitar efeitos de reordenação
          const nodes = Array.from(content.childNodes);
          let ul = null;
          for (let idx = 0; idx < nodes.length; idx++) {
            const node = nodes[idx];
            if (node.nodeType === Node.ELEMENT_NODE && (candidates.includes(node.tagName))) {
              const txt = getText(node);

              // Caso 1: "• texto" na mesma linha (inclusive quando há <br>)
              if (isBulletPrefix(txt)) {
                if (!ul) {
                  ul = document.createElement('ul');
                  ul.className = 'normalized-bullets';
                  content.insertBefore(ul, node);
                }
                const li = document.createElement('li');
                li.textContent = txt.replace(/^(•|\-|–|—|\*)(?:\s|\n|\r|\u00A0)+/, '');
                ul.appendChild(li);
                node.remove();
                continue;
              }

              // Caso 2: nó somente com o bullet, seguido do nó com texto
              if (isBulletOnly(txt)) {
                let lookAhead = node.nextSibling;
                while (isSkippable(lookAhead)) lookAhead = lookAhead?.nextSibling || null;
                if (lookAhead && lookAhead.nodeType === Node.ELEMENT_NODE && candidates.includes(lookAhead.tagName)) {
                  if (!ul) {
                    ul = document.createElement('ul');
                    ul.className = 'normalized-bullets';
                    content.insertBefore(ul, node);
                  }
                  const li = document.createElement('li');
                  li.textContent = getText(lookAhead);
                  ul.appendChild(li);
                  lookAhead.remove();
                  node.remove();
                  continue;
                }
              }

              // Outros nós encerram a lista
              ul = null;
            } else {
              // BRs e nós de texto interrompem listas
              ul = null;
            }
          }

          // Se houver múltiplas <ul> consecutivas criadas, mesclar
          const lists = Array.from(content.querySelectorAll('ul.normalized-bullets'));
          for (let i = 1; i < lists.length; i++) {
            const prev = lists[i-1];
            const curr = lists[i];
            if (prev.nextSibling === curr) {
              while (curr.firstChild) prev.appendChild(curr.firstChild);
              curr.remove();
            }
          }
        }

      (function() {
        const mql = window.matchMedia('(prefers-reduced-motion: reduce)');
        if (mql.matches) return;

        const root = document.documentElement;
        let lastY = 0;
        let ticking = false;

        function update() {
          root.style.setProperty('--scrollY', String(lastY));
          ticking = false;
        }

        function onScroll() {
          lastY = window.pageYOffset || document.documentElement.scrollTop || 0;
          if (!ticking) {
            requestAnimationFrame(update);
            ticking = true;
          }
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
      })();
    </script>
</body>
</html>
