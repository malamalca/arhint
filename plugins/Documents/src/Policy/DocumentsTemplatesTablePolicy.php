<?php
declare(strict_types=1);

namespace Documents\Policy;

/**
 * DocumentsTemplatesTable Policy Resolver
 */
class DocumentsTemplatesTablePolicy
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
        return $query->where(['DocumentsTemplates.owner_id' => $user->company_id]);
    }
}
