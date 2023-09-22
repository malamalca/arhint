<?php
declare(strict_types=1);

namespace Documents\Policy;

use App\Model\Entity\User;
use Cake\ORM\TableRegistry;
use Documents\Model\Entity\DocumentsClient;

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
    public function canView(User $user, DocumentsClient $entity): bool
    {
        /** @var \Documents\Model\Table\DocumentsClientsTable $DocumentsClients */
        $DocumentsClients = TableRegistry::getTableLocator()->get('Documents.DocumentsClients');

        return $DocumentsClients->isOwnedBy($entity, $user->company_id);
    }

    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\DocumentsClient $entity Entity
     * @return bool
     */
    public function canEdit(User $user, DocumentsClient $entity): bool
    {
        //@var \Documents\Model\Table\DocumentsClientsTable $DocumentsClientsTable */
        //$DocumentsClientsTable = TableRegistry::getTableLocator()->get('Documents.DocumentsClients');

        return /*$DocumentsClientsTable->isOwnedBy($entity, $user->company_id) && */$user->hasRole('editor');
    }
}
