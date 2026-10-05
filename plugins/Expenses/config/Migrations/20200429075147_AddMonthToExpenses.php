<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddMonthToExpenses extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     *
     * @return void
     */
    public function change()
    {
        $table = $this->table('expenses');
        $table->addColumn('month', 'string', [
            'default' => null,
            'limit' => 7,
            'null' => true,
            'after' => 'dat_happened',
        ]);
        $table->update();

        $count = $this->execute('UPDATE expenses SET `month` = DATE_FORMAT(dat_happened, "%Y-%m")');
    }
}
