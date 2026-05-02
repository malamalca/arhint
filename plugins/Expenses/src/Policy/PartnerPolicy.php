<?php
declare(strict_types=1);

namespace Expenses\Policy;

use App\Model\Entity\User;
use Cake\ORM\TableRegistry;
use Expenses\Model\Entity\Partner;

/**
 * Partner Policy Resolver
 */
class PartnerPolicy
{
    /**
     * Authorize view action – any authenticated user.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Expenses\Model\Entity\Partner $entity Entity
     * @return bool
     */
    public function canView(User $user, Partner $entity): bool
    {
        return true;
    }

    /**
     * Authorize edit action – users who own the related contact.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Expenses\Model\Entity\Partner $entity Entity
     * @return bool
     */
    public function canEdit(User $user, Partner $entity): bool
    {
        /** @var \Crm\Model\Table\ContactsTable $contactsTable */
        $contactsTable = TableRegistry::getTableLocator()->get('Crm.Contacts');

        return $contactsTable->isOwnedBy($entity->contact_id, $user->company_id);
    }

    /**
     * Authorize delete action – users who own the related contact.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Expenses\Model\Entity\Partner $entity Entity
     * @return bool
     */
    public function canDelete(User $user, Partner $entity): bool
    {
        /** @var \Crm\Model\Table\ContactsTable $contactsTable */
        $contactsTable = TableRegistry::getTableLocator()->get('Crm.Contacts');

        return $contactsTable->isOwnedBy($entity->contact_id, $user->company_id);
    }
}
