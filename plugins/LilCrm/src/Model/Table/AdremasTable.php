<?php
declare(strict_types=1);

namespace LilCrm\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

/**
 * Adremas Model
 *
 * @method \LilCrm\Model\Entity\Adrema newEmptyEntity()
 * @method \LilCrm\Model\Entity\Adrema newEntity(array $data)
 */
class AdremasTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
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
            'className' => 'LilCrm\Model\Table\ContactsTable',
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
     * @param string $entityId Entity Id.
     * @param string $ownerId User Id.
     * @return bool
     */
    public function isOwnedBy($entityId, $ownerId)
    {
        return $this->exists(['id' => $entityId, 'owner_id' => $ownerId]);
    }

    /**
     * copyAddresses method
     *
     * @param string $sourceId Source's adrema id.
     * @param string $destId Destination's adrema id.
     * @return void
     */
    public function copyAddresses($sourceId, $destId)
    {
        $AdremasContacts = TableRegistry::get('LilCrm.AdremasContacts');

        $addresses = $AdremasContacts
            ->find()
            ->where(['adrema_id' => $sourceId])
            ->all();

        foreach ($addresses as $address) {
            /** @var \LilCrm\Model\Entity\AdremasContact $new */
            $new = $AdremasContacts->newEmptyEntity();
            $AdremasContacts->patchEntity($new, $address->toArray());

            $new->adrema_id = $destId;

            $AdremasContacts->save($new);
        }
    }
}
