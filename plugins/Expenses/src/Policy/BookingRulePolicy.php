<?php
declare(strict_types=1);

namespace Expenses\Policy;

use App\Model\Entity\User;
use Expenses\Model\Entity\BookingRule;

/**
 * BookingRule Policy Resolver
 */
class BookingRulePolicy
{
    /**
     * Authorize view action – any user belonging to the same company.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Expenses\Model\Entity\BookingRule $entity Entity
     * @return bool
     */
    public function canView(User $user, BookingRule $entity): bool
    {
        return $entity->owner_id == $user->company_id;
    }

    /**
     * Authorize edit action – any company member (rules are shared config).
     *
     * @param \App\Model\Entity\User $user User
     * @param \Expenses\Model\Entity\BookingRule $entity Entity
     * @return bool
     */
    public function canEdit(User $user, BookingRule $entity): bool
    {
        if ($entity->isNew()) {
            return $user->hasRole('admin');
        }

        return $entity->owner_id == $user->company_id;
    }

    /**
     * Authorize delete action – root only.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Expenses\Model\Entity\BookingRule $entity Entity
     * @return bool
     */
    public function canDelete(User $user, BookingRule $entity): bool
    {
        return $entity->owner_id == $user->company_id
            && $user->hasRole('root');
    }
}
