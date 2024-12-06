<?php
declare(strict_types=1);

use Cake\Core\Configure;
use Migrations\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class AddAccessToUsers extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('users');
        $table->addColumn('access', 'integer', [
            'limit' => 4,
            'default' => null,
            'null' => true,
            'after' => 'privileges',
        ]);
        $table->update();
    }
}
