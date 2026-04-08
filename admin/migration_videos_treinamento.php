<?php
require_once 'c:/Xampp/htdocs/portal/conexao.php';

try {
    // Tabela videos_treinamento
    $sql1 = "CREATE TABLE IF NOT EXISTS `videos_treinamento` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `titulo` varchar(255) NOT NULL,
        `descricao` text,
        `url_video` varchar(255) NOT NULL,
        `ordem` int(11) DEFAULT 0,
        `status` enum('ativo','inativo') DEFAULT 'ativo',
        `criado_em` datetime DEFAULT current_timestamp(),
        `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $conn->query($sql1);
    echo "Tabela videos_treinamento criada com sucesso.\n";

    // Tabela videos_anexos
    $sql2 = "CREATE TABLE IF NOT EXISTS `videos_anexos` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `video_id` int(11) NOT NULL,
        `nome_documento` varchar(255) NOT NULL,
        `caminho_arquivo` varchar(255) NOT NULL,
        `criado_em` datetime DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        FOREIGN KEY (`video_id`) REFERENCES `videos_treinamento`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $conn->query($sql2);
    echo "Tabela videos_anexos criada com sucesso.\n";

} catch (Exception $e) {
    echo "Erro na gravacao: " . $e->getMessage();
}
