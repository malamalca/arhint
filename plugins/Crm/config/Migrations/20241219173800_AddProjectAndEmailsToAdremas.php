<?php
declare(strict_types=1);

use Cake\Core\Configure;
use Migrations\BaseMigration;
use Migrations\Db\Adapter\MysqlAdapter;

class AddProjectAndEmailsToAdremas extends BaseMigration
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
            ->addColumn('project_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
                'after' => 'owner_id',
            ])
            ->save();

        $this->table('adremas_contacts')
            ->addColumn('email', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => true,
                'after' => 'country',
            ])
            ->save();
    }
}
