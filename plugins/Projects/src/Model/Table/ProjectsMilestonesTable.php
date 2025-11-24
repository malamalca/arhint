<?php
declare(strict_types=1);

namespace Projects\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ProjectsMilestones Model
 *
 * @property \Projects\Model\Table\ProjectsTable&\Cake\ORM\Association\BelongsTo $Projects
 * @method \Projects\Model\Entity\ProjectsMilestone newEmptyEntity()
 * @method \Projects\Model\Entity\ProjectsMilestone newEntity(array $data, array $options = [])
 * @method array<\Projects\Model\Entity\ProjectsMilestone> newEntities(array $data, array $options = [])
 * @method \Projects\Model\Entity\ProjectsMilestone get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Projects\Model\Entity\ProjectsMilestone findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \Projects\Model\Entity\ProjectsMilestone patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\Projects\Model\Entity\ProjectsMilestone> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Projects\Model\Entity\ProjectsMilestone|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Projects\Model\Entity\ProjectsMilestone saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\Projects\Model\Entity\ProjectsMilestone>|\Cake\Datasource\ResultSetInterface<\Projects\Model\Entity\ProjectsMilestone>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\Projects\Model\Entity\ProjectsMilestone>|\Cake\Datasource\ResultSetInterface<\Projects\Model\Entity\ProjectsMilestone> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\Projects\Model\Entity\ProjectsMilestone>|\Cake\Datasource\ResultSetInterface<\Projects\Model\Entity\ProjectsMilestone>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\Projects\Model\Entity\ProjectsMilestone>|\Cake\Datasource\ResultSetInterface<\Projects\Model\Entity\ProjectsMilestone> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ProjectsMilestonesTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('projects_milestones');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Projects', [
            'foreignKey' => 'project_id',
            'className' => 'Projects.Projects',
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
            ->uuid('project_id')
            ->allowEmptyString('project_id');

        $validator
            ->scalar('title')
            ->allowEmptyString('title');

        $validator
            ->date('due')
            ->allowEmptyDate('due');

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
        $rules->add($rules->existsIn(['project_id'], 'Projects'), ['errorField' => 'project_id']);

        return $rules;
    }
}
