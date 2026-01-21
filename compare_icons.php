<?php
// Conectar à base de dados
include 'admin/config.php';

// Ícones disponíveis no mapeamento SVG
$iconMap = [
    'ti ti-currency-real',
    'ti ti-device-desktop',
    'ti ti-device-laptop',
    'ti ti-link',
    'ti ti-mail',
    'ti ti-message',
    'ti ti-refresh',
    'ti ti-settings',
    'ti ti-shield',
    'ti ti-tool',
    'ti ti-truck',
    'ti ti-user',
    'ti ti-users',
    'ti ti-currency-dollar',
    'ti ti-credit-card',
    'fa fa-film'
];

// Buscar ícones na base de dados
$sql = "SELECT DISTINCT icone FROM menu_links WHERE icone IS NOT NULL AND icone != ''";
$stmt = $pdo->prepare($sql);
$stmt->execute();

$dbIcons = [];
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $dbIcons[] = $row['icone'];
}

echo "=== COMPARAÇÃO DE ÍCONES ===\n\n";

echo "Ícones na base de dados:\n";
foreach ($dbIcons as $icon) {
    echo "- $icon\n";
}

echo "\nÍcones disponíveis no mapeamento SVG:\n";
foreach ($iconMap as $icon) {
    echo "- $icon\n";
}

echo "\n=== ANÁLISE ===\n\n";

// Ícones na BD que não têm SVG
$missingInSvg = array_diff($dbIcons, $iconMap);
echo "Ícones na BD que NÃO têm mapeamento SVG (causam fallback para emoji):\n";
if (empty($missingInSvg)) {
    echo "- Nenhum\n";
} else {
    foreach ($missingInSvg as $icon) {
        echo "- $icon\n";
    }
}

// Ícones SVG que não estão na BD
$missingInDb = array_diff($iconMap, $dbIcons);
echo "\nÍcones SVG que não estão sendo usados na BD:\n";
if (empty($missingInDb)) {
    echo "- Nenhum\n";
} else {
    foreach ($missingInDb as $icon) {
        echo "- $icon\n";
    }
}

// Conexão PDO será fechada automaticamente
?>