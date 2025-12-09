<?php
declare(strict_types=1);

namespace Projects\Policy;

use App\Model\Entity\User;
use Projects\Model\Entity\ProjectsTask;

/**
 * ProjectsTask Policy Resolver
 */
class ProjectsTaskPolicy
{
    use ProjectAccessTrait;

    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Projects\Model\Entity\ProjectsTask $entity Entity
     * @return bool
     */
    public function canEdit(User $user, ProjectsTask $entity): bool
    {
        return $this->canAccess($entity->project_id, $user) &&
            ($entity->isNew() || $entity->user_id == $user->id || $user->hasRole('admin'));
    }

    /**
     * Authorize view action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Projects\Model\Entity\ProjectsTask $entity Entity
     * @return bool
     */
    public function canView(User $user, ProjectsTask $entity): bool
    {
        return $this->canAccess($entity->project_id, $user);
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Projects\Model\Entity\ProjectsTask $entity Entity
     * @return bool
     */
    public function canDelete(User $user, ProjectsTask $entity): bool
    {
        return $this->canAccess($entity->project_id, $user) &&
            ($entity->user_id == $user->id || $user->hasRole('admin'));
    }
}
