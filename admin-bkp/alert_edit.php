<?php
session_start();
include 'check_access.php'; 
require_once 'config.php';

if (!isset($_GET['id'])) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>ID do alerta não especificado.</div></div>";
    exit;
}

$id = $_GET['id'];

$sql = "SELECT * FROM alerts WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$alert = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$alert) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Alerta não encontrado.</div></div>";
    exit;
}

$upload_dir = "../uploads_alertas/";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $message = $_POST['message'] ?? '';
    $display_order = $_POST['display_order'] ?? 0;
    $status = $_POST['status'] ?? 'ativo';
    
    // Processa a imagem, se um novo arquivo for enviado
    $image = $alert['image'];
    if (!empty($_FILES['image']['name'])) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $newImageName = uniqid('img_', true) . '.' . $ext;
        $target_image = $upload_dir . $newImageName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_image)) {
            // Apaga a imagem antiga, se existir
            if (!empty($alert['image']) && file_exists($upload_dir . $alert['image'])) {
                unlink($upload_dir . $alert['image']);
            }
            $image = $newImageName;
        } else {
            echo "<div class='container mt-3'><div class='alert alert-danger'>Erro no upload da nova imagem.</div></div>";
        }
    }
    
    // Processa o arquivo, se um novo arquivo for enviado
    $file_path = $alert['file_path'];
    if (!empty($_FILES['file']['name'])) {
        $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid('doc_', true) . '.' . $ext;
        $target_file = $upload_dir . $newFileName;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
            // Apaga o arquivo antigo, se existir
            if (!empty($alert['file_path']) && file_exists($upload_dir . $alert['file_path'])) {
                unlink($upload_dir . $alert['file_path']);
            }
            $file_path = $newFileName;
        } else {
            echo "<div class='container mt-3'><div class='alert alert-danger'>Erro no upload do novo arquivo.</div></div>";
        }
    }
    
    $sqlUpdate = "UPDATE alerts 
                  SET title = :title, message = :message, image = :image, file_path = :file_path, 
                      display_order = :display_order, status = :status 
                  WHERE id = :id";
    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->execute([
       ':title' => $title,
       ':message' => $message,
       ':image' => $image,
       ':file_path' => $file_path,
       ':display_order' => $display_order,
       ':status' => $status,
       ':id' => $id
    ]);
    header("Location: alerts_admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Editar Alerta</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f5f5f5; padding-top: 40px; }
    .container { max-width: 600px; }
    .page-title { text-align: center; margin-bottom: 30px; font-weight: bold; color: #333; }
    .card { border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 30px; }
    .img-preview { max-width: 200px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px; }
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
  <h1 class="page-title">Editar Alerta</h1>
  <div class="card">
    <div class="card-header bg-primary text-white">
      Editar Alerta
    </div>
    <div class="card-body">
      <form method="POST" action="" enctype="multipart/form-data">
        <div class="mb-3">
          <label for="title" class="form-label">Título</label>
          <input type="text" name="title" id="title" class="form-control" required value="<?php echo htmlspecialchars($alert['title']); ?>">
        </div>
        <div class="mb-3">
          <label for="message" class="form-label">Mensagem</label>
          <textarea name="message" id="message" class="form-control" rows="3"><?php echo htmlspecialchars($alert['message']); ?></textarea>
        </div>
        <div class="mb-3">
          <label for="image" class="form-label">Imagem Ilustrativa (opcional)</label>
          <?php if ($alert['image']): ?>
            <a href="../uploads_alertas/<?php echo htmlspecialchars($alert['image']); ?>" target="_blank">
              <img src="../uploads_alertas/<?php echo htmlspecialchars($alert['image']); ?>" alt="Imagem Atual" class="img-preview d-block">
            </a>
          <?php endif; ?>
          <input type="file" name="image" id="image" class="form-control" accept="image/*">
        </div>
        <div class="mb-3">
          <label for="file" class="form-label">Arquivo para Download (opcional)</label>
          <?php if ($alert['file_path']): ?>
            <a href="../uploads_alertas/<?php echo htmlspecialchars($alert['file_path']); ?>" target="_blank">
              <i class="bi bi-download"></i> Visualizar Arquivo
            </a>
          <?php endif; ?>
          <input type="file" name="file" id="file" class="form-control" accept="application/pdf, image/*">
        </div>
        <div class="mb-3">
          <label for="display_order" class="form-label">Ordem de Exibição</label>
          <input type="number" name="display_order" id="display_order" class="form-control" value="<?php echo $alert['display_order']; ?>">
          <small class="text-muted">Número menor = exibido primeiro</small>
        </div>
        <div class="mb-3">
          <label for="status" class="form-label">Status</label>
          <select name="status" id="status" class="form-select">
            <option value="ativo" <?php echo ($alert['status'] === 'ativo') ? 'selected' : ''; ?>>Ativo</option>
            <option value="inativo" <?php echo ($alert['status'] === 'inativo') ? 'selected' : ''; ?>>Inativo</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        <a href="alerts_admin.php" class="btn btn-secondary">Voltar</a>
      </form>
    </div>
  </div>
</div>
  
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
