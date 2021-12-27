<?php
declare(strict_types=1);

namespace Documents\Policy;

use Cake\ORM\TableRegistry;

/**
 * DocumentsClient Policy Resolver
 */
class DocumentsClientPolicy
{
    /**
     * Authorize view action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\DocumentsClient $entity Entity
     * @return bool
     */
    public function canView($user, $entity)
    {
        /** @var \Documents\Model\Table\DocumentsTable $DocumentsTable */
        $DocumentsTable = TableRegistry::getTableLocator()->get('Documents.Documents');

        return $DocumentsTable->isOwnedBy($entity->document_id, $user->company_id);
    }

    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\DocumentsClient $entity Entity
     * @return bool
     */
    public function canEdit($user, $entity)
    {
        /** @var \Documents\Model\Table\DocumentsTable $DocumentsTable */
        $DocumentsTable = TableRegistry::getTableLocator()->get('Documents.Documents');

        return $DocumentsTable->isOwnedBy($entity->document_id, $user->company_id) && $user->hasRole('editor');
    }
}
