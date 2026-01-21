<?php
// blog-rh.php — Lista de posts/FAQ de RH (busca + índice + paginação) com limpeza de entidades HTML

include 'admin/config.php'; // $pdo (PDO) com UTF-8

mb_internal_encoding('UTF-8');

$CATEGORIA   = 'RH';
$POR_PAGINA  = 6; // Aumentado para 6 como no blog.php
$MAX_Q_LEN   = 120;

/** Limpa bytes inválidos e normaliza Unicode */
function utf8_clean(?string $s): string {
  $s = (string)$s;
  $s = @iconv('UTF-8','UTF-8//IGNORE',$s);
  if (class_exists('Normalizer')) $s = Normalizer::normalize($s, Normalizer::FORM_C);
  return $s;
}

/** Converte HTML → texto plano (decodifica entidades, remove tags, normaliza espaços) */
function html_to_plain(string $html): string {
  $s = utf8_clean($html);
  $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  // substitui espaços não-quebrantes (NBSP/U+00A0 e NNBSP/U+202F) por espaço normal
  $s = str_replace(["\xC2\xA0", "\xE2\x80\xAF"], ' ', $s);
  $s = strip_tags($s);
  $s = preg_replace('/\s+/u', ' ', trim($s));
  return $s;
}

/** Resumo seguro de texto plano */
function resumoTexto(string $html, int $limite = 220): string {
  $txt = html_to_plain($html);
  return (mb_strlen($txt, 'UTF-8') > $limite)
    ? mb_substr($txt, 0, $limite - 1, 'UTF-8') . '…'
    : $txt;
}

/** Data PT-BR */
function dataPtBr(string $data): string {
  try { $dt = new DateTime($data); } catch (Exception $e) { return ''; }
  if (class_exists('IntlDateFormatter')) {
    $fmt = new IntlDateFormatter('pt_BR', IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE, 'America/Sao_Paulo', IntlDateFormatter::GREGORIAN, "d 'de' MMM 'de' y");
    return $fmt->format($dt) ?: $dt->format('d/m/Y');
  }
  return $dt->format('d/m/Y');
}

/** Destaque dos termos (em texto já escapado) */
function highlight_terms(string $safeText, string $q): string {
  $q = trim($q);
  if ($q === '') return $safeText;
  $terms = preg_split('/\s+/u', $q);
  $terms = array_values(array_unique(array_filter($terms, fn($t)=>mb_strlen($t,'UTF-8')>=2)));
  usort($terms, fn($a,$b)=>mb_strlen($b,'UTF-8')<=>mb_strlen($a,'UTF-8'));
  foreach ($terms as $t) {
    $pattern = '/' . preg_quote(htmlspecialchars($t, ENT_QUOTES, 'UTF-8'), '/') . '/iu';
    $safeText = preg_replace($pattern, '<mark>$0</mark>', $safeText);
  }
  return $safeText;
}

/** Monta URL de página preservando parâmetros */
function build_page_url(int $p): string {
  $params = $_GET; $params['pagina'] = $p;
  return htmlspecialchars(strtok($_SERVER['REQUEST_URI'], '?') . '?' . http_build_query($params), ENT_QUOTES, 'UTF-8');
}

// -------------------- Entrada --------------------
$pagina_req = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$pagina_req = max(1, $pagina_req);

$q_raw = isset($_GET['q']) ? (string)$_GET['q'] : '';
$q_raw = utf8_clean($q_raw);
$q_raw = mb_substr(trim($q_raw), 0, $MAX_Q_LEN, 'UTF-8');
$tem_busca = ($q_raw !== '');
$like = '%' . strtr($q_raw, ['%' => '\%', '_' => '\_']) . '%';

// -------------------- Total ----------------------
$where  = "categoria = :cat AND status = 'ativo'";
$params = [':cat' => $CATEGORIA];
if ($tem_busca) {
  $where .= " AND (titulo LIKE :q ESCAPE '\\\\' OR conteudo LIKE :q ESCAPE '\\\\')";
  $params[':q'] = $like;
}

$sqlTotal = "SELECT COUNT(*) AS total FROM noticias WHERE $where";
$stmtTotal = $pdo->prepare($sqlTotal);
$stmtTotal->execute($params);
$total_noticias = (int)($stmtTotal->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

$total_paginas = max(1, (int)ceil($total_noticias / $POR_PAGINA));
$pagina_atual  = min($pagina_req, $total_paginas);
$offset        = ($pagina_atual - 1) * $POR_PAGINA;

// -------------------- Itens ----------------------
$offset = (int)$offset; $limit = (int)$POR_PAGINA;
$sqlItens = "
  SELECT id, titulo, imagem, data_publicacao, conteudo
  FROM noticias
  WHERE $where
  ORDER BY data_publicacao DESC, id DESC
  LIMIT $offset, $limit
";
$stmt = $pdo->prepare($sqlItens);
$stmt->execute($params);
$noticias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prev/Next SEO
$has_prev = $pagina_atual > 1;
$has_next = ($total_noticias > 0) && ($pagina_atual < $total_paginas);
$baseUrl  = strtok($_SERVER['REQUEST_URI'], '?');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog RH - Recursos Humanos Grupo Barão</title>
    <?php if ($has_prev): ?><link rel="prev" href="<?= build_page_url($pagina_atual-1) ?>"><?php endif; ?>
    <?php if ($has_next): ?><link rel="next" href="<?= build_page_url($pagina_atual+1) ?>"><?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --rh-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --card-shadow-hover: 0 20px 40px rgba(0, 0, 0, 0.15);
            --border-radius: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: #2d3748;
            line-height: 1.6;
        }

        .hero-section {
            background: var(--rh-gradient);
            padding: 4rem 0;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            color: white;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }

        .hero-subtitle {
            font-size: 1.25rem;
            font-weight: 300;
            opacity: 0.9;
            margin-bottom: 2rem;
        }

        .search-container {
            max-width: 600px;
            margin: 0 auto;
            position: relative;
        }

        .search-form {
            display: flex;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 50px;
            padding: 8px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .search-input {
            flex: 1;
            border: none;
            padding: 12px 20px;
            border-radius: 50px;
            font-size: 1rem;
            background: transparent;
            outline: none;
        }

        .search-btn {
            background: var(--rh-gradient);
            border: none;
            border-radius: 50px;
            padding: 12px 24px;
            color: white;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .container-custom {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .blog-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .blog-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            position: relative;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.6s ease forwards;
        }

        .blog-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--card-shadow-hover);
        }

        .blog-card:nth-child(1) { animation-delay: 0.1s; }
        .blog-card:nth-child(2) { animation-delay: 0.2s; }
        .blog-card:nth-child(3) { animation-delay: 0.3s; }
        .blog-card:nth-child(4) { animation-delay: 0.4s; }
        .blog-card:nth-child(5) { animation-delay: 0.5s; }
        .blog-card:nth-child(6) { animation-delay: 0.6s; }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .blog-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
            transition: var(--transition);
        }

        .blog-card:hover .blog-image {
            transform: scale(1.05);
        }

        .blog-content {
            padding: 1.5rem;
        }

        .blog-date {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #4facfe;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .blog-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #2d3748;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .blog-excerpt {
            color: #718096;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .read-more-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--rh-gradient);
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .read-more-btn:hover {
            color: white;
            transform: translateX(4px);
            box-shadow: 0 4px 15px rgba(79, 172, 254, 0.4);
        }

        .pagination-container {
            display: flex;
            justify-content: center;
            margin: 3rem 0;
        }

        .pagination-custom {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .page-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            border: none;
            border-radius: 12px;
            background: white;
            color: #4facfe;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .page-btn:hover {
            background: var(--rh-gradient);
            color: white;
            transform: translateY(-2px);
        }

        .page-btn.active {
            background: var(--rh-gradient);
            color: white;
        }

        .page-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .page-btn.disabled:hover {
            transform: none;
            background: white;
            color: #4facfe;
        }

        .back-btn {
            background: var(--secondary-gradient);
            color: white;
            padding: 0 20px;
            border-radius: 25px;
            width: auto;
        }

        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
        }

        .no-results i {
            font-size: 4rem;
            color: #e2e8f0;
            margin-bottom: 1rem;
        }

        .no-results h3 {
            color: #4a5568;
            margin-bottom: 0.5rem;
        }

        .no-results p {
            color: #718096;
        }

        .stats-bar {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .stats-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #4a5568;
            font-weight: 500;
        }

        .clear-search {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            color: #4a5568;
            padding: 8px 16px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .clear-search:hover {
            background: #edf2f7;
            color: #2d3748;
        }

        .sidebar-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .sidebar-header {
            background: var(--rh-gradient);
            color: white;
            padding: 1rem 1.5rem;
            font-weight: 600;
        }

        .sidebar-content {
            padding: 1.5rem;
        }

        .index-link {
            display: block;
            padding: 0.75rem 1rem;
            color: #4a5568;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: var(--transition);
        }

        .index-link:hover {
            background: #f7fafc;
            color: #2d3748;
            transform: translateX(4px);
        }

        mark {
            background: linear-gradient(120deg, #a8edea 0%, #fed6e3 100%);
            padding: 0 .15em;
            border-radius: .2rem;
            color: #2d3748;
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1.1rem;
            }

            .blog-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .search-form {
                flex-direction: column;
                gap: 8px;
                padding: 12px;
            }

            .search-btn {
                border-radius: 12px;
            }

            .stats-bar {
                flex-direction: column;
                text-align: center;
            }

            .pagination-custom {
                flex-wrap: wrap;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .container-custom {
                padding: 0 15px;
            }

            .hero-section {
                padding: 3rem 0;
            }

            .blog-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .blog-content {
                padding: 1rem;
            }
        }

        .loading-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container-custom">
            <div class="hero-content">
                <h1 class="hero-title">Blog RH Grupo Barão</h1>
                <p class="hero-subtitle">Recursos Humanos, dicas e informações importantes para colaboradores</p>
                
                <div class="search-container">
                    <form method="get" class="search-form" role="search" aria-label="Buscar posts">
                        <input type="hidden" name="pagina" value="1">
                        <input 
                            name="q" 
                            class="search-input" 
                            type="search"
                            placeholder="Buscar por título ou conteúdo..." 
                            value="<?= htmlspecialchars($q_raw, ENT_QUOTES, 'UTF-8') ?>"
                        >
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container-custom">
        <!-- Stats Bar -->
        <div class="stats-bar">
            <div class="stats-info">
                <i class="fas fa-users"></i>
                <span><?= $total_noticias ?> post<?= $total_noticias===1?'':'s' ?> encontrado<?= $total_noticias===1?'':'s' ?></span>
                <?php if ($tem_busca): ?>
                    <span>para "<?= htmlspecialchars($q_raw, ENT_QUOTES, 'UTF-8') ?>"</span>
                <?php endif; ?>
            </div>
            
            <?php if ($tem_busca): ?>
                <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>" class="clear-search">
                    <i class="fas fa-times"></i> Limpar busca
                </a>
            <?php endif; ?>
        </div>

        <!-- Blog Grid -->
        <?php if ($total_noticias === 0): ?>
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h3>Nenhum post encontrado</h3>
                <p>Tente ajustar sua busca ou navegue pelos posts mais recentes.</p>
                <?php if ($tem_busca): ?>
                    <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>" class="read-more-btn" style="margin-top: 1rem;">
                        Ver todos os posts
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="blog-grid">
                <?php foreach ($noticias as $n):
                    $id       = (int)$n['id'];
                    $titulo   = html_to_plain((string)$n['titulo']);
                    $tituloSafe = htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');
                    $imgPath  = utf8_clean((string)$n['imagem']);
                    $imgPath  = html_entity_decode($imgPath, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $imgSrc   = htmlspecialchars($imgPath, ENT_QUOTES, 'UTF-8');
                    $dataPub  = dataPtBr((string)$n['data_publicacao']);
                    $resumo   = resumoTexto((string)$n['conteudo'], 150);
                    $resumoSafe = htmlspecialchars($resumo, ENT_QUOTES, 'UTF-8');
                    if ($tem_busca) { 
                        $resumoSafe = highlight_terms($resumoSafe, $q_raw); 
                        $tituloSafe = highlight_terms($tituloSafe, $q_raw); 
                    }
                    $anchor = "post-$id";
                ?>
                <article id="<?= $anchor ?>" class="blog-card">
                    <?php if ($imgSrc): ?>
                        <img 
                            src="<?= $imgSrc ?>" 
                            alt="<?= $tituloSafe ?>" 
                            class="blog-image"
                            loading="lazy" 
                            decoding="async" 
                            onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIyMCIgdmlld0JveD0iMCAwIDQwMCAyMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSI0MDAiIGhlaWdodD0iMjIwIiBmaWxsPSIjRjdGQUZDIi8+CjxwYXRoIGQ9Ik0xNzUgODBIMjI1VjEzMEgxNzVWODBaIiBmaWxsPSIjRTJFOEYwIi8+CjxwYXRoIGQ9Ik0xOTAgMTQwSDIxMFYxNjBIMTkwVjE0MFoiIGZpbGw9IiNFMkU4RjAiLz4KPC9zdmc+'"
                        >
                    <?php endif; ?>
                    <div class="blog-content">
                        <div class="blog-date">
                            <i class="fas fa-calendar-alt"></i>
                            <?= htmlspecialchars($dataPub, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        
                        <h2 class="blog-title"><?= $tituloSafe ?></h2>
                        
                        <p class="blog-excerpt"><?= $resumoSafe ?></p>
                        
                        <a href="blog_post.php?id=<?= $id ?>&from=blog-rh#content" class="read-more-btn">
                            Leia mais
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_paginas > 1): ?>
                <nav class="pagination-nav" aria-label="Paginação">
                    <div class="pagination-container">
                        <?php
                            $firstDisabled = $pagina_atual === 1;
                            $lastDisabled  = $pagina_atual === $total_paginas;
                            $window = 2;
                            $start = max(1, $pagina_atual - $window);
                            $end   = min($total_paginas, $pagina_atual + $window);
                        ?>
                        
                        <a href="<?= $pagina_atual>1 ? build_page_url($pagina_atual-1) : '#' ?>" 
                           class="pagination-btn <?= $firstDisabled ? 'disabled' : '' ?>"
                           <?= $firstDisabled ? 'aria-disabled="true"' : '' ?>>
                            <i class="fas fa-chevron-left"></i>
                            Anterior
                        </a>
                        
                        <div class="pagination-numbers">
                            <?php if ($start > 1): ?>
                                <a href="<?= build_page_url(1) ?>" class="pagination-number">1</a>
                                <?php if ($start > 2): ?>
                                    <span class="pagination-dots">...</span>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i=$start; $i<=$end; $i++): ?>
                                <a href="<?= build_page_url($i) ?>" 
                                   class="pagination-number <?= $i===$pagina_atual ? 'active' : '' ?>"
                                   <?= $i===$pagina_atual ? 'aria-current="page"' : '' ?>>
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($end < $total_paginas): ?>
                                <?php if ($end < $total_paginas - 1): ?>
                                    <span class="pagination-dots">...</span>
                                <?php endif; ?>
                                <a href="<?= build_page_url($total_paginas) ?>" class="pagination-number"><?= $total_paginas ?></a>
                            <?php endif; ?>
                        </div>
                        
                        <a href="<?= $pagina_atual<$total_paginas ? build_page_url($pagina_atual+1) : '#' ?>" 
                           class="pagination-btn <?= $lastDisabled ? 'disabled' : '' ?>"
                           <?= $lastDisabled ? 'aria-disabled="true"' : '' ?>>
                            Próxima
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                    
                    <div class="pagination-info">
                        Página <?= $pagina_atual ?> de <?= $total_paginas ?>
                    </div>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- JavaScript -->
    <script>
        // Lazy loading images
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }

        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Card hover effects
        document.querySelectorAll('.blog-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Auto-focus search input
        const searchInput = document.querySelector('.search-input');
        if (searchInput && !searchInput.value) {
            searchInput.focus();
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
