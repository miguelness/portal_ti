<?php
include('config.php'); // Conexão com o banco de dados e configurações

$upload_dir = '../uploads/'; // Diretório de uploads
$view_dir = 'uploads/'; // Diretório de uploads

// Processa o formulário de adição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'] ?? '';
    $conteudo = $_POST['conteudo'] ?? '';
    $categoria = $_POST['categoria'] ?? ''; // Novo campo categoria
    $status = $_POST['status'] ?? 'ativo';
    // Define a data de publicação para o horário atual
    $data_publicacao = date('Y-m-d H:i:s');
    
    $imagem_url = '';
    // Verifica se uma imagem foi enviada
    if (!empty($_FILES['imagem']['name'])) {
        $imagem_nome = basename($_FILES['imagem']['name']);
        $imagem_caminho = $upload_dir . $imagem_nome;
        $imagem_bd = $view_dir . $imagem_nome;
        
        if (move_uploaded_file($_FILES['imagem']['tmp_name'], $imagem_caminho)) {
            $imagem_url = $imagem_bd;
        } else {
            echo "<div class='container mt-3'><div class='alert alert-danger'>Erro ao fazer o upload da imagem.</div></div>";
        }
    }
    
    // Insere a nova postagem no banco de dados, incluindo o campo categoria
    $query = "INSERT INTO noticias (titulo, conteudo, categoria, imagem, status, data_publicacao) 
              VALUES (:titulo, :conteudo, :categoria, :imagem, :status, :data_publicacao)";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':titulo', $titulo);
    $stmt->bindParam(':conteudo', $conteudo);
    $stmt->bindParam(':categoria', $categoria);
    $stmt->bindParam(':imagem', $imagem_url);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':data_publicacao', $data_publicacao);
    
    if ($stmt->execute()) {
        echo "<div class='container mt-3'><div class='alert alert-success'>Postagem adicionada com sucesso!</div></div>";
    } else {
        echo "<div class='container mt-3'><div class='alert alert-danger'>Erro ao adicionar a postagem.</div></div>";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Adicionar Postagem - TI Grupo Barão</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- TinyMCE (Editor WYSIWYG) -->
  <script src="https://cdn.tiny.cloud/1/vug13kx9uqadf7chf6kqz4wpxja6senvj4h3anvnw56cj67z/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
  <script>
    tinymce.init({
      selector: '#conteudo',
      plugins: 'link image code',
      toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | code',
      height: 300
    });
  </script>
  <style>
    body {
      background-color: #f5f5f5;
      padding-top: 30px;
    }
    .page-title {
      text-align: center;
      margin-bottom: 30px;
      font-family: sans-serif;
      font-weight: bold;
      color: #333;
    }
    .card {
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      margin-bottom: 30px;
    }
    .card-header {
      background-color: #007bff;
      color: #fff;
      border-radius: 10px 10px 0 0;
      font-weight: bold;
    }
    .card-body {
      border-radius: 0 0 10px 10px;
    }
    .form-label {
      font-weight: bold;
    }
    .img-preview {
      max-width: 200px;
      margin-bottom: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
    }
  </style>
</head>
<body>
<?php include('menu.php'); ?>
<div class="container">
  <h1 class="page-title">Adicionar Postagem</h1>
  <div class="card">
    <div class="card-header">
      Nova Postagem
    </div>
    <div class="card-body">
      <form action="" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
          <label for="titulo" class="form-label">Título</label>
          <input type="text" class="form-control" id="titulo" name="titulo" required>
        </div>
        
        <div class="mb-3">
          <label for="imagem" class="form-label">Imagem</label>
          <input type="file" class="form-control" id="imagem" name="imagem" accept="image/*">
        </div>
        
        <div class="mb-3">
          <label for="conteudo" class="form-label">Descrição</label>
          <textarea id="conteudo" name="conteudo" class="form-control" rows="10"></textarea>
        </div>
        
        <div class="mb-3">
          <label for="categoria" class="form-label">Categoria</label>
          <select id="categoria" name="categoria" class="form-select" required>
            <?php foreach ($categorias_disponiveis as $cat): ?>
              <option value="<?= $cat ?>"
                <?= isset($postagem['categoria']) && $postagem['categoria'] === $cat ? 'selected' : '' ?>>
                <?= $cat ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        
        <div class="mb-3">
          <label for="status" class="form-label">Status</label>
          <select id="status" name="status" class="form-select">
            <option value="ativo" selected>Ativo</option>
            <option value="inativo">Inativo</option>
          </select>
        </div>
        
        <button type="submit" class="btn btn-primary">
          Adicionar Postagem
        </button>
      </form>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
