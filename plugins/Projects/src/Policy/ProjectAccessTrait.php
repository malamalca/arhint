<?php
declare(strict_types=1);

namespace Projects\Policy;

use App\Model\Entity\User;
use Cake\ORM\TableRegistry;
use Projects\Model\Entity\Project;

trait ProjectAccessTrait
{
    /**
     * Check if user has access to specified project.
     *
     * @param \Projects\Model\Entity\Project|string|null $project Project entity or project id
     * @param \App\Model\Entity\User $user User
     * @return bool
     */
    public function canAccess(Project|string|null $project, User $user): bool
    {
        if (empty($project)) {
            return false;
        }

        $projectId = $project;
        if ($project instanceof Project) {
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
