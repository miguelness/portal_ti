<?php
require_once 'conexao.php';

$sql = "SELECT DISTINCT icone FROM menu_links WHERE status='ativo' ORDER BY icone ASC";
$result = $conn->query($sql);

if ($result) {
    echo "Ícones encontrados no banco de dados:\n\n";
    while ($row = $result->fetch_assoc()) {
        echo "'" . $row['icone'] . "'\n";
    }
} else {
    echo "Erro na consulta: " . $conn->error;
}

$conn->close();
?>