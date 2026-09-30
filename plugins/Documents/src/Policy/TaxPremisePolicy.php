<?php
declare(strict_types=1);

namespace Documents\Policy;

use App\Model\Entity\User;
use Documents\Model\Entity\TaxPremise;

/**
 * TaxPremise Policy
 */
class TaxPremisePolicy
{
    /**
     * Check if $user can view TaxPremise
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\TaxPremise $entity TaxPremise
     * @return bool
     */
    public function canView(User $user, TaxPremise $entity): bool
    {
        return $entity->owner_id == $user->company_id;
    }

    /**
     * Check if $user can edit TaxPremise
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\TaxPremise $entity TaxPremise
     * @return bool
     */
    public function canEdit(User $user, TaxPremise $entity): bool
    {
        return $entity->owner_id == $user->company_id && $user->hasRole('admin');
    }

    /**
     * Check if $user can register TaxPremise at tax authority
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\TaxPremise $entity TaxPremise
     * @return bool
     */
    public function canRegister(User $user, TaxPremise $entity): bool
    {
        return $this->canEdit($user, $entity);
    }

    /**
     * Check if $user can delete TaxPremise
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\TaxPremise $entity TaxPremise
     * @return bool
     */
    public function canDelete(User $user, TaxPremise $entity): bool
    {
        return $this->canEdit($user, $entity);
    }
}
