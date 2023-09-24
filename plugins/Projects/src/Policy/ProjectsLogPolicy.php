<?php
declare(strict_types=1);

namespace Projects\Policy;

use App\Model\Entity\User;
use Projects\Model\Entity\ProjectsLog;

/**
 * ProjectsLog Policy Resolver
 */
class ProjectsLogPolicy
{
    use ProjectAccessTrait;

    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Projects\Model\Entity\ProjectsLog $entity Entity
     * @return bool
     */
    public function canEdit(User $user, ProjectsLog $entity): bool
    {
        return $this->canAccess($entity->project_id, $user) &&
            ($entity->isNew() || $entity->user_id == $user->id || $user->hasRole('admin'));
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Projects\Model\Entity\ProjectsLog $entity Entity
     * @return bool
     */
    public function canDelete(User $user, ProjectsLog $entity): bool
    {
        return $this->canAccess($entity->project_id, $user) &&
            ($entity->user_id == $user->id || $user->hasRole('admin'));
    }
}
