<?php
declare(strict_types=1);

namespace Documents\Policy;

use App\Model\Entity\User;
use Cake\ORM\TableRegistry;
use Documents\Model\Entity\DocumentsAttachment;

/**
 * DocumentsAttachment Policy Resolver
 */
class DocumentsAttachmentPolicy
{
    /**
     * Authorize view action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\DocumentsAttachment $entity Entity
     * @return bool
     */
    public function canView(User $user, DocumentsAttachment $entity): bool
    {
        /** @var \Documents\Model\Table\DocumentsAttachmentsTable $DocumentsAttachmentsTable */
        $DocumentsAttachmentsTable = TableRegistry::getTableLocator()->get('Documents.DocumentsAttachments');

        return $DocumentsAttachmentsTable->isOwnedBy($entity, $user->company_id);
    }

    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\DocumentsAttachment $entity Entity
     * @return bool
     */
    public function canEdit(User $user, DocumentsAttachment $entity): bool
    {
        /** @var \Documents\Model\Table\DocumentsAttachmentsTable $DocumentsAttachmentsTable */
        $DocumentsAttachmentsTable = TableRegistry::getTableLocator()->get('Documents.DocumentsAttachments');

        return $DocumentsAttachmentsTable->isOwnedBy($entity, $user->company_id) && $user->hasRole('editor');
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\DocumentsAttachment $entity Entity
     * @return bool
     */
    public function canDelete(User $user, DocumentsAttachment $entity): bool
    {
        /** @var \Documents\Model\Table\DocumentsAttachmentsTable $DocumentsAttachmentsTable */
        $DocumentsAttachmentsTable = TableRegistry::getTableLocator()->get('Documents.DocumentsAttachments');

        return $DocumentsAttachmentsTable->isOwnedBy($entity, $user->company_id) && $user->hasRole('editor');
    }
}
