<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddLoginRedirectToUsers extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function change()
    {
        $table = $this->table('users');
        $table->addColumn('login_redirect', 'string', [
            'limit' => 200,
            'default' => null,
            'null' => true,
            'after' => 'active',
        ]);
        $table->update();
    }
}
