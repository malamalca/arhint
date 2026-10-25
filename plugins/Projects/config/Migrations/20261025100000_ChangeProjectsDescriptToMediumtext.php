<?php
declare(strict_types=1);

use Migrations\BaseMigration;
use Migrations\Db\Adapter\MysqlAdapter;

class ChangeProjectsDescriptToMediumtext extends BaseMigration
{
    /**
     * The description is the project's knowledge base; with the changelog it outgrows 64 KB.
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('projects')
            ->changeColumn('descript', 'text', [
                'default' => null,
                'limit' => MysqlAdapter::TEXT_MEDIUM,
                'null' => true,
            ])
            ->update();
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->table('projects')
            ->changeColumn('descript', 'text', ['default' => null, 'null' => true])
            ->update();
    }
}
