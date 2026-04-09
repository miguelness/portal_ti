<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class CreateSchedulerTables extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('scheduler_tasks');
        $table->addColumn('nome', 'string', ['limit' => 255])
              ->addColumn('script_path', 'string', ['limit' => 255])
              ->addColumn('intervalo_minutos', 'integer', ['default' => 5])
              ->addColumn('ultima_execucao', 'datetime', ['null' => true])
              ->addColumn('proxima_execucao', 'datetime', ['null' => true])
              ->addColumn('status', 'enum', ['values' => ['ativo', 'inativo'], 'default' => 'ativo'])
              ->addColumn('last_log', 'text', ['null' => true])
              ->addColumn('criado_em', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->create();

        // Insere a tarefa de monitoramento por padrão
        $this->execute("INSERT INTO scheduler_tasks (nome, script_path, intervalo_minutos) VALUES ('Monitoramento de Servidores', 'api/check_servidores_status.php', 5)");
    }
}
