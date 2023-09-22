<?php
declare(strict_types=1);

namespace Crm\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

/**
 * Adremas Model
 *
 * @method \Crm\Model\Entity\Adrema newEmptyEntity()
 * @method \Crm\Model\Entity\Adrema newEntity(array $data, array $options = [])
 * @method \Crm\Model\Entity\Adrema get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Crm\Model\Entity\Adrema patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 */
class AdremasTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config List of options for this table.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('adremas');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsToMany('Contacts', [
            'foreignKey' => 'adrema_id',
            'targetForeignKey' => 'contact_id',
            'joinTable' => 'adremas_contacts',
            'className' => 'Crm\Model\Table\ContactsTable',
            'dependant' => true,
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
            ->allowEmptyString('title');

        return $validator;
    }

    /**
     * Checks if entity belongs to user.
     *
     * @param string|null $entityId Entity Id.
     * @param string|null $ownerId User Id.
     * @return bool
     */
    public function isOwnedBy(?string $entityId, ?string $ownerId): bool
    {
        return !empty($entityId) && !empty($ownerId) && $this->exists(['id' => $entityId, 'owner_id' => $ownerId]);
    }

    /**
     * copyAddresses method
     *
     * @param string $sourceId Source's adrema id.
     * @param string $destId Destination's adrema id.
     * @return void
     */
    public function copyAddresses(string $sourceId, string $destId): void
    {
        $AdremasContacts = TableRegistry::getTableLocator()->get('Crm.AdremasContacts');

        $addresses = $AdremasContacts
            ->find()
            ->where(['adrema_id' => $sourceId])
            ->all();

        foreach ($addresses as $address) {
            /** @var \Crm\Model\Entity\AdremasContact $new */
            $new = $AdremasContacts->newEmptyEntity();
            $AdremasContacts->patchEntity($new, $address->toArray());

            $new->adrema_id = $destId;

            $AdremasContacts->save($new);
        }
    }
}
