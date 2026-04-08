<?php
// Test script for hex extraction
$testColors = ['Preto', 'Branco', 'Azul', '', null, '#ff0000', '1e293b'];

$legacyColorMap = [
    'Preto' => '#1e293b',
    'black' => '#1e293b',
    'Azul' => '#206bc4',
    'blue' => '#206bc4',
    'Azul Claro' => '#4299e1',
    'Verde' => '#2fb344',
    'green' => '#2fb344',
    'Vermelho' => '#dc3545',
    'red' => '#dc3545',
    'Amarelo' => '#f59f00',
    'yellow' => '#f59f00',
    'Laranja' => '#fd7e14',
    'orange' => '#fd7e14',
    'Roxo' => '#6f42c1',
    'purple' => '#6f42c1',
    'Rosa' => '#d63384',
    'pink' => '#d63384',
    'Cinza' => '#6c757d',
    'gray' => '#6c757d',
    'Branco' => '#ffffff',
    'white' => '#ffffff'
];

foreach ($testColors as $cor) {
    $corValue = isset($legacyColorMap[$cor]) ? $legacyColorMap[$cor] : $cor;
    $corHex = $corValue ?: '#206bc4'; 

    $hexToClean = ltrim($corHex, '#');
    $hex = preg_replace('/[^0-9a-fA-F]/', '', $hexToClean);
    if (strlen($hex) == 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    } elseif (strlen($hex) != 6) {
        $hex = '1e293b'; 
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
    $textColor = ($yiq >= 150) ? '#1e293b' : '#ffffff';
    $finalBackgroundColor = '#' . $hex;
    
    echo "Input: '$cor' -> Output BG: $finalBackgroundColor, Text: $textColor, YIQ: $yiq\n";
}
?>
