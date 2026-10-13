<?php
declare(strict_types=1);

namespace Projects\Policy;

use App\AppPluginsEnum;
use App\Model\Entity\User;
use Projects\Model\Entity\Project;

/**
 * Project Policy Resolver
 */
class ProjectPolicy
{
    use ProjectAccessTrait;

    /**
     * @param \App\Model\Entity\User $user User.
     * @param \Projects\Model\Entity\Project $project Project.
     * @return bool
     */
    public function canAuthorityCalendar(User $user, Project $project): bool
    {
        return $user->hasAccess(AppPluginsEnum::Calendar) && $this->canAccess($project, $user);
    }

    /**
     * Procedure editing follows the existing editor role and project membership.
     *
     * @param \App\Model\Entity\User $user User.
     * @param \Projects\Model\Entity\Project $project Project.
     * @return bool
     */
    public function canAuthorities(User $user, Project $project): bool
    {
        return $user->hasRole('editor') && $this->canAccess($project, $user);
    }

    /**
     * Final reviews are reserved for project administrators.
     *
     * @param \App\Model\Entity\User $user User.
     * @param \Projects\Model\Entity\Project $project Project.
     * @return bool
     */
    public function canAuthorityReview(User $user, Project $project): bool
    {
        return $user->hasRole('admin') && $this->canAccess($project, $user);
    }

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
