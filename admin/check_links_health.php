<?php
/**
 * admin/check_links_health.php
 * 
 * Script de verificação de estabilidade de links.
 * Pode ser executado manualmente ou via agendador de tarefas (cron/Task Scheduler).
 *
 * Uso via linha de comando: php check_links_health.php
 * Uso via navegador: http://localhost/portal/admin/check_links_health.php
 */

require_once __DIR__ . '/config.php';

$isCli = (php_sapi_name() === 'cli');
$nl = $isCli ? "\n" : "<br>";

// Busca links marcados para verificação que tenham URL
$stmt = $pdo->query("
    SELECT id, titulo, url, tempo_bom_ms, tempo_lento_ms 
    FROM menu_links 
    WHERE verificar_estabilidade = 1 AND url IS NOT NULL AND url != '' AND status = 'ativo'
    ORDER BY id ASC
");
$links = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($links)) {
    echo "Nenhum link marcado para verificacao de estabilidade.{$nl}";
    exit;
}

echo "=== Verificacao de Estabilidade de Links ==={$nl}";
echo "Inicio: " . date('d/m/Y H:i:s') . "{$nl}";
echo "Links para verificar: " . count($links) . "{$nl}{$nl}";

$updateStmt = $pdo->prepare("
    UPDATE menu_links 
    SET ultimo_check = NOW(), tempo_resposta_ms = :tempo, link_status = :status 
    WHERE id = :id
");

foreach ($links as $link) {
    $url = $link['url'];
    $tempoBom = (int)$link['tempo_bom_ms'] ?: 2000;
    $tempoLento = (int)$link['tempo_lento_ms'] ?: 5000;
    $timeoutSec = ceil($tempoLento / 1000) + 5; // Timeout = limite lento + 5s margem

    echo "Verificando: {$link['titulo']} ({$url})... ";

    $ch = curl_init();
    $options = [
        CURLOPT_URL            => $url,
        CURLOPT_NOBODY         => true,       // Tenta HEAD primeiro (mais rápido)
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => $timeoutSec,
        CURLOPT_CONNECTTIMEOUT => $timeoutSec,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        CURLOPT_PROXY          => "",                // Desabilita proxy (força conexão direta)
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    ];
    curl_setopt_array($ch, $options);

    $startTime = microtime(true);
    curl_exec($ch);
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_errno($ch);

    // Se falhou com erro de conexão (7), tenta o mais básico possível: HTTP 1.0 e sem cabeçalhos extras
    if ($curlError == 7 || $httpCode >= 400) {
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ch, CURLOPT_NOBODY, false); 
        // Removemos o Range aqui para evitar bloqueios de alguns proxies
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_errno($ch);
    }
    
    $endTime = microtime(true);
    $curlErrorMessage = curl_error($ch);
    curl_close($ch);

    $tempoMs = (int)(($endTime - $startTime) * 1000);

    // Determina o status
    $status = 'offline';
    $msgStatus = "";

    if ($curlError) {
        $status = 'offline';
        $msgStatus = "OFFLINE (cURL: {$curlError} - {$curlErrorMessage}, {$tempoMs}ms)";
    } elseif ($httpCode < 200 || $httpCode >= 400) {
        $status = 'offline';
        $msgStatus = "OFFLINE (HTTP: {$httpCode}, {$tempoMs}ms)";
    } elseif ($tempoMs <= $tempoBom) {
        $status = 'online';
        $msgStatus = "ONLINE ({$tempoMs}ms)";
    } else {
        $status = 'lento';
        $msgStatus = ($tempoMs <= $tempoLento) ? "LENTO ({$tempoMs}ms)" : "MUITO LENTO ({$tempoMs}ms)";
    }

    echo $msgStatus . $nl;

    // Salva o resultado
    $updateStmt->execute([
        ':tempo'  => $tempoMs,
        ':status' => $status,
        ':id'     => $link['id']
    ]);
}

echo "{$nl}Verificacao concluida em " . date('d/m/Y H:i:s') . ".{$nl}";
