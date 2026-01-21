<?php
require_once 'conexao.php';

echo "<h2>Debug dos Ícones - Menu Links</h2>";

try {
    $sql = "SELECT id, titulo, icone, cor FROM menu_links WHERE status='ativo' ORDER BY ordem ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $links = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
    echo "<tr><th>ID</th><th>Título</th><th>Ícone (Classe)</th><th>Cor</th><th>Tem Mapeamento?</th></tr>";
    
    // Mapeamento atual
    $iconMap = [
        'ti ti-apps' => 'SIM',
        'ti ti-home' => 'SIM',
        'ti ti-user' => 'SIM',
        'ti ti-users' => 'SIM',
        'ti ti-settings' => 'SIM',
        'ti ti-news' => 'SIM',
        'ti ti-building' => 'SIM',
        'ti ti-briefcase' => 'SIM',
        'ti ti-phone' => 'SIM',
        'ti ti-chart-bar' => 'SIM',
        'ti ti-file-text' => 'SIM'
    ];
    
    $iconesNaoMapeados = [];
    
    foreach ($links as $link) {
        $temMapeamento = isset($iconMap[$link['icone']]) ? 'SIM' : 'NÃO';
        if ($temMapeamento === 'NÃO') {
            $iconesNaoMapeados[] = $link['icone'];
        }
        
        echo "<tr style='background-color: " . ($temMapeamento === 'NÃO' ? '#ffcccc' : '#ccffcc') . ";'>";
        echo "<td>" . htmlspecialchars($link['id']) . "</td>";
        echo "<td>" . htmlspecialchars($link['titulo']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($link['icone']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($link['cor']) . "</td>";
        echo "<td><strong>" . $temMapeamento . "</strong></td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Lista de ícones não mapeados
    if (!empty($iconesNaoMapeados)) {
        echo "<h3 style='color: red;'>Ícones que precisam ser mapeados:</h3>";
        echo "<ul>";
        foreach (array_unique($iconesNaoMapeados) as $icone) {
            echo "<li><strong>" . htmlspecialchars($icone) . "</strong></li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>

<!-- Incluir Tabler Icons -->
<link href="https://cdn.jsdelivr.net/npm/@tabler/icons@2.44.0/tabler-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">

<style>
.ti {
    font-family: "tabler-icons" !important;
    font-style: normal !important;
    font-weight: normal !important;
    font-variant: normal !important;
    text-transform: none !important;
    line-height: 1 !important;
    -webkit-font-smoothing: antialiased !important;
    -moz-osx-font-smoothing: grayscale !important;
    display: inline-block !important;
}
</style>