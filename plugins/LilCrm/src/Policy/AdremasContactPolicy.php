<?php
declare(strict_types=1);

namespace LilCrm\Policy;

use Cake\ORM\TableRegistry;

/**
 * AdremasContact Policy Resolver
 */
class AdremasContactPolicy
{
    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \LilCrm\Model\Entity\AdremasContact $entity Entity
     * @return bool
     */
    public function canEdit($user, $entity)
    {
        /** @var \LilCrm\Model\Table\ContactsTable $adremasTable */
        $adremasTable = TableRegistry::getTableLocator()->get('LilCrm.Adremas');

        return $adremasTable->isOwnedBy($entity->adrema_id, $user->company_id);
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \LilCrm\Model\Entity\AdremasContact $entity Entity
     * @return bool
     */
    public function canDelete($user, $entity)
    {
        /** @var \LilCrm\Model\Table\ContactsTable $adremasTable */
        $adremasTable = TableRegistry::getTableLocator()->get('LilCrm.Adremas');

        return $adremasTable->isOwnedBy($entity->adrema_id, $user->company_id);
    }
}
