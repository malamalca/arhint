<?php
declare(strict_types=1);

namespace LilCrm\Policy;

use Cake\ORM\TableRegistry;

/**
 * ContactsAddress Policy Resolver
 */
class ContactsAddressPolicy
{
    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \LilCrm\Model\Entity\ContactsAddress $entity Entity
     * @return bool
     */
    public function canEdit($user, $entity)
    {
        /** @var \LilCrm\Model\Table\ContactsTable $contactsTable */
        $contactsTable = TableRegistry::getTableLocator()->get('LilCrm.Contacts');

        return $contactsTable->isOwnedBy($entity->contact_id, $user->company_id);
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \LilCrm\Model\Entity\ContactsAddress $entity Entity
     * @return bool
     */
    public function canDelete($user, $entity)
    {
        /** @var \LilCrm\Model\Table\ContactsTable $contactsTable */
        $contactsTable = TableRegistry::getTableLocator()->get('LilCrm.Contacts');

        return $contactsTable->isOwnedBy($entity->contact_id, $user->company_id);
    }
}
