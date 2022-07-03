<?php
declare(strict_types=1);

namespace Projects\Policy;

use Cake\ORM\TableRegistry;

/**
 * Project Policy Resolver
 */
class ProjectPolicy
{
    use ProjectAccessTrait;

    /**
     * Authorize view action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Projects\Model\Entity\Project $project Entity
     * @return bool
     */
    public function canView($user, $project)
    {
        return $this->canAccess($project, $user);
    }

    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Projects\Model\Entity\Project $project Entity
     * @return bool
     */
    public function canEdit($user, $project)
    {
        return $user->hasRole('admin') && $this->canAccess($project, $user);
    }

    /**
     * Authorize user action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Projects\Model\Entity\Project $project Entity
     * @return bool
     */
    public function canUser($user, $project)
    {
        return $user->hasRole('admin') && $this->canAccess($project, $user);
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Projects\Model\Entity\Project $entity Entity
     * @return bool
     */
    public function canDelete($user, $entity)
    {
        return $entity->owner_id == $user->company_id && $user->hasRole('admin');
    }
}
