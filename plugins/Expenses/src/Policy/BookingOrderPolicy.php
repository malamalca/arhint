<?php
declare(strict_types=1);

namespace Expenses\Policy;

use App\Model\Entity\User;
use Expenses\Model\Entity\BookingOrder;

/**
 * BookingOrder Policy Resolver
 */
class BookingOrderPolicy
{
    /**
     * Authorize view action – any user belonging to the same company.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Expenses\Model\Entity\BookingOrder $entity Entity
     * @return bool
     */
    public function canView(User $user, BookingOrder $entity): bool
    {
        return $entity->owner_id == $user->company_id;
    }

    /**
     * Authorize edit action – opener or root, and order must not be locked.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Expenses\Model\Entity\BookingOrder $entity Entity
     * @return bool
     */
    public function canEdit(User $user, BookingOrder $entity): bool
    {
        if ($entity->status === 'locked') {
            return false;
        }

        // Allow creating new orders – owner/opener will be set on save
        if ($entity->isNew()) {
            return true;
        }

        return $entity->owner_id == $user->company_id
            && ($entity->opener_id == $user->id || $user->hasRole('root'));
    }

    /**
     * Authorize delete action – root only, and order must be in draft status.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Expenses\Model\Entity\BookingOrder $entity Entity
     * @return bool
     */
    public function canDelete(User $user, BookingOrder $entity): bool
    {
        return $user->hasRole('root') || ($entity->owner_id == $user->company_id
            && $entity->status === 'draft' && $user->hasRole('admin'));
    }

    /**
     * Authorize post action – moves status from draft to posted.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Expenses\Model\Entity\BookingOrder $entity Entity
     * @return bool
     */
    public function canPost(User $user, BookingOrder $entity): bool
    {
        return $entity->owner_id == $user->company_id
            && $entity->status === 'draft'
            && ($entity->opener_id == $user->id || $user->hasRole('admin'));
    }

    /**
     * Authorize lock action – moves status from posted to locked.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Expenses\Model\Entity\BookingOrder $entity Entity
     * @return bool
     */
    public function canLock(User $user, BookingOrder $entity): bool
    {
        return $entity->owner_id == $user->company_id
            && $entity->status === 'posted'
            && $user->hasRole('admin');
    }
}
