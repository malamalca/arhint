<?php
declare(strict_types=1);

namespace Tasks\Model\Table;

use Cake\Datasource\ResultSetInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * TasksFolders Model
 *
 * @property \Cake\ORM\Association\BelongsTo $Owners
 * @method \Tasks\Model\Entity\TasksFolder get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Tasks\Model\Entity\TasksFolder newEntity($data = null, array $options = [])
 * @method \Tasks\Model\Entity\TasksFolder newEmptyEntity(array $options = [])
 * @method \Tasks\Model\Entity\TasksFolder patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 */
class TasksFoldersTable extends Table
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

        $this->setTable('tasks_folders');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('Tasks', [
            'foreignKey' => 'folder_id',
            'className' => 'Tasks.Tasks',
            'dependent' => true,
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
            ->notEmptyString('title');

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

        return $ret;
    }

    /**
     * Returns list of accounts for specified owner
     *
     * @param string $ownerId Company Id.
     * @return \Cake\Datasource\ResultSetInterface
     */
    public function findForOwner(string $ownerId): ResultSetInterface
    {
        $conditions = ['TasksFolders.owner_id' => $ownerId];
        $ret = $this->find()->where($conditions)->all();

        return $ret;
    }

    /**
     * Returns list of accounts for specified owner
     *
     * @param string $ownerId Company Id.
     * @return array<\Tasks\Model\Entity\TasksFolder>
     */
    public function listForOwner(string $ownerId): array
    {
        $conditions = ['TasksFolders.owner_id' => $ownerId];
        $ret = $this->find()
            ->where($conditions)
            ->all()
            ->combine('id', function ($entity) {
                return $entity;
            })
            ->toArray();

        return $ret;
    }
}
