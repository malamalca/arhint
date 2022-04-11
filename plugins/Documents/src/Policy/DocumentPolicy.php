<?php
declare(strict_types=1);

namespace Documents\Policy;

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
    public function canView($user, $entity)
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
    public function canEdit($user, $entity)
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
    public function canEmail($user, $entity)
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
    public function canSign($user, $entity)
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
    public function canDelete($user, $entity)
    {
        return $entity->owner_id == $user->company_id && $user->hasRole('editor');
    }
}
