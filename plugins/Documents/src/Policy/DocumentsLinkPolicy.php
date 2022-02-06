<?php
declare(strict_types=1);

namespace Documents\Policy;

use Cake\ORM\TableRegistry;

/**
 * DocumentsLink Policy Resolver
 */
class DocumentsLinkPolicy
{
    /**
     * Authorize view action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\DocumentsLink $entity Entity
     * @return bool
     */
    public function canLink($user, $entity)
    {
        /** @var \Documents\Model\Table\InvoicesTable $InvoicesTable */
        $InvoicesTable = TableRegistry::getTableLocator()->get('Documents.Invoices');

        return $InvoicesTable->isOwnedBy($entity->document_id, $user->id) && $user->hasRole('editor');
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\DocumentsLink $entity Entity
     * @return bool
     */
    public function canDelete($user, $entity)
    {
        /** @var \Documents\Model\Table\InvoicesTable $InvoicesTable */
        $InvoicesTable = TableRegistry::getTableLocator()->get('Documents.Invoices');

        return $InvoicesTable->isOwnedBy($entity->document_id, $user->id) && $user->hasRole('editor');
    }
}
