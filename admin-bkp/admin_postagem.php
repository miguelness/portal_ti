<?php
$requiredAccesses = ['Feeds TI', 'Feeds RH'];
require_once 'check_access.php';

$pageTitle = 'Gerenciar Posts';

// Paginação: define número de itens por página
$limite = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina - 1) * $limite;

// Consulta as notícias
if (in_array('Feeds TI', $user_accesses) && in_array('Feeds RH', $user_accesses)) {
    // Tem ambos os acessos: mostra tudo
    $query = "SELECT * FROM noticias ORDER BY data_publicacao DESC LIMIT :limite OFFSET :offset";
} elseif (in_array('Feeds TI', $user_accesses)) {
    // Só Feeds TI
    $query = "SELECT * FROM noticias WHERE categoria != 'RH' ORDER BY data_publicacao DESC LIMIT :limite OFFSET :offset";
} elseif (in_array('Feeds RH', $user_accesses)) {
    // Só Feeds RH
    $query = "SELECT * FROM noticias WHERE categoria = 'RH' ORDER BY data_publicacao DESC LIMIT :limite OFFSET :offset";
} else {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Acesso não permitido.</div></div>";
    exit;
}
$stmt = $pdo->prepare($query);
$stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$noticias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Consulta o total de notícias para paginação
$total_query = "SELECT COUNT(*) FROM noticias";
$total_stmt = $pdo->prepare($total_query);
$total_stmt->execute();
$total_noticias = $total_stmt->fetchColumn();

$total_paginas = ceil($total_noticias / $limite);
$max_links = 8;
$start = max(1, $pagina - intval($max_links / 2));
$end = min($total_paginas, $start + $max_links - 1);
if ($end - $start < $max_links - 1) {
    $start = max(1, $end - $max_links + 1);
}
ob_start(); ?>

<!-- Page header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <i class="ti ti-rss me-2"></i>Gerenciar Posts
                </h2>
                <div class="text-muted mt-1">Administração de postagens do portal</div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="noticia_adicionar.php" class="btn btn-primary d-none d-sm-inline-block">
                        <i class="ti ti-plus"></i>
                        Nova Postagem
                    </a>
                    <a href="noticia_adicionar.php" class="btn btn-primary d-sm-none btn-icon">
                        <i class="ti ti-plus"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Page body -->
<div class="page-body">
    <div class="container-xl">
        <div class="row row-deck row-cards">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Lista de Posts</h3>
                    </div>
                    <div class="table-responsive">
                        <table id="tabelaNoticias" class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th class="w-1">ID</th>
                                    <th>Título</th>
                                    <th>Categoria</th>
                                    <th>Data</th>
                                    <th>Status</th>
                                    <th class="w-1">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($noticias as $noticia): ?>
                                    <?php 
                                    // Badge para o status
                                    $status = $noticia['status'];
                                    $badgeStatus = ($status === 'ativo')
                                        ? '<span class="badge bg-success">Ativo</span>'
                                        : '<span class="badge bg-danger">Inativo</span>';

                                    // Badge para a categoria
                                    $categoria = $noticia['categoria'];
                                    switch ($categoria) {
                                        case 'Maxtrade':
                                            $badgeCategoria = '<span class="badge bg-blue">Maxtrade</span>';
                                            break;
                                        case 'Portal':
                                            $badgeCategoria = '<span class="badge bg-cyan">Portal</span>';
                                            break;
                                        case 'RH':
                                            $badgeCategoria = '<span class="badge bg-green">RH</span>';
                                            break;
                                        default:
                                            $badgeCategoria = '<span class="badge bg-secondary">Sem Categoria</span>';
                                    }
                                    ?>
                                    <tr id="post-<?= $noticia['id'] ?>">
                                        <td class="text-muted"><?= $noticia['id'] ?></td>
                                        <td>
                                            <div class="d-flex py-1 align-items-center">
                                                <div class="flex-fill">
                                                    <div class="font-weight-medium">
                                                        <a href="http://ti.grupobarao.com.br/portal/blog_post.php?id=<?= $noticia['id'] ?>" 
                                                           target="_blank" class="text-reset">
                                                            <?= htmlspecialchars($noticia['titulo']) ?>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= $badgeCategoria ?></td>
                                        <td class="text-muted">
                                            <?= date('d/m/Y', strtotime($noticia['data_publicacao'])) ?>
                                        </td>
                                        <td>
                                            <a href="#" class="status-toggle" 
                                               data-id="<?= $noticia['id'] ?>" 
                                               data-status="<?= $noticia['status'] ?>">
                                                <?= $badgeStatus ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="btn-list flex-nowrap">
                                                <a href="editar_postagem.php?id=<?= $noticia['id'] ?>" 
                                                   class="btn btn-white btn-sm" title="Editar">
                                                    <i class="ti ti-edit"></i>
                                                </a>
                                                <a href="excluir_postagem.php?id=<?= $noticia['id'] ?>" 
                                                   class="btn btn-white btn-sm" title="Excluir"
                                                   onclick="return confirm('Tem certeza que deseja excluir esta postagem?');">
                                                    <i class="ti ti-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginação -->
                    <?php if ($total_paginas > 1): ?>
                    <div class="card-footer d-flex align-items-center">
                        <p class="m-0 text-muted">
                            Mostrando <span><?= ($pagina - 1) * $limite + 1 ?></span> 
                            a <span><?= min($pagina * $limite, $total_noticias) ?></span> 
                            de <span><?= $total_noticias ?></span> entradas
                        </p>
                        <ul class="pagination m-0 ms-auto">
                            <?php if ($pagina > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?pagina=<?= $pagina - 1 ?>">
                                    <i class="ti ti-chevron-left"></i>
                                    Anterior
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php for ($i = $start; $i <= $end; $i++): ?>
                            <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                                <a class="page-link" href="?pagina=<?= $i ?>"><?= $i ?></a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if ($pagina < $total_paginas): ?>
                            <li class="page-item">
                                <a class="page-link" href="?pagina=<?= $pagina + 1 ?>">
                                    Próximo
                                    <i class="ti ti-chevron-right"></i>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();

// JavaScript adicional para funcionalidade de toggle de status
$extraJS = '
<script>
$(document).ready(function() {
    // Inicializa DataTable
    $("#tabelaNoticias").DataTable({
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json"
        },
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
        order: [[0, "desc"]],
        columnDefs: [
            { orderable: false, targets: [5] }
        ]
    });
    
    // Evento para alternar status
    $(".status-toggle").on("click", function(e) {
        e.preventDefault();
        var element = $(this);
        var id = element.data("id");
        var currentStatus = element.data("status");
        var newStatus = (currentStatus === "ativo") ? "inativo" : "ativo";
        
        $.ajax({
            url: "noticia_alterar_status.php",
            type: "GET",
            data: { id: id, status: newStatus },
            success: function(response) {
                if(response.trim() === "success") {
                    element.data("status", newStatus);
                    if(newStatus === "ativo") {
                        element.html(\'<span class="badge bg-success">Ativo</span>\');
                    } else {
                        element.html(\'<span class="badge bg-danger">Inativo</span>\');
                    }
                } else {
                    alert("Erro ao atualizar o status.");
                }
            },
            error: function() {
                alert("Erro na requisição.");
            }
        });
    });
});
</script>
';

include 'admin_layout.php';
