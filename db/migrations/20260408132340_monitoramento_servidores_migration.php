<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class MonitoramentoServidoresMigration extends AbstractMigration
{
    public function change(): void
    {
        // Tabela principal de servidores
        $tableName = 'monitoramento_servidores';
        if (!$this->hasTable($tableName)) {
            $table = $this->table($tableName);
            $table->addColumn('nome', 'string', ['limit' => 100])
                  ->addColumn('ip_ou_url', 'string', ['limit' => 255])
                  ->addColumn('tipo', 'enum', ['values' => ['interno', 'externo'], 'default' => 'externo'])
                  ->addColumn('status', 'enum', ['values' => ['online', 'lento', 'offline', 'pendente'], 'default' => 'pendente'])
                  ->addColumn('tempo_resposta_ms', 'integer', ['default' => 0])
                  ->addColumn('ultima_verificacao', 'datetime', ['null' => true])
                  ->addColumn('verificar_estabilidade', 'boolean', ['default' => true])
                  ->addColumn('exibir_dashboard', 'boolean', ['default' => false])
                  ->addColumn('status_registro', 'enum', ['values' => ['ativo', 'inativo'], 'default' => 'ativo'])
                  ->addColumn('tempo_bom_ms', 'integer', ['default' => 1500])
                  ->addColumn('tempo_lento_ms', 'integer', ['default' => 3500])
                  ->addColumn('criado_em', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                  ->create();
        }

        // Tabela de Logs (Histórico)
        $logTable = 'monitoramento_logs';
        if (!$this->hasTable($logTable)) {
            $logs = $this->table($logTable);
            $logs->addColumn('servidor_id', 'integer')
                 ->addColumn('status', 'enum', ['values' => ['online', 'lento', 'offline']])
                 ->addColumn('tempo_ms', 'integer', ['default' => 0])
                 ->addColumn('verificado_em', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                 ->addIndex(['servidor_id'])
                 ->addIndex(['verificado_em'])
                 ->addForeignKey('servidor_id', 'monitoramento_servidores', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
                 ->create();
        }
    }
}
