<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddValidUntilToAuthorityEvents extends BaseMigration
{
    /**
     * Last day on which received conditions or an opinion are valid.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('projects_authority_events')
            ->addColumn('date_valid', 'date', ['null' => true, 'after' => 'date_due'])
            ->update();
    }
}
