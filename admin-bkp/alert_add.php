<?php
session_start();
include 'check_access.php'; 
require_once 'config.php';

// Diretório de upload (na raiz): o caminho relativo, saindo de admin
$upload_dir = "../uploads_alertas/";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $message = $_POST['message'] ?? '';
    $display_order = $_POST['display_order'] ?? 0;
    $status = $_POST['status'] ?? 'ativo';
    
    // Processar imagem (opcional)
    if (!empty($_FILES['image']['name'])) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $newImageName = uniqid('img_', true) . '.' . $ext;
        $target_image = $upload_dir . $newImageName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_image)) {
            $image = $newImageName;
        } else {
            $error = "Erro no upload da imagem.";
        }
    } else {
        $image = null;
    }
    
    // Processar arquivo (opcional)
    if (!empty($_FILES['file']['name'])) {
        $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid('doc_', true) . '.' . $ext;
        $target_file = $upload_dir . $newFileName;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
            $file_path = $newFileName;
        } else {
            $error = "Erro no upload do arquivo.";
        }
    } else {
        $file_path = null;
    }

    if (!isset($error)) {
        $sql = "INSERT INTO alerts (title, message, image, file_path, display_order, status)
                VALUES (:title, :message, :image, :file_path, :display_order, :status)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':title' => $title,
            ':message' => $message,
            ':image' => $image,
            ':file_path' => $file_path,
            ':display_order' => $display_order,
            ':status' => $status
        ]);
        header("Location: alerts_admin.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Adicionar Alerta</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
    body { background: #f5f5f5; padding-top: 40px; }
    .container { max-width: 600px; }
    .page-title { text-align: center; margin-bottom: 30px; font-weight: bold; }
    .card { border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 30px; }
  </style>
  <!-- TinyMCE (Editor WYSIWYG) -->
<script src="https://cdn.tiny.cloud/1/vug13kx9uqadf7chf6kqz4wpxja6senvj4h3anvnw56cj67z/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({
    selector: '#message',
    plugins: 'link image code lists',
    toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright | bullist numlist | link | code',
    height: 300,
    menubar: true,
    branding: false
  });
</script>
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
  <h1 class="page-title">Adicionar Alerta</h1>
  
  <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
  <?php endif; ?>
  
  <div class="card">
    <div class="card-header bg-success text-white">
      Novo Alerta
    </div>
    <div class="card-body">
      <form method="POST" action="" enctype="multipart/form-data">
        <div class="mb-3">
          <label for="title" class="form-label">Título</label>
          <input type="text" name="title" id="title" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="message" class="form-label">Mensagem</label>
          <textarea name="message" id="message" class="form-control" rows="3" placeholder="Digite o alerta (pode conter links)"></textarea>
        </div>
        <div class="mb-3">
          <label for="image" class="form-label">Imagem Ilustrativa (opcional)</label>
          <input type="file" name="image" id="image" class="form-control" accept="image/*">
        </div>
        <div class="mb-3">
          <label for="file" class="form-label">Arquivo para Download (opcional)</label>
          <input type="file" name="file" id="file" class="form-control" accept="application/pdf, image/*">
        </div>
        <div class="mb-3">
          <label for="display_order" class="form-label">Ordem de Exibição</label>
          <input type="number" name="display_order" id="display_order" class="form-control" value="0">
          <small class="text-muted">Número menor = exibido primeiro</small>
        </div>
        <div class="mb-3">
          <label for="status" class="form-label">Status</label>
          <select name="status" id="status" class="form-select">
            <option value="ativo">Ativo</option>
            <option value="inativo">Inativo</option>
          </select>
        </div>
        <button type="submit" class="btn btn-success">
          <i class="bi bi-check-lg"></i> Salvar Alerta
        </button>
        <a href="alerts_admin.php" class="btn btn-secondary">Voltar</a>
      </form>
    </div>
  </div>
</div>
  
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
