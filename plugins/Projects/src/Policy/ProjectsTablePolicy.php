<?php
declare(strict_types=1);

namespace Projects\Policy;

use App\Model\Entity\User;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\TableRegistry;

/**
 * ProjectsTable Policy Resolver
 */
class ProjectsTablePolicy
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
        $conditions = ['Projects.owner_id' => $user->company_id];
        if (!$user->hasRole('admin')) {
            /** @var \Projects\Model\Table\ProjectsUsersTable $ProjectsUsersTable */
            $ProjectsUsersTable = TableRegistry::getTableLocator()->get('Projects.ProjectsUsers');

            $projectsList = $ProjectsUsersTable->find()
                ->where(['user_id' => $user->id])
                ->all()
                ->combine('project_id', 'user_id')
                ->toArray();

            if (empty($projectsList)) {
                $conditions['Projects.id IS'] = null;
            } else {
                $conditions['Projects.id IN'] = array_keys($projectsList);
            }
        }

        return $query->where($conditions);
    }
}
