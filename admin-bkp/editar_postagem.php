<?php
// editar_postagem.php
session_start();
$requiredAccess = 'Feeds RH';
include 'check_access.php';
require 'config.php';

if (!isset($_GET['id'])) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>ID da postagem não especificado.</div></div>";
    exit;
}
$id = (int)$_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo     = $_POST['titulo']   ?? '';
    $conteudo   = $_POST['conteudo'] ?? '';
    $categoria  = $_POST['categoria']?? '';
    $status     = $_POST['status']   ?? '';
    $dataPub    = date('Y-m-d H:i:s');
    $imagem_url = '';

    // busca URL antiga
    $stmtOld = $pdo->prepare("SELECT imagem FROM noticias WHERE id = :id");
    $stmtOld->execute([':id' => $id]);
    $old = $stmtOld->fetch(PDO::FETCH_ASSOC);
    if ($old) {
        $imagem_url = $old['imagem'];
    }

    // upload de nova imagem
    if (!empty($_FILES['imagem']['name'])) {
        $ext       = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $novo_nome = uniqid('img_', true) . '.' . $ext;
        $destino   = '../uploads/' . $novo_nome;
        $bd_path   = 'uploads/' . $novo_nome;
        if (move_uploaded_file($_FILES['imagem']['tmp_name'], $destino)) {
            if ($imagem_url && file_exists('../'.$imagem_url)) {
                @unlink('../'.$imagem_url);
            }
            $imagem_url = $bd_path;
        }
    }

    // gravação no banco
    $upd = $pdo->prepare("
        UPDATE noticias SET
          titulo = :t,
          conteudo = :c,
          imagem = :i,
          categoria = :cat,
          status = :s,
          data_publicacao = :d
        WHERE id = :id
    ");
    $upd->execute([
        ':t'   => $titulo,
        ':c'   => $conteudo,
        ':i'   => $imagem_url,
        ':cat' => $categoria,
        ':s'   => $status,
        ':d'   => $dataPub,
        ':id'  => $id
    ]);

    $_SESSION['flash_msg'] = 'Postagem atualizada com sucesso!';
    header("Location: editar_postagem.php?id=$id");
    exit;
}

// GET: busca dados atuais
$stmt = $pdo->prepare("SELECT * FROM noticias WHERE id = :id");
$stmt->execute([':id' => $id]);
$postagem = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$postagem) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Postagem não encontrada.</div></div>";
    exit;
}

$flash = $_SESSION['flash_msg'] ?? '';
unset($_SESSION['flash_msg']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Editar Postagem</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- TinyMCE -->
  <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
  <script src="https://cdn.tiny.cloud/1/vug13kx9uqadf7chf6kqz4wpxja6senvj4h3anvnw56cj67z/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function(){
      tinymce.init({
        selector: '#conteudo',
        plugins: 'link image code',
        toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright | bullist numlist | link | code',
        height: 300,
        menubar: false,
        branding: false
      });
    });
  </script>

  <style>
    body { background: #f5f5f5; padding-top: 30px; }
    .page-title { text-align: center; margin-bottom: 30px; font-weight: 700; color: #333; }
    .card { border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.1); margin-bottom: 30px; }
    .card-header { background: #007bff; color: #fff; border-radius: 10px 10px 0 0; font-weight: 700; }
    .img-preview { max-width: 200px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px; }
  </style>
</head>
<body>
  <?php include 'menu.php'; ?>

  <?php if ($flash): ?>
    <div class="container">
      <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
    </div>
  <?php endif; ?>

  <div class="container">
    <h1 class="page-title">Editar Postagem</h1>
    <div class="card">
      <div class="card-header">Formulário de Edição</div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
          <div class="mb-3">
            <label for="titulo" class="form-label">Título</label>
            <input type="text" id="titulo" name="titulo" class="form-control"
                   value="<?= htmlspecialchars($postagem['titulo']) ?>" required>
          </div>

          <div class="mb-3">
            <label for="imagem" class="form-label">Imagem</label><br>
            <?php if ($postagem['imagem']): ?>
              <img src="../<?= htmlspecialchars($postagem['imagem']) . '?' . time() ?>"
                   alt="Atual" class="img-preview">
            <?php endif; ?>
            <input type="file" id="imagem" name="imagem" class="form-control" accept="image/*">
          </div>

          <div class="mb-3">
            <label for="conteudo" class="form-label">Descrição</label>
            <textarea id="conteudo" name="conteudo" class="form-control" rows="10"><?= htmlspecialchars($postagem['conteudo']) ?></textarea>
          </div>

          <select id="categoria" name="categoria" class="form-select" required>
            <?php foreach ($categorias_disponiveis as $cat): ?>
              <option value="<?= $cat ?>" <?= $postagem['categoria'] === $cat ? 'selected' : '' ?>><?= $cat ?></option>
            <?php endforeach; ?>
          </select>

          <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select id="status" name="status" class="form-select">
              <option value="ativo"   <?= $postagem['status'] === 'ativo'   ? 'selected' : '' ?>>Ativo</option>
              <option value="inativo" <?= $postagem['status'] === 'inativo' ? 'selected' : '' ?>>Inativo</option>
            </select>
          </div>

          <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
