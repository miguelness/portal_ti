<?php
// Script para extrair todos os ícones Tabler do link_editar.php e verificar se estão no index.php

// Ler o conteúdo do link_editar.php para extrair o array $tablerIcons
$linkEditarContent = file_get_contents('admin/link_editar.php');

// Extrair o array $tablerIcons usando regex
preg_match('/\$tablerIcons\s*=\s*\[(.*?)\];/s', $linkEditarContent, $matches);
$tablerIcons = [];
if ($matches) {
    $arrayContent = $matches[1];
    // Extrair as chaves do array
    preg_match_all("/'([^']+)'\s*=>/", $arrayContent, $iconMatches);
    $tablerIcons = $iconMatches[1];
}

// Ícones encontrados diretamente no HTML do link_editar.php
$iconsInHtml = [
    'ti ti-edit',
    'ti ti-arrow-left', 
    'ti ti-info-circle',
    'ti ti-palette',
    'ti ti-settings',
    'ti ti-tag',
    'ti ti-file-text',
    'ti ti-link',
    'ti ti-world',
    'ti ti-external-link',
    'ti ti-layout',
    'ti ti-icons',
    'ti ti-sort-ascending',
    'ti ti-hierarchy',
    'ti ti-toggle-left',
    'ti ti-device-floppy',
    'ti ti-eye'
];

// Extrair ícones do array $tablerIcons
$iconsFromArray = $tablerIcons;

// Combinar todos os ícones
$allIcons = array_unique(array_merge($iconsInHtml, $iconsFromArray));
sort($allIcons);

echo "=== TODOS OS ÍCONES TABLER ENCONTRADOS NO LINK_EDITAR.PHP ===\n";
echo "Total de ícones únicos: " . count($allIcons) . "\n\n";

foreach ($allIcons as $icon) {
    echo "- $icon\n";
}

echo "\n=== VERIFICANDO MAPEAMENTO NO INDEX.PHP ===\n";

// Ler o conteúdo do index.php para verificar o mapeamento
$indexContent = file_get_contents('index.php');

// Extrair o array iconMap do index.php
preg_match('/\$iconMap\s*=\s*\[(.*?)\];/s', $indexContent, $matches);
if ($matches) {
    $iconMapContent = $matches[1];
    
    $missingIcons = [];
    $presentIcons = [];
    
    foreach ($allIcons as $icon) {
        if (strpos($iconMapContent, "'$icon'") !== false || strpos($iconMapContent, "\"$icon\"") !== false) {
            $presentIcons[] = $icon;
        } else {
            $missingIcons[] = $icon;
        }
    }
    
    echo "\nÍcones PRESENTES no mapeamento SVG (" . count($presentIcons) . "):\n";
    foreach ($presentIcons as $icon) {
        echo "✅ $icon\n";
    }
    
    echo "\nÍcones FALTANTES no mapeamento SVG (" . count($missingIcons) . "):\n";
    foreach ($missingIcons as $icon) {
        echo "❌ $icon\n";
    }
    
    if (count($missingIcons) > 0) {
        echo "\n⚠️  ATENÇÃO: " . count($missingIcons) . " ícones precisam ser adicionados ao mapeamento SVG!\n";
    } else {
        echo "\n✅ SUCESSO: Todos os ícones estão mapeados!\n";
    }
} else {
    echo "❌ Erro: Não foi possível encontrar o array iconMap no index.php\n";
}
?>