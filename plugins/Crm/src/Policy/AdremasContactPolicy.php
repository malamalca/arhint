<?php
declare(strict_types=1);

namespace Crm\Policy;

use App\Model\Entity\User;
use Cake\ORM\TableRegistry;
use Crm\Model\Entity\AdremasContact;

/**
 * AdremasContact Policy Resolver
 */
class AdremasContactPolicy
{
    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Crm\Model\Entity\AdremasContact $entity Entity
     * @return bool
     */
    public function canEdit(User $user, AdremasContact $entity): bool
    {
        /** @var \Crm\Model\Table\ContactsTable $adremasTable */
        $adremasTable = TableRegistry::getTableLocator()->get('Crm.Adremas');

        return $adremasTable->isOwnedBy($entity->adrema_id, $user->company_id);
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Crm\Model\Entity\AdremasContact $entity Entity
     * @return bool
     */
    public function canDelete(User $user, AdremasContact $entity): bool
    {
        /** @var \Crm\Model\Table\ContactsTable $adremasTable */
        $adremasTable = TableRegistry::getTableLocator()->get('Crm.Adremas');

        return $adremasTable->isOwnedBy($entity->adrema_id, $user->company_id);
    }
}
