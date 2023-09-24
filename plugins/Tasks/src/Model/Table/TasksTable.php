<?php
declare(strict_types=1);

namespace Tasks\Model\Table;

use Cake\I18n\DateTime;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Tasks Model
 *
 * @property \Cake\ORM\Association\BelongsTo $Owners
 * @property \Cake\ORM\Association\BelongsTo $Foreigns
 * @method \Tasks\Model\Entity\Task get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Tasks\Model\Entity\Task newEntity($data = null, array $options = [])
 * @method \Tasks\Model\Entity\Task newEmptyEntity(array $options = [])
 * @method \Tasks\Model\Entity\Task patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 */
class TasksTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config List of options for this table.
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
            'className' => 'Tasks.TasksFolders',
            'joinType' => 'INNER',
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
     * Checks if entity belongs to user.
     *
     * @param string $entityId Entity Id.
     * @param string $ownerId User Id.
     * @return bool
     */
    public function isOwnedBy(string $entityId, string $ownerId): bool
    {
        return $this->exists(['id' => $entityId, 'owner_id' => $ownerId]);
    }

    /**
     * Filters accounts by query string
     *
     * @param array<string, mixed> $filter Filter array.
     * @return array<string, mixed>
     */
    public function filter(array &$filter): array
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
                    $ret['conditions']['Tasks.deadline >='] = new DateTime('today');
                    $ret['conditions']['Tasks.deadline <'] = (new DateTime('today'))->addDays(1);
                    break;
                case 'tomorrow':
                    $ret['conditions']['Tasks.deadline >='] = new DateTime('tomorrow');
                    $ret['conditions']['Tasks.deadline <'] = (new DateTime('tomorrow'))->addDays(1);
                    break;
                case 'week':
                    $ret['conditions']['Tasks.deadline >='] = (new DateTime('today'))->startOfWeek();
                    $ret['conditions']['Tasks.deadline <'] = (new DateTime('today'))->startOfWeek()->addWeeks(1);
                    break;
                case 'morethan2days':
                    $ret['conditions']['Tasks.deadline >='] = (new DateTime('tomorrow'))->addDays(1);
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
                'Tasks.completed >=' => (new DateTime('today'))->subDays(8),
            ];
        }

        if (empty($filter['due']) && empty($filter['folder']) && empty($filter['completed'])) {
            $filter['all'] = true;
        }

        return $ret;
    }
}
