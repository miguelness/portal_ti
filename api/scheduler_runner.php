<?php
/**
 * api/scheduler_runner.php
 * Runner central que executa os scripts agendados.
 */

require_once __DIR__ . '/../admin/config.php';

// Segurança: verificar token via GET ou ambiente
$token = $_GET['token'] ?? '';
$envToken = $_ENV['CRON_TOKEN'] ?? 'default_if_not_set';

if ($token !== $envToken && php_sapi_name() !== 'cli') {
    logCron("ERRO: Tentativa de acesso com token inválido: '$token'");
    header('HTTP/1.1 403 Forbidden');
    die('Acesso negado: Token inválido.');
}

$isCli = (php_sapi_name() === 'cli');
$nl = $isCli ? "\n" : "<br>";

echo "=== Web Cron Runner - Início: " . date('H:i:s') . " ==={$nl}";

// 1. Buscar agendamentos que precisam rodar
// Usamos NOW() com o fuso definido no config.php
$stmt = $pdo->query("
    SELECT * FROM sys_agendamentos 
    WHERE status = 'ativo' 
      AND (proxima_execucao IS NULL OR proxima_execucao <= NOW())
");
$agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($agendamentos)) {
    echo "Nenhuma tarefa pendente agora.{$nl}";
    exit;
}

foreach ($agendamentos as $task) {
    echo "Executando: {$task['nome']}... ";
    
    $startTime = microtime(true);
    
    // Atualiza o registro ANTES para evitar duplicidade
    $pdo->prepare("UPDATE sys_agendamentos SET ultima_execucao = NOW() WHERE id = ?")->execute([$task['id']]);

    // Executa a URL
    $url = $task['url_script'];
    
    // Se o portal estiver rodando em outra porta ou host, tentamos ajustar se for localhost bruto
    if (strpos($url, 'localhost') !== false && isset($_SERVER['HTTP_HOST'])) {
        $url = str_replace('localhost', $_SERVER['HTTP_HOST'], $url);
    }

    $response = "";
    $success = false;

    try {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) WebCron/1.0',
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $success = ($httpCode >= 200 && $httpCode < 400);
        if (!$success) {
            $response = "HTTP $httpCode - " . ($error ?: "Erro desconhecido");
        }
    } catch (Exception $e) {
        $response = "Exception: " . $e->getMessage();
    }

    $endTime = microtime(true);
    $diff = round($endTime - $startTime, 2);

    // Calcula próxima execução (Intervalo a partir de agora)
    $intervalo = (int)$task['intervalo_minutos'];
    $pdo->prepare("
        UPDATE sys_agendamentos 
        SET proxima_execucao = DATE_ADD(NOW(), INTERVAL ? MINUTE) 
        WHERE id = ?
    ")->execute([$intervalo, $task['id']]);

    if ($success) {
        echo "[OK] ({$diff}s){$nl}";
        logCron("SUCESSO: Task {$task['id']} ({$task['nome']}) em {$diff}s");
    } else {
        echo "[FALHA] {$response}{$nl}";
        logCron("FALHA: Task {$task['id']} ({$task['nome']}) - Erro: $response");
    }
}

echo "{$nl}Processamento finalizado.{$nl}";
