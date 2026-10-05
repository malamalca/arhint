<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RenameTravelOrdersFieldsDatOrderAndTask extends BaseMigration
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
            ->renameColumn('task', 'title')
            ->renameColumn('dat_order', 'dat_issue')
            ->save();
    }
}
