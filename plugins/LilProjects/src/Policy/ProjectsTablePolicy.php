<?php
declare(strict_types=1);

namespace LilProjects\Policy;

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
     * @param \Cake\ORM\Query $query Query object
     * @return \Cake\ORM\Query
     */
    public function scopeIndex($user, $query)
    {
        $conditions = ['Projects.owner_id' => $user->company_id];
        if (!$user->hasRole('admin')) {
            /** @var \LilProjects\Model\Table\ProjectsUsersTable $ProjectsUsersTable */
            $ProjectsUsersTable = TableRegistry::getTableLocator()->get('LilProjects.ProjectsUsers');

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
