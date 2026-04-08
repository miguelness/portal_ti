<?php
require_once 'c:/Xampp/htdocs/portal/conexao.php';

try {
    // Adiciona a coluna se ela não existir
    $check_col = $conn->query("SHOW COLUMNS FROM `menu_links` LIKE 'is_novidade'");
    if ($check_col->num_rows == 0) {
        $conn->query("ALTER TABLE `menu_links` ADD COLUMN `is_novidade` TINYINT(1) DEFAULT 0");
        echo "Coluna is_novidade adicionada com sucesso.\n";
    } else {
        echo "Coluna is_novidade ja existe.\n";
    }
} catch (Exception $e) {
    echo "Erro na gravacao: " . $e->getMessage();
}
