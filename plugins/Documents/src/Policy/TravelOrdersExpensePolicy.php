<?php
declare(strict_types=1);

namespace Documents\Policy;

use App\Model\Entity\User;
use Documents\Model\Entity\TravelOrdersExpense;

/**
 * TravelOrdersExpense Policy Resolver
 */
class TravelOrdersExpensePolicy
{
    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\TravelOrdersExpense $entity Entity
     * @return bool
     */
    public function canEdit(User $user, TravelOrdersExpense $entity): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\TravelOrdersExpense $entity Entity
     * @return bool
     */
    public function canDelete(User $user, TravelOrdersExpense $entity): bool
    {
        return $user->hasRole('admin');
    }
}
