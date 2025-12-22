<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class ModifyAdremasTable extends AbstractMigration
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
            ->removeColumn('additional_fields')
            ->addColumn('kind', 'string', [
                'default' => 'email',
                'limit' => 20,
                'null' => false,
                'after' => 'project_id',
            ])
            ->addColumn('kind_type', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
                'after' => 'kind',
            ])
            ->addColumn('user_values', 'text', [
                'default' => null,
                'limit' => MysqlAdapter::TEXT_MEDIUM,
                'null' => true,
                'after' => 'title',
            ])
            ->save();
    }
}
