<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class AddLastLogToAgendamentos extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('sys_agendamentos');
        $table->addColumn('last_log', 'text', ['null' => true, 'after' => 'status'])
              ->update();
    }
}
