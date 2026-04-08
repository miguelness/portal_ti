<?php
require 'c:/Xampp/htdocs/portal/admin/config.php';
$stmt = $pdo->query("SELECT titulo, tamanho, cor FROM menu_links WHERE titulo LIKE '%Mapeamento%'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
