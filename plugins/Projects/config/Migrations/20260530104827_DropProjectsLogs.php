<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Drop the projects_logs table - logs are now stored in App.Logs (logs table)
 * with model='Projects.Project', action='Comment', foreign_id=project_id.
 */
class DropProjectsLogs extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('projects_logs');
        if ($table->exists()) {
            $table->drop()->save();
        }
    }
}
