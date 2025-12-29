<?php
declare(strict_types=1);

namespace Projects\Filter;

use App\Filter\Filter;
use App\Model\Entity\User;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class ProjectsWorkhoursFilter extends Filter
{
    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->allowEmptyString('status')
            ->add('status', 'inList', [
                'rule' => ['inList', ['open', 'closed']],
                'message' => __d('projects', 'Status can only be "open" or "closed".'),
            ]);

        return $validator;
    }

    /**
     * Get query parameters
     *
     * @param string $projectId Project ID
     * @param \App\Model\Entity\User $currentUser Current user
     * @return array<string,mixed>
     */
    public function getParams(User $currentUser): array
    {
        $ret = ['conditions' => []];

        $fields = $this->getFields();

        if (!empty($fields['fields']['project'])) {
            $ProjectsTable = TableRegistry::getTableLocator()->get('Projects.Projects');
            $matchingProjects = $ProjectsTable->find()
                ->select(['id'])
                ->where([
                    'title LIKE' => $fields['fields']['project'],
                ]);

            $ret['conditions'][]['ProjectsWorkhours.project_id IN'] = $matchingProjects;
        }

        if (!empty($fields['fields']['user'])) {
            $UsersTable = TableRegistry::getTableLocator()->get('App.Users');
            $matchingUsers = $UsersTable->find()
                ->select(['id'])
                ->where([
                    'company_id' => $currentUser->get('company_id'),
                    'name LIKE' => $fields['fields']['user'],
                ]);

            $ret['conditions'][]['ProjectsWorkhours.user_id IN'] = $matchingUsers;
        }

        if (!empty($fields['fields']['status'])) {
            if (strtolower($fields['fields']['status']) == 'closed') {
                $ret['conditions']['ProjectsWorkhours.dat_confirmed IS NOT'] = null;
            }
            if (strtolower($fields['fields']['status']) == 'open') {
                $ret['conditions']['ProjectsWorkhours.dat_confirmed IS'] = null;
            }
        }

        if (!empty($fields['fields']['sort'])) {
            switch (strtolower($fields['fields']['sort'])) {
                case 'created':
                    $ret['order'] = ['ProjectsWorkhours.created'];
                    break;
                case 'created-desc':
                    $ret['order'] = ['ProjectsWorkhours.created DESC'];
                    break;
                case 'updated':
                    $ret['order'] = ['ProjectsWorkhours.modified'];
                    break;
                case 'updated-desc':
                    $ret['order'] = ['ProjectsWorkhours.modified DESC'];
                    break;
            }
        }

        return $ret;
    }
}
