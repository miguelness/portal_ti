<?php
echo "<h3>Informações de IP Detectadas pelo Servidor</h3>";
echo "<strong>IP Direto (REMOTE_ADDR):</strong> " . $_SERVER['REMOTE_ADDR'] . "<br>";

$headers = [
    'HTTP_CLIENT_IP',
    'HTTP_X_FORWARDED_FOR',
    'HTTP_X_FORWARDED',
    'HTTP_X_CLUSTER_CLIENT_IP',
    'HTTP_FORWARDED_FOR',
    'HTTP_FORWARDED',
    'HTTP_X_REAL_IP',
    'HTTP_CF_CONNECTING_IP',
    'HTTP_TRUE_CLIENT_IP'
];

echo "<h4>Outros cabeçalhos (usados por Proxies, Load Balancers, Cloudflare, VPNs, etc):</h4><ul>";
$found = false;
foreach ($headers as $header) {
    if (array_key_exists($header, $_SERVER) && !empty($_SERVER[$header])) {
        echo "<li><strong>$header:</strong> " . $_SERVER[$header] . "</li>";
        $found = true;
    }
}
if (!$found) {
    echo "<li><em>Nenhum outro cabeçalho de IP de proxy/VPN encontrado.</em></li>";
}
echo "</ul>";

echo "<h4>Como o Portal detecta agora:</h4>";
$visitorIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
if (strpos($visitorIp, ',') !== false) {
    $visitorIp = trim(explode(',', $visitorIp)[0]);
}
echo "<strong>IP Final Detectado:</strong> " . $visitorIp . "<br>";
?>
