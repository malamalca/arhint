<?php
declare(strict_types=1);

namespace Documents\Policy;

/**
 * DocumentsAttachmentsTable Policy Resolver
 */
class DocumentsAttachmentsTablePolicy
{
    /**
     * Index scope
     *
     * @param \App\Model\Entity\User $user User
     * @param \Cake\ORM\Query $query Query object
     * @return \Cake\ORM\Query
     */
    public function scopeIndex($user, $query)
    {
        return $query;
    }
}
