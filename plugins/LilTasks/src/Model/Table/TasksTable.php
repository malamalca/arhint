<?php
declare(strict_types=1);

namespace LilTasks\Model\Table;

use Cake\I18n\Time;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Tasks Model
 *
 * @property \Cake\ORM\Association\BelongsTo $Owners
 * @property \Cake\ORM\Association\BelongsTo $Foreigns
 * @method \LilTasks\Model\Entity\Task get(string $id, array $data = [])
 * @method \LilTasks\Model\Entity\Task newEntity($data = null, array $options = [])
 * @method \LilTasks\Model\Entity\Task newEmptyEntity(array $options = [])
 * @method \LilTasks\Model\Entity\Task patchEntity(\LilTasks\Model\Entity\Task $entity, array $data = [])
 */
class TasksTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('tasks');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('TasksFolders', [
            'foreignKey' => 'folder_id',
            'className' => 'LilTasks.TasksFolders',
            'type' => 'INNER',
        ]);
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
            ->add('id', 'valid', ['rule' => 'uuid'])
            ->allowEmptyString('id', 'create');

        $validator
            ->allowEmptyString('model');

        $validator
            ->notEmptyString('title');

        $validator
            ->allowEmptyString('descript');

        $validator
            ->add('started', 'valid', ['rule' => 'date'])
            ->allowEmptyString('started');

        $validator
            ->add('deadline', 'valid', ['rule' => 'date'])
            ->allowEmptyString('deadline');

        $validator
            ->add('completed', 'valid', ['rule' => 'datetime'])
            ->allowEmptyString('completed');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        return $rules;
    }

    /**
     * Checks if entity belongs to user.
     *
     * @param string $entityId Entity Id.
     * @param string $ownerId User Id.
     * @return bool
     */
    public function isOwnedBy($entityId, $ownerId)
    {
        return $this->exists(['id' => $entityId, 'owner_id' => $ownerId]);
    }

    /**
     * Filters accounts by query string
     *
     * @param array $filter Filter array.
     * @return array
     */
    public function filter(&$filter)
    {
        $ret = [];

        if (!empty($filter['folder'])) {
            $ret['conditions']['Tasks.folder_id'] = $filter['folder'];
        }

        if (!empty($filter['user'])) {
            $ret['conditions'][]['OR'] = [
                'Tasks.tasker_id IS' => null,
                'Tasks.tasker_id' => $filter['user'],
                'Tasks.user_id' => $filter['user'],
            ];
        }

        if (!empty($filter['due'])) {
            switch ($filter['due']) {
                case 'today':
                    $ret['conditions']['Tasks.deadline >='] = new Time('today');
                    $ret['conditions']['Tasks.deadline <'] = (new Time('today'))->addDay();
                    break;
                case 'tomorrow':
                    $ret['conditions']['Tasks.deadline >='] = new Time('tomorrow');
                    $ret['conditions']['Tasks.deadline <'] = (new Time('tomorrow'))->addDay();
                    break;
                case 'week':
                    $ret['conditions']['Tasks.deadline >='] = (new Time('today'))->startOfWeek();
                    $ret['conditions']['Tasks.deadline <'] = (new Time('today'))->startOfWeek()->addWeek();
                    break;
                case 'morethan2days':
                    $ret['conditions']['Tasks.deadline >='] = (new Time('tomorrow'))->addDay();
                    break;
                case 'empty':
                    $ret['conditions']['Tasks.deadline IS'] = null;
                    break;
            }
        }

        if (!empty($filter['completed'])) {
            switch ($filter['completed']) {
                case 'only':
                    $ret['conditions']['Tasks.completed IS NOT'] = null;
                    break;
                case 'notyet':
                    $ret['conditions']['Tasks.completed IS'] = null;
                    break;
            }
        } else {
            $ret['conditions'][]['OR'] = [
                'Tasks.completed IS' => null,
                'Tasks.completed >=' => (new Time('today'))->subDays(8),
            ];
        }

        if (empty($filter['due']) && empty($filter['folder']) && empty($filter['completed'])) {
            $filter['all'] = true;
        }

        return $ret;
    }
}
