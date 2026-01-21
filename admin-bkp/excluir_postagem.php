<?php
include('config.php'); // Conecta ao banco de dados

// Verifica se o ID da postagem foi passado via GET
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Consulta para obter o caminho da imagem da postagem
    $query = "SELECT imagem FROM noticias WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $noticia = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($noticia) {
        // Caminho da imagem para exclusão
        $imagem = "../".$noticia['imagem'];
        
        // Verifica se a imagem existe no servidor e a exclui
        if (!empty($imagem) && file_exists($imagem)) {
            unlink($imagem); // Remove o arquivo de imagem
        }

        // Consulta para excluir a postagem
        $query = "DELETE FROM noticias WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo "<script>alert('Postagem excluída com sucesso!'); window.location.href='index.php';</script>";
        } else {
            echo "<script>alert('Erro ao excluir a postagem.'); window.location.href='index.php';</script>";
        }
    } else {
        echo "<script>alert('Postagem não encontrada.'); window.location.href='index.php';</script>";
    }
} else {
    header('Location: listar_postagens.php');
    exit();
}
