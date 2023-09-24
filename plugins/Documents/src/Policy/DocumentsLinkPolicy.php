<?php
declare(strict_types=1);

namespace Documents\Policy;

use App\Model\Entity\User;
use Cake\ORM\TableRegistry;
use Documents\Model\Entity\DocumentsLink;

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
    public function canLink(User $user, DocumentsLink $entity): bool
    {
        /** @var \Documents\Model\Table\DocumentsLinksTable $DocumentsLinksTable */
        $DocumentsLinksTable = TableRegistry::getTableLocator()->get('Documents.DocumentsLinks');

        return $DocumentsLinksTable->isOwnedBy($entity, $user->id) && $user->hasRole('editor');
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\DocumentsLink $entity Entity
     * @return bool
     */
    public function canDelete(User $user, DocumentsLink $entity): bool
    {
        /** @var \Documents\Model\Table\DocumentsLinksTable $DocumentsLinksTable */
        $DocumentsLinksTable = TableRegistry::getTableLocator()->get('Documents.DocumentsLinks');

        return $DocumentsLinksTable->isOwnedBy($entity, $user->id) && $user->hasRole('editor');
    }
}
