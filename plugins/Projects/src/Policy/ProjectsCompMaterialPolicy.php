<?php
declare(strict_types=1);

namespace Projects\Policy;

use Cake\ORM\TableRegistry;

/**
 * ProjectsCompMaterial Policy Resolver
 */
class ProjectsCompMaterialPolicy
{
    /**
     * Authorize view action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Projects\Model\Entity\ProjectsCompMaterial $entity Entity
     * @return bool
     */
    public function canView($user, $entity)
    {
        /** @var \Projects\Model\Table\ProjectsCompositesTable $ProjectsCompositesTable */
        $ProjectsCompositesTable = TableRegistry::getTableLocator()->get('Projects.ProjectsComposites');

        $composite = $ProjectsCompositesTable->get($entity->composite_id);

        /** @var \Projects\Model\Table\ProjectsTable $ProjectsTable */
        $ProjectsTable = TableRegistry::getTableLocator()->get('Projects.Projects');

        return $ProjectsTable->isOwnedBy($composite->project_id, $user->company_id);
    }

    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Projects\Model\Entity\ProjectsCompMaterial $entity Entity
     * @return bool
     */
    public function canEdit($user, $entity)
    {
        /** @var \Projects\Model\Table\ProjectsCompositesTable $ProjectsCompositesTable */
        $ProjectsCompositesTable = TableRegistry::getTableLocator()->get('Projects.ProjectsComposites');

        $composite = $ProjectsCompositesTable->get($entity->composite_id);

        /** @var \Projects\Model\Table\ProjectsTable $ProjectsTable */
        $ProjectsTable = TableRegistry::getTableLocator()->get('Projects.Projects');

        return $ProjectsTable->isOwnedBy($composite->project_id, $user->company_id);
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Projects\Model\Entity\ProjectsCompMaterial $entity Entity
     * @return bool
     */
    public function canDelete($user, $entity)
    {
        /** @var \Projects\Model\Table\ProjectsCompositesTable $ProjectsCompositesTable */
        $ProjectsCompositesTable = TableRegistry::getTableLocator()->get('Projects.ProjectsComposites');

        $composite = $ProjectsCompositesTable->get($entity->composite_id);

        /** @var \Projects\Model\Table\ProjectsTable $ProjectsTable */
        $ProjectsTable = TableRegistry::getTableLocator()->get('Projects.Projects');

        return $ProjectsTable->isOwnedBy($composite->project_id, $user->company_id);
    }
}
