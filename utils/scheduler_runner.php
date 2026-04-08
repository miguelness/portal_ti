<?php
/**
 * utils/scheduler_runner.php
 * Motor do Agendador de Tarefas Interno (Web Cron)
 */

require_once __DIR__ . '/../admin/config.php';

function runScheduler($pdo) {
    $agora = new DateTime();
    $agoraStr = $agora->format('Y-m-d H:i:s');

    // Busca tarefas ativas que precisam rodar (proxima_execucao <= agora ou nula)
    $stmt = $pdo->prepare("SELECT * FROM scheduler_tasks WHERE status = 'ativo' AND (proxima_execucao <= :agora OR proxima_execucao IS NULL)");
    $stmt->execute([':agora' => $agoraStr]);
    $tarefas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tarefas as $tarefa) {
        $id = $tarefa['id'];
        $script = __DIR__ . '/../' . $tarefa['script_path'];

        if (file_exists($script)) {
            // Executa o script capturando o output
            ob_start();
            try {
                include $script;
                $output = ob_get_clean();
                $status_log = "Sucesso: " . substr($output, 0, 500);
            } catch (Exception $e) {
                ob_end_clean();
                $status_log = "Erro: " . $e->getMessage();
            }

            // Calcula próxima execução
            $proxima = new DateTime();
            $proxima->add(new DateInterval('PT' . $tarefa['intervalo_minutos'] . 'M'));
            $proximaStr = $proxima->format('Y-m-d H:i:s');

            // Atualiza a tarefa
            $update = $pdo->prepare("UPDATE scheduler_tasks SET ultima_execucao = :ultima, proxima_execucao = :proxima, last_log = :log WHERE id = :id");
            $update->execute([
                ':ultima' => $agoraStr,
                ':proxima' => $proximaStr,
                ':log' => $status_log,
                ':id' => $id
            ]);
        }
    }
}
