<?php
declare(strict_types=1);

namespace Documents\Policy;

use App\Model\Entity\User;
use Documents\Model\Entity\Invoice;

/**
 * Invoice Policy Resolver
 */
class InvoicePolicy
{
    /**
     * Authorize view action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\Invoice $entity Entity
     * @return bool
     */
    public function canView(User $user, Invoice $entity): bool
    {
        return $entity->owner_id == $user->company_id;
    }

    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\Invoice $entity Entity
     * @return bool
     */
    public function canEdit(User $user, Invoice $entity): bool
    {
        return $entity->owner_id == $user->company_id && $user->hasRole('editor');
    }

    /**
     * Authorize email action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\Invoice $entity Entity
     * @return bool
     */
    public function canEmail(User $user, Invoice $entity): bool
    {
        return $entity->owner_id == $user->company_id;
    }

    /**
     * Authorize sign action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\Invoice $entity Entity
     * @return bool
     */
    public function canSign(User $user, Invoice $entity): bool
    {
        return $entity->owner_id == $user->company_id && $user->hasRole('editor');
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\Invoice $entity Entity
     * @return bool
     */
    public function canDelete(User $user, Invoice $entity): bool
    {
        return $entity->owner_id == $user->company_id && $user->hasRole('editor');
    }
}
