<?php
include('admin/config.php');

// Consultar as últimas 4 notícias ativas
$query = "SELECT * FROM noticias WHERE status = 'ativo' ORDER BY data_publicacao DESC LIMIT 4";
$stmt = $pdo->prepare($query);
$stmt->execute();
$noticias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div style="text-align: center; max-width: 100%; margin-top: 1px;">
    <div class="row">
        <?php foreach ($noticias as $noticia): ?>
            <div class="col-lg-3 col-xl-3 mb-4">
                <div class="card shadow" style="border-radius: 10px; overflow: hidden; background-color: #f5f5f5; border: none; position: relative;">
                    <!-- Imagem da notícia -->
                    <div style="position: relative;">
                        <img src="<?php echo $noticia['imagem']; ?>" class="card-img-top" alt="Imagem notícia" 
                             style="object-fit: cover; width: 100%; height: 200px;">
                        
                        <!-- Parte inferior da imagem com sobreposição de 20% -->
                        <div style="position: absolute; bottom: 0; left: 0; right: 0; height: 40%; background: rgba(255, 255, 255, 0.85); display: flex; align-items: center; padding: 10px;">
                            <?php
                                // Limitar o título para mostrar na sobreposição
                                $titulo = strlen($noticia['titulo']) > 40 ? substr($noticia['titulo'], 0, 40) . '...' : $noticia['titulo'];
                            ?>
                            <h5 class="card-title m-0" style="color: #333; font-weight: 600;"><?php echo htmlspecialchars($titulo); ?></h5>
                        </div>
                    </div>
                    
                    <!-- Quadro de Descrição abaixo da imagem -->
                    <div class="card-body" style="padding: 20px; display: flex; flex-direction: column; justify-content: space-between; min-height: 180px;">
                        <?php
                            // Limitar a descrição, remover tags HTML e decodificar entidades
                            $descricao = strip_tags($noticia['descricao']);
                            $descricao = html_entity_decode($descricao, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                            $descricao = strlen($descricao) > 120 ? substr($descricao, 0, 120) . '...' : $descricao;
                        ?>
                        <p class="card-text" style="color: #555; font-size: 14px;"><?php echo htmlspecialchars($descricao); ?></p>
                        <!-- Botão de 'Leia mais' -->
                        <a href="blog_post.php?id=<?php echo $noticia['id']; ?>" class="btn btn-outline-dark btn-sm mt-auto">Leia mais</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Botão Blog para ver todos os posts -->
    <div style="margin-top: 1px;">
        <a href="blog.php" class="btn btn-dark">Ver Blog - TI Grupo Barão</a>
    </div>
</div>