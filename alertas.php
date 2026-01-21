<?php
session_start();

// Se o portal exigir login para visualizar alertas, descomente:
// include 'login.php';

include_once 'admin/config.php';

// Busca todos os alertas (ativos e inativos) ordenados por data de criação (mais recentes primeiro)
$sql = "SELECT * 
        FROM alerts 
        ORDER BY created_at DESC";
$stmt = $pdo->query($sql);
$alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Histórico de Alertas</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Seus estilos personalizados -->
  <link href="assets/css/estilo.css" rel="stylesheet">

  <style>
    .alert-card {
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      margin-bottom: 1.5rem;
    }
    .alert-card .card-header {
      background-color: #f8f9fa;
      font-weight: 600;
    }
    .alert-date {
      font-size: 0.85rem;
      color: #6c757d;
    }
    .alert-image {
      max-width: 100%;
      border-radius: 4px;
      margin-bottom: 0.75rem;
    }
    .alert-download a {
      text-decoration: none;
    }
  </style>
</head>
<body>
  <!-- Se tiver um menu comum, inclua-o aqui -->
  <?php /* include 'menu.php'; */ ?>

  <div class="container my-5">
    <h2 class="text-center mb-4">Histórico de Alertas</h2>

    <?php if (empty($alerts)): ?>
      <p class="text-center">Nenhum alerta encontrado.</p>
    <?php else: ?>
      <?php foreach ($alerts as $alert): ?>
        <div class="card alert-card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span><?php echo htmlspecialchars($alert['title']); ?></span>
            <small class="alert-date">
              <?php 
                $dt = new DateTime($alert['created_at']);
                echo $dt->format('d/m/Y H:i');
              ?>
            </small>
          </div>
          <div class="card-body">
            <p class="mb-3"><?php echo nl2br(htmlspecialchars($alert['message'])); ?></p>

            <?php if (!empty($alert['image'])): ?>
              <img 
                src="uploads_alertas/<?php echo htmlspecialchars($alert['image']); ?>" 
                alt="Imagem do alerta" 
                class="alert-image img-fluid"
              >
            <?php endif; ?>

            <?php if (!empty($alert['file_path'])): ?>
              <div class="alert-download">
                <a href="uploads_alertas/<?php echo htmlspecialchars($alert['file_path']); ?>" class="btn btn-sm btn-outline-primary" download>
                  <i class="bi bi-download"></i> Baixar anexo
                </a>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Bootstrap 5 JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
