<?php
declare(strict_types=1);

namespace Projects\Policy;

use App\Model\Entity\User;
use Projects\Model\Entity\ProjectsMilestone;

/**
 * ProjectsMilestone Policy Resolver
 */
class ProjectsMilestonePolicy
{
    use ProjectAccessTrait;

    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Projects\Model\Entity\ProjectsMilestone $entity Entity
     * @return bool
     */
    public function canEdit(User $user, ProjectsMilestone $entity): bool
    {
        return $this->canAccess($entity->project_id, $user) &&
            ($entity->isNew() || $entity->user_id == $user->id || $user->hasRole('admin'));
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Projects\Model\Entity\ProjectsMilestone $entity Entity
     * @return bool
     */
    public function canDelete(User $user, ProjectsMilestone $entity): bool
    {
        return $this->canAccess($entity->project_id, $user) &&
            ($entity->user_id == $user->id || $user->hasRole('admin'));
    }
}
