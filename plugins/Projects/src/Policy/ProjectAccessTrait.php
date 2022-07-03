<?php
declare(strict_types=1);

namespace Projects\Policy;

use Cake\ORM\TableRegistry;

trait ProjectAccessTrait
{
    /**
     * Check if user has access to specified project.
     *
     * @param \Projects\Model\Entity\Project|string $project Project entity or project id
     * @param \App\Model\Entity\User $user User
     * @return bool
     */
    public function canAccess($project, $user)
    {
        $projectId = $project;
        if ($project instanceof \Projects\Model\Entity\Project) {
            $projectId = $project->id;

            if (!$project->owner_id == $user->company_id) {
                return false;
            }
        } else {
            /** @var \Projects\Model\Table\ProjectsTable $ProjectsTable */
            $ProjectsTable = TableRegistry::getTableLocator()->get('Projects.Projects');

            if (!$ProjectsTable->isOwnedBy((string)$projectId, $user->company_id)) {
                return false;
            }
        }

        // if user is not an admin, additional check in projects-users table is required
        if (!$user->hasRole('admin')) {
            /** @var \Projects\Model\Table\ProjectsUsersTable $ProjectsUsersTable */
            $ProjectsUsersTable = TableRegistry::getTableLocator()->get('Projects.ProjectsUsers');

            if (!$ProjectsUsersTable->hasAccess((string)$projectId, $user->id)) {
                return false;
            }
        }

        return true;
    }
}
