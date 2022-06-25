<?php
declare(strict_types=1);

namespace Projects\Policy;

use Cake\ORM\TableRegistry;

/**
 * ProjectsWorkhour Policy Resolver
 */
class ProjectsWorkhourPolicy
{
    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Projects\Model\Entity\ProjectsWorkhour $entity Entity
     * @return bool
     */
    public function canEdit($user, $entity)
    {
        if (!$user->hasRole('admin') && !empty($entity->dat_confirmed)) {
            return false;
        }

        /** @var \Projects\Model\Table\ProjectsTable $ProjectsTable */
        $ProjectsTable = TableRegistry::getTableLocator()->get('Projects.Projects');

        return $ProjectsTable->isOwnedBy($entity->project_id, $user->company_id);
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Projects\Model\Entity\ProjectsWorkhour $entity Entity
     * @return bool
     */
    public function canDelete($user, $entity)
    {
        if (!$user->hasRole('admin') && !empty($entity->dat_confirmed)) {
            return false;
        }

        /** @var \Projects\Model\Table\ProjectsTable $ProjectsTable */
        $ProjectsTable = TableRegistry::getTableLocator()->get('Projects.Projects');

        return $ProjectsTable->isOwnedBy($entity->project_id, $user->company_id);
    }
}
