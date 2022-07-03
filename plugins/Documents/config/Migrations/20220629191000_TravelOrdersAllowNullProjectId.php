<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class TravelOrdersAllowNullProjectId extends AbstractMigration
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

        $table = $this->table('travel_orders');
        $table
            ->changeColumn('project_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->save();
    }
}
