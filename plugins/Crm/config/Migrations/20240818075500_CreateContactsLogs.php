<?php
declare(strict_types=1);

use Cake\Core\Configure;
use Migrations\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateContactsLogs extends AbstractMigration
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
        $this->table('contacts_logs', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('contact_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('user_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('kind', 'string', [
                'default' => 'N',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('descript', 'text', [
                'default' => null,
                'limit' => MysqlAdapter::TEXT_MEDIUM,
                'null' => true,
            ])
            ->addColumn('email_uid', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(['email_uid'], ['name' => 'IX_EMAIL_UID', 'unique' => true])
            ->create();
    }
}
