<?php
// download_lideranca.php

// Autenticação HTTP Básica para acesso restrito
$auth_user = 'lider';
$auth_pass = 'barao';

if (
    !isset($_SERVER['PHP_AUTH_USER']) ||
    $_SERVER['PHP_AUTH_USER'] !== $auth_user ||
    !isset($_SERVER['PHP_AUTH_PW']) ||
    $_SERVER['PHP_AUTH_PW'] !== $auth_pass
) {
    header('WWW-Authenticate: Basic realm="Área Restrita - Liderança"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Acesso restrito';
    exit;
}

session_start();
include 'admin/config.php';

// Consulta todos os documentos
$sql = "SELECT * FROM rh_documents ORDER BY upload_date DESC";
$stmt = $pdo->query($sql);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Download - Liderança</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- DataTables CSS -->
  <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
    body { background: #f5f5f5; margin-top: 20px; }
    .container { max-width: 900px; }
    .page-title { text-align: center; margin-bottom: 30px; font-weight: bold; }
  </style>
</head>
<body>
<div class="container">
  <h1 class="page-title">Download de Documentos - Liderança</h1>
  <div class="table-responsive">
    <table id="tabelaDocsLider" class="table table-striped table-hover">
      <thead class="table-dark">
        <tr>
          <th class="text-center" style="width:5%;">ID</th>
          <th>Título</th>
          <th>Descrição</th>
          <th class="text-center">Tipo</th>
          <th class="text-center">Upload</th>
          <th class="text-center" style="width:15%;">Download</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($documents)): ?>
          <?php foreach ($documents as $doc): ?>
            <tr>
              <td class="text-center"><?php echo $doc['id']; ?></td>
              <td><?php echo htmlspecialchars($doc['title']); ?></td>
              <td><?php echo htmlspecialchars($doc['description']); ?></td>
              <td class="text-center">
                <?php 
                  echo ($doc['leadership_only'] == 1)
                    ? '<span class="badge bg-warning">Liderança</span>'
                    : '<span class="badge bg-info">RH</span>'; 
                ?>
              </td>
              <td class="text-center"><?php echo date('d/m/Y H:i', strtotime($doc['upload_date'])); ?></td>
              <td class="text-center">
                <a href="uploads_rh/<?php echo htmlspecialchars($doc['file_path']); ?>" class="btn btn-sm btn-primary" download>
                  <i class="bi bi-download"></i> Baixar
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" class="text-center text-muted">Nenhum documento disponível.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
  
<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery e DataTables JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function(){
  $('#tabelaDocsLider').DataTable({
    language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json' },
    pageLength: 10,
    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]]
  });
});
</script>
</body>
</html>
