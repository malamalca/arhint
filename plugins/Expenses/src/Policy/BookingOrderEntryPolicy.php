<?php
declare(strict_types=1);

namespace Expenses\Policy;

use App\Model\Entity\User;
use Expenses\Model\Entity\BookingOrderEntry;

/**
 * BookingOrderEntry Policy Resolver
 */
class BookingOrderEntryPolicy
{
    /**
     * Authorize view action.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Expenses\Model\Entity\BookingOrderEntry $entity Entity
     * @return bool
     */
    public function canView(User $user, BookingOrderEntry $entity): bool
    {
        return true;
    }

    /**
     * Authorize edit action – only when the parent order is not locked.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Expenses\Model\Entity\BookingOrderEntry $entity Entity
     * @return bool
     */
    public function canEdit(User $user, BookingOrderEntry $entity): bool
    {
        if (!empty($entity->booking_order) && $entity->booking_order->status === 'locked') {
            return false;
        }

        return true;
    }

    /**
     * Authorize delete action – only when the parent order is in draft status.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Expenses\Model\Entity\BookingOrderEntry $entity Entity
     * @return bool
     */
    public function canDelete(User $user, BookingOrderEntry $entity): bool
    {
        if (!empty($entity->booking_order) && $entity->booking_order->status !== 'draft') {
            return false;
        }

        return true;
    }
}
