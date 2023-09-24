<?php
declare(strict_types=1);

namespace Calendar\Policy;

use App\Model\Entity\User;
use Calendar\Model\Entity\Event;

/**
 * Event Policy Resolver
 */
class EventPolicy
{
    /**
     * Authorize view action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Calendar\Model\Entity\Event $entity Entity
     * @return bool
     */
    public function canView(User $user, Event $entity): bool
    {
        return $entity->calendar_id == $user->id;
    }

    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Calendar\Model\Entity\Event $entity Entity
     * @return bool
     */
    public function canEdit(User $user, Event $entity): bool
    {
        return $entity->calendar_id == $user->id;
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Calendar\Model\Entity\Event $entity Entity
     * @return bool
     */
    public function canDelete(User $user, Event $entity): bool
    {
        return $entity->calendar_id == $user->id;
    }
}
