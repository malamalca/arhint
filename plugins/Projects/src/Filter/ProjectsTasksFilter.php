<?php
declare(strict_types=1);

namespace Projects\Filter;

use App\Filter\Filter;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class ProjectsTasksFilter extends Filter
{
    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        $this->addField('status');
    }

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
                'message' => 'Status can only be "open" or "closed".',
            ]);

        return $validator;
    }

    /**
     * Get query parameters
     *
     * @return array<string,mixed>
     */
    public function getParams(): array
    {
        $ret = ['conditions' => []];

        $fields = $this->getFields();

        if (!empty($fields['fields']['milestone'])) {
            $MilestonesTable = TableRegistry::getTableLocator()->get('Projects.ProjectsMilestones');
            $matchingMilestones = $MilestonesTable->find()
                ->select(['id'])
                ->distinct()
                ->where(['title LIKE' => $fields['fields']['milestone']]);

            $ret['conditions'][]['ProjectsTasks.milestone_id IN'] = $matchingMilestones;
        }

        if (!empty($fields['fields']['user'])) {
            $ret['conditions'][]['ProjectsTasks.user_id IN'] = (array)$fields['fields']['user'];
        }

        if (!empty($fields['fields']['status'])) {
            if (strtolower($fields['fields']['status']) == 'closed') {
                $ret['conditions']['ProjectsTasks.date_complete IS NOT'] = null;
            }
            if (strtolower($fields['fields']['status']) == 'open') {
                $ret['conditions']['ProjectsTasks.date_complete IS'] = null;
            }
        }

        if (!empty($fields['fields']['sort'])) {
            switch (strtolower($fields['fields']['sort'])) {
                case 'created':
                    $ret['order'] = ['ProjectsTasks.created'];
                    break;
                case 'created-desc':
                    $ret['order'] = ['ProjectsTasks.created DESC'];
                    break;
                case 'updated':
                    $ret['order'] = ['ProjectsTasks.modified'];
                    break;
                case 'updated-desc':
                    $ret['order'] = ['ProjectsTasks.modified DESC'];
                    break;
            }
        }

        return $ret;
    }
}
