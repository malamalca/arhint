<?php
declare(strict_types=1);

namespace Crm\Policy;

use App\Model\Entity\User;
use Cake\ORM\TableRegistry;
use Crm\Model\Entity\ContactsAddress;

/**
 * ContactsAddress Policy Resolver
 */
class ContactsAddressPolicy
{
    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Crm\Model\Entity\ContactsAddress $entity Entity
     * @return bool
     */
    public function canEdit(User $user, ContactsAddress $entity): bool
    {
        /** @var \Crm\Model\Table\ContactsTable $contactsTable */
        $contactsTable = TableRegistry::getTableLocator()->get('Crm.Contacts');

        return $contactsTable->isOwnedBy($entity->contact_id, $user->company_id);
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Crm\Model\Entity\ContactsAddress $entity Entity
     * @return bool
     */
    public function canDelete(User $user, ContactsAddress $entity): bool
    {
        /** @var \Crm\Model\Table\ContactsTable $contactsTable */
        $contactsTable = TableRegistry::getTableLocator()->get('Crm.Contacts');

        return $contactsTable->isOwnedBy($entity->contact_id, $user->company_id);
    }
}
