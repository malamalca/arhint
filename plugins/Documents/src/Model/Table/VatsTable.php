<?php
declare(strict_types=1);

namespace Documents\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Vats Model
 *
 * @method \Documents\Model\Entity\Vat get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Documents\Model\Entity\Vat newEmptyEntity()
 * @method \Documents\Model\Entity\Vat patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 */
class VatsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config List of options for this table.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('vats');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->hasMany('InvoicesTaxes', [
            'foreignKey' => 'vat_id',
            'className' => 'Documents\Model\Table\InvoicesTaxesTable',
        ]);
        $this->hasMany('Items', [
            'foreignKey' => 'vat_id',
            'className' => 'Documents\Model\Table\ItemsTable',
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
            ->allowEmptyString('id', 'create')
            ->notEmptyString('descript')
            ->add('percent', 'valid', ['rule' => 'decimal'])
            ->requirePresence('percent', 'create')
            ->notEmptyString('percent');

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
     * Fetch vat levels
     *
     * @param string $ownerId Company id.
     * @return array<string, \Documents\Model\Entity\Vat>
     */
    public function levels(string $ownerId): array
    {
        $vats = $this
            ->find()
            ->where(['owner_id' => $ownerId])
            ->all()
            ->combine('id', fn ($entity) => $entity)
            ->toArray();

        return $vats;
    }
}
