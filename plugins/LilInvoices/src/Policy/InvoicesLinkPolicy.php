<?php
declare(strict_types=1);

namespace LilInvoices\Policy;

use Cake\ORM\TableRegistry;

/**
 * InvoicesLink Policy Resolver
 */
class InvoicesLinkPolicy
{
    /**
     * Authorize view action
     *
     * @param \App\Model\Entity\User $user User
     * @param \LilInvoices\Model\Entity\InvoicesLink $entity Entity
     * @return bool
     */
    public function canLink($user, $entity)
    {
        /** @var \LilInvoices\Model\Table\InvoicesTable $InvoicesTable */
        $InvoicesTable = TableRegistry::getTableLocator()->get('LilInvoices.Invoices');

        return $InvoicesTable->isOwnedBy($entity->invoice_id, $user->id);
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \LilInvoices\Model\Entity\InvoicesLink $entity Entity
     * @return bool
     */
    public function canDelete($user, $entity)
    {
        /** @var \LilInvoices\Model\Table\InvoicesTable $InvoicesTable */
        $InvoicesTable = TableRegistry::getTableLocator()->get('LilInvoices.Invoices');

        return $InvoicesTable->isOwnedBy($entity->invoice_id, $user->id);
    }
}
