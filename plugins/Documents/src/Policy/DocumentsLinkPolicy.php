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
        /** @var \Documents\Model\Table\DocumentsTable $DocumentsTable */
        $DocumentsTable = TableRegistry::getTableLocator()->get('Documents.Documents');

        return $DocumentsTable->isOwnedBy($entity->document_id, $user->id) && $user->hasRole('editor');
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
        /** @var \Documents\Model\Table\DocumentsTable $DocumentsTable */
        $DocumentsTable = TableRegistry::getTableLocator()->get('Documents.Documents');

        return $DocumentsTable->isOwnedBy($entity->document_id, $user->id) && $user->hasRole('editor');
    }
}
