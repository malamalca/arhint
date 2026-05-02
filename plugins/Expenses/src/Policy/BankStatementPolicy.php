<?php
declare(strict_types=1);

namespace Expenses\Policy;

use App\Model\Entity\User;
use Expenses\Model\Entity\BankStatement;

/**
 * BankStatement Policy Resolver
 */
class BankStatementPolicy
{
    /**
     * Authorize view action – any user belonging to the same company.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Expenses\Model\Entity\BankStatement $entity Entity
     * @return bool
     */
    public function canView(User $user, BankStatement $entity): bool
    {
        return $entity->owner_id == $user->company_id;
    }

    /**
     * Authorize edit action – root only.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Expenses\Model\Entity\BankStatement $entity Entity
     * @return bool
     */
    public function canEdit(User $user, BankStatement $entity): bool
    {
        if ($entity->isNew()) {
            return true;
        }

        return $entity->owner_id == $user->company_id && $user->hasRole('editor');
    }

    /**
     * Authorize delete action – root only.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Expenses\Model\Entity\BankStatement $entity Entity
     * @return bool
     */
    public function canDelete(User $user, BankStatement $entity): bool
    {
        return $entity->owner_id == $user->company_id && $user->hasRole('admin');
    }

    /**
     * Authorize import action – any user with editor role.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Expenses\Model\Entity\BankStatement $entity Entity
     * @return bool
     */
    public function canImport(User $user, BankStatement $entity): bool
    {
        return $user->hasRole('editor');
    }
}
