<?php
declare(strict_types=1);

namespace Projects\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ProjectsMaterials Model
 *
 * @method \Projects\Model\Entity\ProjectsMaterial newEmptyEntity()
 * @method \Projects\Model\Entity\ProjectsMaterial newEntity(array $data, array $options = [])
 * @method \Projects\Model\Entity\ProjectsMaterial[] newEntities(array $data, array $options = [])
 * @method \Projects\Model\Entity\ProjectsMaterial get($primaryKey, $options = [])
 * @method \Projects\Model\Entity\ProjectsMaterial findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Projects\Model\Entity\ProjectsMaterial patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Projects\Model\Entity\ProjectsMaterial[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Projects\Model\Entity\ProjectsMaterial|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Projects\Model\Entity\ProjectsMaterial saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Projects\Model\Entity\ProjectsMaterial[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Projects\Model\Entity\ProjectsMaterial[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Projects\Model\Entity\ProjectsMaterial[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Projects\Model\Entity\ProjectsMaterial[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class ProjectsMaterialsTable extends Table
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

        $this->setTable('projects_materials');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
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
            ->uuid('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('descript')
            ->allowEmptyString('descript');

        $validator
            ->decimal('thickness')
            ->notEmptyString('thickness');

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
}
