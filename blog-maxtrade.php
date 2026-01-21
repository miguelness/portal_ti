<?php
include('admin/config.php'); // Inclui a conexão com o banco de dados e as configurações

// Define o número de notícias por página
$noticias_por_pagina = 6;

// Obtém o número da página atual a partir da URL; define a página como 1 caso não seja passado nenhum valor
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

// Obtém o termo de busca se fornecido
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';

// Calcula o índice inicial para a consulta SQL
$indice_inicial = ($pagina_atual - 1) * $noticias_por_pagina;

// Constrói a consulta com ou sem busca
$where_clause = "WHERE categoria = 'Maxtrade' AND status = 'ativo'";
$params = [];

if (!empty($busca)) {
    $where_clause .= " AND (titulo LIKE :busca OR conteudo LIKE :busca)";
    $params[':busca'] = '%' . $busca . '%';
}

// Consulta para contar o total de notícias ativas
$query_total = "SELECT COUNT(*) AS total FROM noticias $where_clause";
$stmt_total = $pdo->prepare($query_total);
foreach ($params as $key => $value) {
    $stmt_total->bindValue($key, $value);
}
$stmt_total->execute();
$total_noticias = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];

// Consulta para selecionar as notícias com limite de paginação
$query = "SELECT * FROM noticias $where_clause ORDER BY data_publicacao DESC LIMIT :indice_inicial, :noticias_por_pagina";
$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindParam(':indice_inicial', $indice_inicial, PDO::PARAM_INT);
$stmt->bindParam(':noticias_por_pagina', $noticias_por_pagina, PDO::PARAM_INT);
$stmt->execute();
$noticias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcula o número total de páginas
$total_paginas = ceil($total_noticias / $noticias_por_pagina);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualizações Maxtrade - TI Grupo Barão</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #2509cf 0%, #6f42c1 100%);
            --secondary-gradient: linear-gradient(135deg, #6f42c1 0%, #2509cf 100%);
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
            background: linear-gradient(135deg, #f0f4ff 0%, #e6e6ff 100%);
            min-height: 100vh;
            color: #2d3748;
            line-height: 1.6;
        }

        .hero-section {
            background: var(--primary-gradient);
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
            background: var(--primary-gradient);
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
            color: #2509cf;
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
            background: var(--primary-gradient);
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
            box-shadow: 0 4px 15px rgba(37, 9, 207, 0.4);
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
            color: #2509cf;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .page-btn:hover {
            background: var(--primary-gradient);
            color: white;
            transform: translateY(-2px);
        }

        .page-btn.active {
            background: var(--primary-gradient);
            color: white;
        }

        .page-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .page-btn.disabled:hover {
            transform: none;
            background: white;
            color: #2509cf;
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

        .maxtrade-badge {
            background: var(--primary-gradient);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container-custom">
            <div class="hero-content">
                <h1 class="hero-title">Atualizações Maxtrade</h1>
                <p class="hero-subtitle">Fique por dentro das últimas novidades e atualizações do sistema Maxtrade</p>
                
                <div class="search-container">
                    <form method="GET" class="search-form">
                        <input 
                            type="text" 
                            name="busca" 
                            class="search-input" 
                            placeholder="Buscar atualizações..." 
                            value="<?php echo htmlspecialchars($busca); ?>"
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
                <i class="fas fa-sync-alt"></i>
                <span><?php echo $total_noticias; ?> atualização(ões) encontrada(s)</span>
                <?php if (!empty($busca)): ?>
                    <span>para "<?php echo htmlspecialchars($busca); ?>"</span>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($busca)): ?>
                <a href="blog-maxtrade.php" class="clear-search">
                    <i class="fas fa-times"></i> Limpar busca
                </a>
            <?php endif; ?>
        </div>

        <!-- Blog Grid -->
        <?php if ($noticias): ?>
            <div class="blog-grid">
                <?php foreach ($noticias as $noticia): ?>
                    <article class="blog-card">
                        <img 
                            src="<?php echo htmlspecialchars_decode($noticia['imagem']); ?>" 
                            alt="<?php echo htmlspecialchars_decode($noticia['titulo']); ?>"
                            class="blog-image"
                            loading="lazy"
                            onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIyMCIgdmlld0JveD0iMCAwIDQwMCAyMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSI0MDAiIGhlaWdodD0iMjIwIiBmaWxsPSIjRjdGQUZDIi8+CjxwYXRoIGQ9Ik0xNzUgODBIMjI1VjEzMEgxNzVWODBaIiBmaWxsPSIjRTJFOEYwIi8+CjxwYXRoIGQ9Ik0xOTAgMTQwSDIxMFYxNjBIMTkwVjE0MFoiIGZpbGw9IiNFMkU4RjAiLz4KPC9zdmc+'"
                        >
                        <div class="blog-content">
                            <div class="maxtrade-badge">
                                <i class="fas fa-cogs"></i> Maxtrade
                            </div>
                            
                            <div class="blog-date">
                                <i class="fas fa-calendar-alt"></i>
                                <?php echo date('d M Y', strtotime($noticia['data_publicacao'])); ?>
                            </div>
                            
                            <h2 class="blog-title">
                                <?php echo htmlspecialchars_decode($noticia['titulo']); ?>
                            </h2>
                            
                            <p class="blog-excerpt">
                                <?php echo strip_tags(htmlspecialchars_decode(substr($noticia['conteudo'], 0, 150))) . '...'; ?>
                            </p>
                            
                            <a href="blog_post.php?id=<?php echo $noticia['id']; ?>&from=blog-maxtrade" class="read-more-btn">
                                Leia mais
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h3>Nenhuma atualização encontrada</h3>
                <p>Tente ajustar sua busca ou navegue pelas atualizações mais recentes.</p>
                <?php if (!empty($busca)): ?>
                    <a href="blog-maxtrade.php" class="read-more-btn" style="margin-top: 1rem;">
                        Ver todas as atualizações
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($total_paginas > 1): ?>
            <div class="pagination-container">
                <div class="pagination-custom">
                    <!-- Botão Voltar -->
                    <a href="index.php" class="page-btn back-btn">
                        <i class="fas fa-home"></i>
                    </a>

                    <!-- Botão Anterior -->
                    <a href="?pagina=<?php echo max(1, $pagina_atual - 1); ?><?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?>" 
                       class="page-btn <?php echo $pagina_atual <= 1 ? 'disabled' : ''; ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>

                    <!-- Números das páginas -->
                    <?php 
                    $inicio = max(1, $pagina_atual - 2);
                    $fim = min($total_paginas, $pagina_atual + 2);
                    
                    for ($i = $inicio; $i <= $fim; $i++): 
                    ?>
                        <a href="?pagina=<?php echo $i; ?><?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?>" 
                           class="page-btn <?php echo $i == $pagina_atual ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <!-- Botão Próxima -->
                    <a href="?pagina=<?php echo min($total_paginas, $pagina_atual + 1); ?><?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?>" 
                       class="page-btn <?php echo $pagina_atual >= $total_paginas ? 'disabled' : ''; ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Lazy loading para imagens
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src || img.src;
                        img.classList.remove('loading-skeleton');
                        observer.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[loading="lazy"]').forEach(img => {
                img.classList.add('loading-skeleton');
                imageObserver.observe(img);
            });
        }

        // Smooth scroll para links internos
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

        // Adicionar efeito de hover nos cards
        document.querySelectorAll('.blog-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Auto-focus no campo de busca quando a página carrega
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('.search-input');
            if (searchInput && !searchInput.value) {
                searchInput.focus();
            }
        });

        // Adicionar animação de entrada para os cards
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const cardObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.blog-card').forEach(card => {
            cardObserver.observe(card);
        });
    </script>
</body>
</html>
