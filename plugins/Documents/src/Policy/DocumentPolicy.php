<?php
declare(strict_types=1);

namespace Documents\Policy;

use App\Model\Entity\User;
use Documents\Model\Entity\Document;

/**
 * Document Policy Resolver
 */
class DocumentPolicy
{
    /**
     * Authorize view action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\Document $entity Entity
     * @return bool
     */
    public function canView(User $user, Document $entity): bool
    {
        return $entity->owner_id == $user->company_id;
    }

    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\Document $entity Entity
     * @return bool
     */
    public function canEdit(User $user, Document $entity): bool
    {
        return $entity->owner_id == $user->company_id && $user->hasRole('editor');
    }

    /**
     * Authorize email action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\Document $entity Entity
     * @return bool
     */
    public function canEmail(User $user, Document $entity): bool
    {
        return $entity->owner_id == $user->company_id;
    }

    /**
     * Authorize sign action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\Document $entity Entity
     * @return bool
     */
    public function canSign(User $user, Document $entity): bool
    {
        return $entity->owner_id == $user->company_id && $user->hasRole('editor');
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\Document $entity Entity
     * @return bool
     */
    public function canDelete(User $user, Document $entity): bool
    {
        return $entity->owner_id == $user->company_id && $user->hasRole('editor');
    }
}
