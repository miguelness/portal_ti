<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class CreateAgendamentosTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('sys_agendamentos');
        $table->addColumn('nome', 'string', ['limit' => 100])
              ->addColumn('url_script', 'string', ['limit' => 255])
              ->addColumn('intervalo_minutos', 'integer', ['default' => 5])
              ->addColumn('ultima_execucao', 'datetime', ['null' => true])
              ->addColumn('proxima_execucao', 'datetime', ['null' => true])
              ->addColumn('status', 'enum', ['values' => ['ativo', 'inativo'], 'default' => 'ativo'])
              ->addColumn('criado_em', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->create();

        // Inserir o primeiro agendamento padrão (Verificação de Servidores)
        $this->execute("
            INSERT INTO sys_agendamentos (nome, url_script, intervalo_minutos, status) 
            VALUES ('Verificação de Status dos Servidores', 'http://localhost/portal/api/check_servidores_status.php', 5, 'ativo')
        ");
    }
}
