<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * DashboardNotes Model
 *
 * @method \App\Model\Entity\DashboardNote get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\DashboardNote newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\DashboardNote newEmptyEntity()
 * @method \App\Model\Entity\DashboardNote[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\DashboardNote|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\DashboardNote saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\DashboardNote patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\DashboardNote[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\DashboardNote findOrCreate($search, array<array-key, mixed>|callable|null $callback = null, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class DashboardNotesTable extends Table
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

        $this->setTable('dashboard_notes');
        $this->setDisplayField('note');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
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
            ->scalar('note')
            ->notEmptyString('note');

        return $validator;
    }
}
