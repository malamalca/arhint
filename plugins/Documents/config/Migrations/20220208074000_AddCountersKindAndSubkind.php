<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddCountersKindAndSubkind extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change()
    {

        $table = $this->table('documents_counters');
        $table
            ->renameColumn('kind', 'direction')
            ->addColumn('kind', 'string', [
                'default' => 'invoice',
                'limit' => 15,
                'null' => false,
                'after' => 'owner_id',
            ])
            ->save();
    }
}
