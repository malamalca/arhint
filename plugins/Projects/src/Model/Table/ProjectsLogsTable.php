<?php
declare(strict_types=1);

namespace Projects\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ProjectsLogs Model
 *
 * @property \Projects\Model\Table\ProjectsTable|\Cake\ORM\Association\BelongsTo $Projects
 * @property \Projects\Model\Table\UsersTable|\Cake\ORM\Association\BelongsTo $Users
 * @method \Projects\Model\Entity\ProjectsLog get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Projects\Model\Entity\ProjectsLog newEntity($data = null, array $options = [])
 * @method \Projects\Model\Entity\ProjectsLog newEmptyEntity(array $options = [])
 * @method \Projects\Model\Entity\ProjectsLog[] newEntities(array $data, array $options = [])
 * @method \Projects\Model\Entity\ProjectsLog|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Projects\Model\Entity\ProjectsLog saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Projects\Model\Entity\ProjectsLog patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Projects\Model\Entity\ProjectsLog[] patchEntities($entities, array $data, array $options = [])
 * @method \Projects\Model\Entity\ProjectsLog findOrCreate($search, array<array-key, mixed>|callable|null $callback = null, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ProjectsLogsTable extends Table
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

        $this->setTable('projects_logs');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Projects', [
            'foreignKey' => 'project_id',
            'className' => 'Projects.Projects',
        ]);
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'className' => 'Lil.Users',
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
            ->uuid('id')
            ->allowEmptyString('id', 'create');

        $validator
            ->scalar('descript')
            ->allowEmptyString('descript');

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
        $rules->add($rules->existsIn(['project_id'], 'Projects'));
        $rules->add($rules->existsIn(['user_id'], 'Users'));

        return $rules;
    }
}
