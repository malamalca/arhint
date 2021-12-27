<?php
declare(strict_types=1);

namespace Expenses\Policy;

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
    public function canView($user, $entity)
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
    public function canEdit($user, $entity)
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
    public function canDelete($user, $entity)
    {
        return $entity->owner_id == $user->company_id;
    }
}
