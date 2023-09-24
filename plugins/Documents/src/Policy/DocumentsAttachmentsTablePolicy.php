<?php
declare(strict_types=1);

namespace Documents\Policy;

use App\Model\Entity\User;
use Cake\ORM\Query\SelectQuery;

/**
 * DocumentsAttachmentsTable Policy Resolver
 */
class DocumentsAttachmentsTablePolicy
{
    /**
     * Index scope
     *
     * @param \App\Model\Entity\User $user User
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function scopeIndex(User $user, SelectQuery $query): SelectQuery
    {
        return $query;
    }
}
