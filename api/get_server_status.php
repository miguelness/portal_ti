<?php
/**
 * api/get_server_status.php
 * Retorna o status atual dos servidores e logs das últimas 24h em formato JSON.
 */
header('Content-Type: application/json');
require_once '../admin/config.php';

try {
    // 1. Buscar servidores ativos
    $stmt = $pdo->query("SELECT id, nome, tipo, status, tempo_resposta_ms FROM monitoramento_servidores WHERE verificar_estabilidade = 1 ORDER BY tipo DESC, nome ASC");
    $servidores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Buscar logs das últimas 24 horas para os gráficos
    $logs24h = [];
    $stmtLogs = $pdo->query("SELECT servidor_id, tempo_ms, verificado_em FROM monitoramento_logs WHERE verificado_em >= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY verificado_em ASC");
    while ($row = $stmtLogs->fetch(PDO::FETCH_ASSOC)) {
        $logs24h[$row['servidor_id']][] = [
            'x' => strtotime($row['verificado_em']) * 1000,
            'y' => (int)$row['tempo_ms']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'servidores' => $servidores,
            'logs' => $logs24h,
            'atualizado_em' => date('d/m/Y H:i:s')
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
