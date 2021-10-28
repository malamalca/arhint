<?php
declare(strict_types=1);

namespace LilProjects\Policy;

use Cake\ORM\TableRegistry;

/**
 * ProjectsLog Policy Resolver
 */
class ProjectsLogPolicy
{
    /**
     * Authorize view action
     *
     * @param \App\Model\Entity\User $user User
     * @param \LilProjects\Model\Entity\ProjectsLog $entity Entity
     * @return bool
     */
    public function canView($user, $entity)
    {
        /** @var \LilProjects\Model\Table\ProjectsTable $ProjectsTable */
        $ProjectsTable = TableRegistry::getTableLocator()->get('LilProjects.Projects');

        return $ProjectsTable->isOwnedBy($entity->project_id, $user->company_id);
    }

    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \LilProjects\Model\Entity\ProjectsLog $entity Entity
     * @return bool
     */
    public function canEdit($user, $entity)
    {
        /** @var \LilProjects\Model\Table\ProjectsTable $ProjectsTable */
        $ProjectsTable = TableRegistry::getTableLocator()->get('LilProjects.Projects');

        return $ProjectsTable->isOwnedBy($entity->project_id, $user->company_id) &&
            ($entity->user_id == $user->id || $user->hasRole('admin'));
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \LilProjects\Model\Entity\ProjectsLog $entity Entity
     * @return bool
     */
    public function canDelete($user, $entity)
    {
        /** @var \LilProjects\Model\Table\ProjectsTable $ProjectsTable */
        $ProjectsTable = TableRegistry::getTableLocator()->get('LilProjects.Projects');

        return $ProjectsTable->isOwnedBy($entity->project_id, $user->company_id) &&
            ($entity->user_id == $user->id || $user->hasRole('admin'));
    }
}
