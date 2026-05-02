<?php
declare(strict_types=1);

namespace Expenses\Policy;

use App\Model\Entity\User;
use Expenses\Model\Entity\BankStatementEntry;

/**
 * BankStatementEntry Policy Resolver
 */
class BankStatementEntryPolicy
{
    /**
     * Authorize view action.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Expenses\Model\Entity\BankStatementEntry $entity Entity
     * @return bool
     */
    public function canView(User $user, BankStatementEntry $entity): bool
    {
        return true;
    }

    /**
     * Authorize edit action.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Expenses\Model\Entity\BankStatementEntry $entity Entity
     * @return bool
     */
    public function canEdit(User $user, BankStatementEntry $entity): bool
    {
        return $user->hasRole('editor');
    }

    /**
     * Authorize bookings action – editor level.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Expenses\Model\Entity\BankStatementEntry $entity Entity
     * @return bool
     */
    public function canBookings(User $user, BankStatementEntry $entity): bool
    {
        return $user->hasRole('editor');
    }

    /**
     * Authorize delete action – root only.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Expenses\Model\Entity\BankStatementEntry $entity Entity
     * @return bool
     */
    public function canDelete(User $user, BankStatementEntry $entity): bool
    {
        return $user->hasRole('admin');
    }
}
