<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class ChangeFieldInvoicesTemplatesBody extends AbstractMigration
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
        $table = $this->table('invoices_templates');
        $table->changeColumn('body', 'text', ['limit' => MysqlAdapter::TEXT_MEDIUM])
            ->save();

        $table->update();
    }
}
