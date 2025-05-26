<?php
declare(strict_types=1);

use Cake\Core\Configure;
use Migrations\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class ModifyAdremasContactsTable extends AbstractMigration
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
        $this->table('adremas_contacts')
            ->removeColumn('title')
            ->removeColumn('street')
            ->removeColumn('city')
            ->removeColumn('zip')
            ->removeColumn('country')
            ->removeColumn('email')
            ->addColumn('contact_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
                'after' => 'adrema_id'
            ])
            ->addColumn('contacts_email_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
                'after' => 'contacts_address_id'
            ])
            ->addColumn('descript', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
                'after' => 'contacts_email_id',
            ])
            ->save();
    }
}
