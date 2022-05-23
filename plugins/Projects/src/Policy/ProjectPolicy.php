<?php
declare(strict_types=1);

namespace Projects\Policy;

/**
 * Project Policy Resolver
 */
class ProjectPolicy
{
    /**
     * Authorize view action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Projects\Model\Entity\Project $entity Entity
     * @return bool
     */
    public function canView($user, $entity)
    {
        return $entity->owner_id == $user->company_id;
    }

    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Projects\Model\Entity\Project $entity Entity
     * @return bool
     */
    public function canEdit($user, $entity)
    {
        return $entity->owner_id == $user->company_id && $user->hasRole('admin');
    }

    /**
     * Authorize user action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Projects\Model\Entity\Project $entity Entity
     * @return bool
     */
    public function canUser($user, $entity)
    {
        return $entity->owner_id == $user->company_id && $user->hasRole('admin');
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
