<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['imagem'])) {
    $imagem = $_FILES['imagem'];
    
    // Caminho da pasta onde as imagens serão salvas
    $uploadDir = 'uploads/';
    $uploadFile = $uploadDir . basename($imagem['name']);
    
    // Mover o arquivo para a pasta uploads
    if (move_uploaded_file($imagem['tmp_name'], $uploadFile)) {
        echo "Imagem carregada com sucesso!";
    } else {
        echo "Erro ao carregar a imagem.";
    }
}
?>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="imagem">
    <button type="submit">Enviar Imagem</button>
</form>
