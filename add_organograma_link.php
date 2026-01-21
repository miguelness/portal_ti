<?php
require_once 'admin/config.php';

try {
    // Verificar se o link já existe
    $stmt = $pdo->prepare("SELECT id FROM menu_links WHERE titulo = 'Organograma'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // Inserir o link do organograma
        $stmt = $pdo->prepare("
            INSERT INTO menu_links (titulo, descricao, url, icone, cor, status, ordem, tamanho, target_blank) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            'Organograma',
            'Visualize a estrutura organizacional da empresa',
            'organograma.php',
            'ti ti-sitemap',
            '#0054a6', // Cor azul corporativa
            'ativo', // Status ativo
            999, // Ordem alta para aparecer por último
            'col-lg-3 col-md-6', // Tamanho padrão
            0 // Não abrir em nova aba
        ]);
        
        echo "<div style='padding: 20px; font-family: Arial, sans-serif;'>";
        echo "<h2 style='color: #0054a6;'>✅ Link do Organograma Adicionado</h2>";
        echo "<p>O link para o organograma público foi adicionado com sucesso à página inicial!</p>";
        echo "<p><strong>Detalhes:</strong></p>";
        echo "<ul>";
        echo "<li>Título: Organograma</li>";
        echo "<li>Descrição: Visualize a estrutura organizacional da empresa</li>";
        echo "<li>URL: organograma.php</li>";
        echo "<li>Ícone: ti ti-sitemap</li>";
        echo "<li>Cor: #0054a6 (azul corporativo)</li>";
        echo "</ul>";
        echo "<p><a href='index.php' style='background: #0054a6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ver Página Inicial</a></p>";
        echo "</div>";
    } else {
        echo "<div style='padding: 20px; font-family: Arial, sans-serif;'>";
        echo "<h2 style='color: #ffa500;'>⚠️ Link Já Existe</h2>";
        echo "<p>O link do organograma já existe na página inicial.</p>";
        echo "<p><a href='index.php' style='background: #0054a6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ver Página Inicial</a></p>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<div style='padding: 20px; font-family: Arial, sans-serif;'>";
    echo "<h2 style='color: #dc3545;'>❌ Erro</h2>";
    echo "<p>Erro ao adicionar o link: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>