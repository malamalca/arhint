<?php
declare(strict_types=1);

namespace Projects\Policy;

use Cake\ORM\TableRegistry;

/**
 * ProjectsComposite Policy Resolver
 */
class ProjectsCompositePolicy
{
    /**
     * Authorize view action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Projects\Model\Entity\ProjectsComposite $entity Entity
     * @return bool
     */
    public function canView($user, $entity)
    {
        /** @var \Projects\Model\Table\ProjectsTable $ProjectsTable */
        $ProjectsTable = TableRegistry::getTableLocator()->get('Projects.Projects');

        return $ProjectsTable->isOwnedBy($entity->project_id, $user->company_id);
    }

    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Projects\Model\Entity\ProjectsComposite $entity Entity
     * @return bool
     */
    public function canEdit($user, $entity)
    {
        /** @var \Projects\Model\Table\ProjectsTable $ProjectsTable */
        $ProjectsTable = TableRegistry::getTableLocator()->get('Projects.Projects');

        return $ProjectsTable->isOwnedBy($entity->project_id, $user->company_id);
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Projects\Model\Entity\ProjectsComposite $entity Entity
     * @return bool
     */
    public function canDelete($user, $entity)
    {
        /** @var \Projects\Model\Table\ProjectsTable $ProjectsTable */
        $ProjectsTable = TableRegistry::getTableLocator()->get('Projects.Projects');

        return $ProjectsTable->isOwnedBy($entity->project_id, $user->company_id);
    }
}
