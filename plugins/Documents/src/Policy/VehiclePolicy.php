<?php
declare(strict_types=1);

namespace Documents\Policy;

use App\Model\Entity\User;
use Documents\Model\Entity\Vehicle;

/**
 * Vehicle Policy Resolver
 */
class VehiclePolicy
{
    /**
     * Authorize view action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\Vehicle $entity Entity
     * @return bool
     */
    public function canView(User $user, Vehicle $entity): bool
    {
        return $entity->owner_id == $user->company_id;
    }

    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\Vehicle $entity Entity
     * @return bool
     */
    public function canEdit(User $user, Vehicle $entity): bool
    {
        return $entity->owner_id == $user->company_id && $user->hasRole('editor');
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\Vehicle $entity Entity
     * @return bool
     */
    public function canDelete(User $user, Vehicle $entity): bool
    {
        return $entity->owner_id == $user->company_id && $$user->hasRole('editor');
    }
}
