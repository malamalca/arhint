<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddAvatarToUsers extends AbstractMigration
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
        $table->addColumn('avatar', 'text', [
            'default' => null,
            'null' => true,
            'after' => 'active',
        ]);
        $table->update();
    }
}
