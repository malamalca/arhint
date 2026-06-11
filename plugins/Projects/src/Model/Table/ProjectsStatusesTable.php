<?php
declare(strict_types=1);

namespace Projects\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ProjectsStatuses Model
 *
 * @property \Projects\Model\Table\ProjectsTable&\Cake\ORM\Association\HasMany $Projects
 * @method \Projects\Model\Entity\ProjectsStatus newEmptyEntity()
 * @method \Projects\Model\Entity\ProjectsStatus newEntity(array $data, array $options = [])
 * @method \Projects\Model\Entity\ProjectsStatus[] newEntities(array $data, array $options = [])
 * @method \Projects\Model\Entity\ProjectsStatus get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Projects\Model\Entity\ProjectsStatus findOrCreate($search, array<array-key, mixed>|callable|null $callback = null, $options = [])
 * @method \Projects\Model\Entity\ProjectsStatus patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Projects\Model\Entity\ProjectsStatus[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Projects\Model\Entity\ProjectsStatus|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 */
class ProjectsStatusesTable extends Table
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

        $this->setTable('projects_statuses');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->hasMany('Projects', [
            'foreignKey' => 'status_id',
            'className' => 'Projects.Projects',
        ]);

        $this->belongsTo('Companies', [
            'foreignKey' => 'owner_id',
            'className' => 'Crm.Contacts',
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
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('title')
            ->maxLength('title', 255)
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
        $rules->add($rules->existsIn(['owner_id'], 'Companies'));

        $rules->addDelete(function ($entity, $options) {
            $projectsCount = $this->Projects->find()
                ->where(['Projects.status_id' => $entity->id])
                ->count();

            return $projectsCount == 0;
        }, 'usedInProject');

        return $rules;
    }
}
