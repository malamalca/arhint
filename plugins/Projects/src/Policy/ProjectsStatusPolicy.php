<?php
declare(strict_types=1);

namespace Projects\Policy;

use App\Model\Entity\User;
use Projects\Model\Entity\ProjectsStatus;

/**
 * ProjectsStatus Policy Resolver
 */
class ProjectsStatusPolicy
{
    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Projects\Model\Entity\ProjectsStatus $entity Entity
     * @return bool
     */
    public function canEdit(User $user, ProjectsStatus $entity): bool
    {
        return $entity->owner_id == $user->company_id && $user->hasRole('admin');
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Projects\Model\Entity\ProjectsStatus $entity Entity
     * @return bool
     */
    public function canDelete(User $user, ProjectsStatus $entity): bool
    {
        return $entity->owner_id == $user->company_id && $user->hasRole('admin');
    }
}
