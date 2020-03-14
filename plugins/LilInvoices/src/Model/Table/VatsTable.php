<?php
declare(strict_types=1);

namespace LilInvoices\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Vats Model
 *
 * @method \LilInvoices\Model\Entity\Vat get(string $id)
 * @method \LilInvoices\Model\Entity\Vat newEmptyEntity()
 * @method \LilInvoices\Model\Entity\Vat patchEntity($entity, array $data = [])
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
        $this->hasMany('InvoicesItems', [
            'foreignKey' => 'vat_id',
            'className' => 'LilInvoices\Model\Table\InvoicesItemsTable',
        ]);
        $this->hasMany('InvoicesTaxes', [
            'foreignKey' => 'vat_id',
            'className' => 'LilInvoices\Model\Table\InvoicesTaxesTable',
        ]);
        $this->hasMany('Items', [
            'foreignKey' => 'vat_id',
            'className' => 'LilInvoices\Model\Table\ItemsTable',
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
