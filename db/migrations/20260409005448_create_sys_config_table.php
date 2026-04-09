<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSysConfigTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $table = $this->table('sys_config');
        $table->addColumn('chave', 'string', ['limit' => 50])
              ->addColumn('valor', 'text', ['null' => true])
              ->addColumn('descricao', 'string', ['limit' => 255, 'null' => true])
              ->addIndex(['chave'], ['unique' => true])
              ->create();

        // Inserir configuração do Heartbeat (Cron Interno)
        $this->execute("
            INSERT INTO sys_config (chave, valor, descricao) 
            VALUES ('web_cron_heartbeat', '1', 'Habilita o disparo automático de agendamentos via navegação no portal')
        ");
    }
}
