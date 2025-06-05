<?php
declare(strict_types=1);

use Cake\Core\Configure;
use Migrations\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class AddAdditionalFieldsToAdremas extends AbstractMigration
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
        $this->table('adremas')
            ->addColumn('additional_fields', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
                'after' => 'title'
            ])
            ->save();
    }
}
