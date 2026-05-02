<?php
declare(strict_types=1);

namespace Expenses\Policy;

use App\Model\Entity\User;
use Expenses\Model\Entity\BookingRuleAccountEntry;

/**
 * BookingRuleAccountEntry Policy Resolver
 */
class BookingRuleAccountEntryPolicy
{
    /**
     * Authorize edit action.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Expenses\Model\Entity\BookingRuleAccountEntry $entity Entity
     * @return bool
     */
    public function canEdit(User $user, BookingRuleAccountEntry $entity): bool
    {
        if ($entity->isNew()) {
            return true;
        }

        return !empty($entity->booking_rule)
            && $entity->booking_rule->owner_id == $user->company_id;
    }

    /**
     * Authorize delete action.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Expenses\Model\Entity\BookingRuleAccountEntry $entity Entity
     * @return bool
     */
    public function canDelete(User $user, BookingRuleAccountEntry $entity): bool
    {
        return !empty($entity->booking_rule)
            && $entity->booking_rule->owner_id == $user->company_id;
    }
}
