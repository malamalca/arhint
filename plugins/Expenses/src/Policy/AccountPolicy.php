<?php
declare(strict_types=1);

namespace Expenses\Policy;

use App\Model\Entity\User;
use Expenses\Model\Entity\Account;

/**
 * Account Policy Resolver
 */
class AccountPolicy
{
    /**
     * Authorize view action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Expenses\Model\Entity\Account $entity Entity
     * @return bool
     */
    public function canView(User $user, Account $entity): bool
    {
        return true;
    }

    /**
     * Authorize edit action – root only.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Expenses\Model\Entity\Account $entity Entity
     * @return bool
     */
    public function canEdit(User $user, Account $entity): bool
    {
        return $user->hasRole('root');
    }

    /**
     * Authorize delete action – root only.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Expenses\Model\Entity\Account $entity Entity
     * @return bool
     */
    public function canDelete(User $user, Account $entity): bool
    {
        return $user->hasRole('root');
    }
}
