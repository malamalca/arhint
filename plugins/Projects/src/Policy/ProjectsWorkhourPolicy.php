<?php
declare(strict_types=1);

namespace Projects\Policy;

use App\Model\Entity\User;
use Projects\Model\Entity\ProjectsWorkhour;

/**
 * ProjectsWorkhour Policy Resolver
 */
class ProjectsWorkhourPolicy
{
    use ProjectAccessTrait;

    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Projects\Model\Entity\ProjectsWorkhour $entity Entity
     * @return bool
     */
    public function canEdit(User $user, ProjectsWorkhour $entity): bool
    {
        if (!$user->hasRole('admin') && !empty($entity->dat_confirmed)) {
            return false;
        }

        return $this->canAccess($entity->project_id, $user) &&
            ($entity->user_id == $user->id || $user->hasRole('admin'));
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Projects\Model\Entity\ProjectsWorkhour $entity Entity
     * @return bool
     */
    public function canDelete(User $user, ProjectsWorkhour $entity): bool
    {
        if (!$user->hasRole('admin') && !empty($entity->dat_confirmed)) {
            return false;
        }

        return $this->canAccess($entity->project_id, $user) &&
            ($entity->user_id == $user->id || $user->hasRole('admin'));
    }
}
