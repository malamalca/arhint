<?php
declare(strict_types=1);

namespace Expenses\Policy;

use App\Model\Entity\User;
use Expenses\Model\Entity\PaymentsAccount;

/**
 * PaymentsAccount Policy Resolver
 */
class PaymentsAccountPolicy
{
    /**
     * Authorize view action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Expenses\Model\Entity\PaymentsAccount $entity Entity
     * @return bool
     */
    public function canView(User $user, PaymentsAccount $entity): bool
    {
        return $entity->owner_id == $user->company_id;
    }

    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Expenses\Model\Entity\PaymentsAccount $entity Entity
     * @return bool
     */
    public function canEdit(User $user, PaymentsAccount $entity): bool
    {
        return $entity->owner_id == $user->company_id;
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Expenses\Model\Entity\PaymentsAccount $entity Entity
     * @return bool
     */
    public function canDelete(User $user, PaymentsAccount $entity): bool
    {
        return $entity->owner_id == $user->company_id;
    }
}
