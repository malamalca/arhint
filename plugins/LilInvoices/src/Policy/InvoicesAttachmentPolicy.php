<?php
declare(strict_types=1);

namespace LilInvoices\Policy;

use Cake\ORM\TableRegistry;

/**
 * InvoicesAttachment Policy Resolver
 */
class InvoicesAttachmentPolicy
{
    /**
     * Authorize view action
     *
     * @param \App\Model\Entity\User $user User
     * @param \LilInvoices\Model\Entity\InvoicesAttachment $entity Entity
     * @return bool
     */
    public function canView($user, $entity)
    {
        /** @var \LilInvoices\Model\Table\InvoicesTable $InvoicesTable */
        $InvoicesTable = TableRegistry::getTableLocator()->get('LilInvoices.Invoices');

        return $InvoicesTable->isOwnedBy($entity->invoice_id, $user->company_id);
    }

    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \LilInvoices\Model\Entity\InvoicesAttachment $entity Entity
     * @return bool
     */
    public function canEdit($user, $entity)
    {
        /** @var \LilInvoices\Model\Table\InvoicesTable $InvoicesTable */
        $InvoicesTable = TableRegistry::getTableLocator()->get('LilInvoices.Invoices');

        return $InvoicesTable->isOwnedBy($entity->invoice_id, $user->company_id);
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \LilInvoices\Model\Entity\InvoicesAttachment $entity Entity
     * @return bool
     */
    public function canDelete($user, $entity)
    {
        /** @var \LilInvoices\Model\Table\InvoicesTable $InvoicesTable */
        $InvoicesTable = TableRegistry::getTableLocator()->get('LilInvoices.Invoices');

        return $InvoicesTable->isOwnedBy($entity->invoice_id, $user->company_id);
    }
}
