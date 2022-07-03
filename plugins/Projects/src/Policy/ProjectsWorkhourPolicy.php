<?php
declare(strict_types=1);

namespace Projects\Policy;

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
    public function canEdit($user, $entity)
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
    public function canDelete($user, $entity)
    {
        if (!$user->hasRole('admin') && !empty($entity->dat_confirmed)) {
            return false;
        }

        return $this->canAccess($entity->project_id, $user) &&
            ($entity->user_id == $user->id || $user->hasRole('admin'));
    }
}
