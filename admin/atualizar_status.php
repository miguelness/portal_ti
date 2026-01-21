<?php
include('config.php'); // Inclui a conexão com o banco de dados

// Verifica se os parâmetros 'id' e 'status' foram enviados via GET
if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = $_GET['id'];
    $status = $_GET['status'];  // Pode ser 'ativo' ou 'inativo'

    try {
        // Atualiza o status da postagem no banco de dados
        $updateQuery = "UPDATE noticias SET status = :status WHERE id = :id";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->bindParam(':status', $status);
        $updateStmt->bindParam(':id', $id);
        $updateStmt->execute();

        // Se a atualização foi bem-sucedida, retorna 'success'
        if ($updateStmt->rowCount() > 0) {
            echo 'success';
        } else {
            echo 'error';
        }
    } catch (PDOException $e) {
        echo 'error: ' . $e->getMessage();
    }
}
?>
