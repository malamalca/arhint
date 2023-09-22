<?php
declare(strict_types=1);

namespace Tasks\Policy;

use App\Model\Entity\User;
use Tasks\Model\Entity\TasksFolder;

/**
 * TasksFolder Policy Resolver
 */
class TasksFolderPolicy
{
    /**
     * Authorize view action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Tasks\Model\Entity\TasksFolder $entity Entity
     * @return bool
     */
    public function canView(User $user, TasksFolder $entity): bool
    {
        return $entity->owner_id == $user->company_id;
    }

    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Tasks\Model\Entity\TasksFolder $entity Entity
     * @return bool
     */
    public function canEdit(User $user, TasksFolder $entity): bool
    {
        return $entity->owner_id == $user->company_id;
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Tasks\Model\Entity\TasksFolder $entity Entity
     * @return bool
     */
    public function canDelete(User $user, TasksFolder $entity): bool
    {
        return $entity->owner_id == $user->company_id;
    }
}
