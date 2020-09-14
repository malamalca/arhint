<?php
declare(strict_types=1);

namespace LilTasks\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * TasksFolders Model
 *
 * @property \Cake\ORM\Association\BelongsTo $Owners
 * @method \LilTasks\Model\Entity\TasksFolder get($primaryKey, $options = [])
 * @method \LilTasks\Model\Entity\TasksFolder newEntity($data = null, array $options = [])
 * @method \LilTasks\Model\Entity\TasksFolder newEmptyEntity(array $options = [])
 */
class TasksFoldersTable extends Table
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

        $this->setTable('tasks_folders');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('Tasks', [
            'foreignKey' => 'folder_id',
            'className' => 'LilTasks.Tasks',
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

        return $ret;
    }

    /**
     * Returns list of accounts for specified owner
     *
     * @param string $ownerId Company Id.
     * @return \Cake\Datasource\ResultSetInterface
     */
    public function findForOwner($ownerId)
    {
        $conditions = ['TasksFolders.owner_id' => $ownerId];
        $ret = $this->find()->where($conditions)->all();

        return $ret;
    }

    /**
     * Returns list of accounts for specified owner
     *
     * @param string $ownerId Company Id.
     * @return array
     */
    public function listForOwner($ownerId)
    {
        $conditions = ['TasksFolders.owner_id' => $ownerId];
        $ret = $this->find('list')->where($conditions)->toArray();

        return $ret;
    }
}
