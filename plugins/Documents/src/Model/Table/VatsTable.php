<?php
declare(strict_types=1);

namespace Documents\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Vats Model
 *
 * @method \Documents\Model\Entity\Vat get($primaryKey, array $options = [])
 * @method \Documents\Model\Entity\Vat newEmptyEntity()
 * @method \Documents\Model\Entity\Vat patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 */
class VatsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('vats');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->hasMany('DocumentsItems', [
            'foreignKey' => 'vat_id',
            'className' => 'Documents\Model\Table\DocumentsItemsTable',
        ]);
        $this->hasMany('DocumentsTaxes', [
            'foreignKey' => 'vat_id',
            'className' => 'Documents\Model\Table\DocumentsTaxesTable',
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
    public function isOwnedBy($entityId, $ownerId)
    {
        return $this->exists(['id' => $entityId, 'owner_id' => $ownerId]);
    }

    /**
     * Fetch vat levels
     *
     * @param string $ownerId Company id.
     * @return array
     */
    public function levels($ownerId)
    {
        $vats = $this
            ->find()
            ->where(['owner_id' => $ownerId])
            ->all();

        $ret = [];
        foreach ($vats as $vat) {
            $ret[$vat->id] = $vat;
        }

        return $ret;
    }
}
