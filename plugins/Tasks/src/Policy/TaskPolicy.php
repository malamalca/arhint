<?php
declare(strict_types=1);

namespace Tasks\Policy;

use App\Model\Entity\User;
use Tasks\Model\Entity\Task;

/**
 * Task Policy Resolver
 */
class TaskPolicy
{
    /**
     * Authorize view action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Tasks\Model\Entity\Task $entity Entity
     * @return bool
     */
    public function canView(User $user, Task $entity): bool
    {
        return $entity->owner_id == $user->company_id;
    }

    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Tasks\Model\Entity\Task $entity Entity
     * @return bool
     */
    public function canEdit(User $user, Task $entity): bool
    {
        return $entity->owner_id == $user->company_id;
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Tasks\Model\Entity\Task $entity Entity
     * @return bool
     */
    public function canDelete(User $user, Task $entity): bool
    {
        return $entity->owner_id == $user->company_id;
    }
}
