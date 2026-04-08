<?php
/**
 * api/check_servidores_status.php
 * Script para verificar o status dos servidores monitorados.
 */

require_once __DIR__ . '/../admin/config.php';

$isCli = (php_sapi_name() === 'cli');
$nl = $isCli ? "\n" : "<br>";

$stmt = $pdo->query("SELECT * FROM monitoramento_servidores WHERE verificar_estabilidade = 1 ORDER BY id ASC");
$servidores = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($servidores)) {
    echo "Nenhum servidor marcado para verificação.{$nl}";
    exit;
}

echo "=== Verificação de Status de Servidores ==={$nl}";
echo "Início: " . date('d/m/Y H:i:s') . "{$nl}";

$updateStmt = $pdo->prepare("
    UPDATE monitoramento_servidores 
    SET ultima_verificacao = NOW(), tempo_resposta_ms = :tempo, status = :status 
    WHERE id = :id
");

foreach ($servidores as $servidor) {
    $urlOuIp = $servidor['ip_ou_url'];
    $tempoBom = (int)$servidor['tempo_bom_ms'] ?: 1500;
    $tempoLento = (int)$servidor['tempo_lento_ms'] ?: 3500;
    $timeoutSec = 5;

    echo "Verificando: {$servidor['nome']} ({$urlOuIp})... ";

    $status = 'offline';
    $tempoMs = 0;
    $startTime = microtime(true);

    if (filter_var($urlOuIp, FILTER_VALIDATE_URL)) {
        // Teste como URL (HTTP/HTTPS)
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $urlOuIp,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_TIMEOUT => $timeoutSec,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_USERAGENT => 'Monitoramento-Portal/1.0',
        ]);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $endTime = microtime(true);
        $tempoMs = (int)(($endTime - $startTime) * 1000);
        
        if ($httpCode >= 200 && $httpCode < 400) {
           $status = ($tempoMs <= $tempoBom) ? 'online' : ($tempoMs <= $tempoLento ? 'lento' : 'lento');
        }
        curl_close($ch);
    } else {
        // Tentativa de socket connect (TCP) - port 80 default se não informado
        $host = $urlOuIp;
        $port = 80;
        if (strpos($urlOuIp, ':') !== false) {
           list($host, $port) = explode(':', $urlOuIp);
        }

        $connection = @fsockopen($host, $port, $errno, $errstr, $timeoutSec);
        $endTime = microtime(true);
        $tempoMs = (int)(($endTime - $startTime) * 1000);

        if ($connection) {
            $status = ($tempoMs <= $tempoBom) ? 'online' : ($tempoMs <= $tempoLento ? 'lento' : 'lento');
            fclose($connection);
        } else {
            // Se falhou TCP porta 80, tenta ver se responde ping (opcional, requer permissão)
            $status = 'offline';
        }
    }

    echo "Status: {$status} ({$tempoMs}ms){$nl}";

    $updateStmt->execute([
        ':tempo'  => $tempoMs,
        ':status' => $status,
        ':id'     => $servidor['id']
    ]);
}

echo "{$nl}Processamento concluído em " . date('d/m/Y H:i:s') . ".{$nl}";
