<?php
declare(strict_types=1);

namespace Crm\Policy;

use App\Model\Entity\User;
use Crm\Model\Entity\Adrema;

/**
 * Adrema Policy Resolver
 */
class AdremaPolicy
{
    /**
     * Authorize view action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Crm\Model\Entity\Adrema $entity Entity
     * @return bool
     */
    public function canView(User $user, Adrema $entity): bool
    {
        return $entity->owner_id == $user->company_id;
    }

    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Crm\Model\Entity\Adrema $entity Entity
     * @return bool
     */
    public function canEdit(User $user, Adrema $entity): bool
    {
        return $entity->owner_id == $user->company_id;
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Crm\Model\Entity\Adrema $entity Entity
     * @return bool
     */
    public function canDelete(User $user, Adrema $entity): bool
    {
        return $entity->owner_id == $user->company_id;
    }
}
