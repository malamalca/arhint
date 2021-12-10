<?php
declare(strict_types=1);

namespace LilProjects\Policy;

/**
 * ProjectsMaterial Policy Resolver
 */
class ProjectsMaterialPolicy
{
    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \LilProjects\Model\Entity\ProjectsMaterial $entity Entity
     * @return bool
     */
    public function canEdit($user, $entity)
    {
        return $entity->owner_id == $user->company_id && $user->hasRole('admin');
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \LilProjects\Model\Entity\ProjectsMaterial $entity Entity
     * @return bool
     */
    public function canDelete($user, $entity)
    {
        return $entity->owner_id == $user->company_id && $user->hasRole('admin');
    }
}
