<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddSortToAuthorityCases extends BaseMigration
{
    /**
     * Manual order of the cases within a project; existing cases keep their alphabetical order.
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('projects_authority_cases')
            ->addColumn('sort_order', 'integer', ['default' => 0, 'null' => false])
            ->addIndex(['project_id', 'sort_order'])
            ->update();

        $position = [];
        $rows = $this->fetchAll('SELECT id, project_id FROM projects_authority_cases ORDER BY project_id, title, id');
        foreach ($rows as $row) {
            $projectId = $row['project_id'];
            $position[$projectId] = ($position[$projectId] ?? 0) + 1;
            $this->execute(sprintf(
                "UPDATE projects_authority_cases SET sort_order = %d WHERE id = '%s'",
                $position[$projectId],
                $row['id'],
            ));
        }
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->table('projects_authority_cases')
            ->removeIndex(['project_id', 'sort_order'])
            ->removeColumn('sort_order')
            ->update();
    }
}
