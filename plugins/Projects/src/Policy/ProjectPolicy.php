<?php
declare(strict_types=1);

namespace Projects\Policy;

use App\Model\Entity\User;
use Projects\Model\Entity\Project;

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
    public function canView(User $user, Project $project): bool
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
    public function canEdit(User $user, Project $project): bool
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
    public function canUser(User $user, Project $project): bool
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
    public function canDelete(User $user, Project $entity): bool
    {
        return $entity->owner_id == $user->company_id && $user->hasRole('admin');
    }
}
