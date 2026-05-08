<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddMessageGuidToTasks extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change()
    {
        $this->table('tasks')
            ->addColumn('message_guid', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
                'after' => 'foreign_id',
            ])
            ->update();
    }
}
