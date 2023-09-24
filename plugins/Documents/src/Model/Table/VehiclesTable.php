<?php
declare(strict_types=1);

namespace Documents\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Vehicles Model
 *
 * @method \Documents\Model\Entity\Vehicle newEmptyEntity()
 * @method \Documents\Model\Entity\Vehicle newEntity(array $data, array $options = [])
 * @method \Documents\Model\Entity\Vehicle[] newEntities(array $data, array $options = [])
 * @method \Documents\Model\Entity\Vehicle get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Documents\Model\Entity\Vehicle findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Documents\Model\Entity\Vehicle patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Documents\Model\Entity\Vehicle[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Documents\Model\Entity\Vehicle|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class VehiclesTable extends Table
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

        $this->setTable('vehicles');
        $this->setDisplayField('title');
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
            ->uuid('owner_id')
            ->allowEmptyString('owner_id');

        $validator
            ->scalar('title')
            ->maxLength('title', 200)
            ->notEmptyString('title');

        $validator
            ->scalar('registration')
            ->maxLength('registration', 200)
            ->notEmptyString('registration');

        $validator
            ->scalar('owner')
            ->maxLength('owner', 200)
            ->notEmptyString('owner');

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
     * List vehicles for specified owner.
     *
     * @param string $ownerId Users Company Id.
     * @param \Cake\ORM\Query\SelectQuery|null $query Query object.
     * @return array<\Documents\Model\Entity\Vehicle>
     */
    public function findForOwner(string $ownerId, ?SelectQuery $query = null): array
    {
        if (empty($query)) {
            $query = $this->find();
        }

        return $query
            ->order(['title'])
            ->all()
            ->combine('id', function ($entity) {
                return $entity;
            })
            ->toArray();
    }
}
