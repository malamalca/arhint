<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Initial Migration
 */
class LilCrmAddIndexesForPrimarySubitems extends AbstractMigration
{
    /**
     * Up migration tasks
     *
     * @return void
     */
    public function up()
    {
        $table = $this->table('contacts_accounts');
        $table->addIndex(['contact_id', 'primary'], ['name' => 'IX_CONTACT'])
              ->save();

        $table = $this->table('contacts_addresses');
        $table->addIndex(['contact_id', 'primary'], ['name' => 'IX_CONTACT'])
            ->save();

        $table = $this->table('contacts_emails');
        $table->addIndex(['contact_id', 'primary'], ['name' => 'IX_CONTACT'])
            ->save();

        $table = $this->table('contacts_phones');
        $table->addIndex(['contact_id', 'primary'], ['name' => 'IX_CONTACT'])
            ->save();
    }

    /**
     * Down migration tasks
     *
     * @return void
     */
    public function down()
    {
        $table = $this->table('contacts_accounts');
        $table->removeIndexByName('IX_CONTACT')
            ->save();

        $table = $this->table('contacts_addresses');
        $table->removeIndexByName('IX_CONTACT')
            ->save();

        $table = $this->table('contacts_emails');
        $table->removeIndexByName('IX_CONTACT')
            ->save();

        $table = $this->table('contacts_phones');
        $table->removeIndexByName('IX_CONTACT')
            ->save();
    }
}
