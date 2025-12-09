<?php
declare(strict_types=1);

namespace Projects\Policy;

use App\Model\Entity\User;
use Projects\Model\Entity\ProjectsTasksComment;

/**
 * ProjectsTasksComment Policy Resolver
 */
class ProjectsTasksCommentPolicy
{
    use ProjectAccessTrait;

    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Projects\Model\Entity\ProjectsTasksComment $entity Entity
     * @return bool
     */
    public function canEdit(User $user, ProjectsTasksComment $entity): bool
    {
        return $entity->isNew() || $entity->user_id == $user->id || $user->hasRole('admin');
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Projects\Model\Entity\ProjectsTasksComment $entity Entity
     * @return bool
     */
    public function canDelete(User $user, ProjectsTasksComment $entity): bool
    {
        return $entity->user_id == $user->id || $user->hasRole('admin');
    }
}
