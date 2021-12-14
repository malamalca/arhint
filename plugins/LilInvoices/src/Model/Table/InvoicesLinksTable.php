<?php
declare(strict_types=1);

namespace LilInvoices\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Text;
use Cake\Validation\Validator;

/**
 * InvoicesLinks Model
 *
 * @method \LilInvoices\Model\Entity\InvoicesLink get($primaryKey, array $options = [])
 * @method \LilInvoices\Model\Entity\InvoicesLink newEmptyEntity()
 * @method \LilInvoices\Model\Entity\InvoicesLink patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 */
class InvoicesLinksTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('invoices_links');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Invoices', [
            'foreignKey' => 'invoice_id',
            'className' => 'LilInvoices\Model\Table\InvoicesTable',
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
            ->add('link_id', 'valid', ['rule' => 'uuid'])
            ->allowEmptyString('link_id')
            ->add('invoice_id', 'valid', ['rule' => 'uuid'])
            ->allowEmptyString('invoice_id');

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
        //$rules->add($rules->existsIn(['link_id'], 'Links'));
        $rules->add($rules->existsIn(['invoice_id'], 'Invoices'));

        return $rules;
    }

    /**
     * afterDelete method
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \LilInvoices\Model\Entity\InvoicesLink $entity Entity object.
     * @param \ArrayObject $options Array object.
     * @return void
     */
    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {
        $cnt = $this
            ->find()
            ->where(['link_id' => $entity->link_id])
            ->count();

        // only delete when there is only one invoice in a group of linked invoices
        if ($cnt == 1) {
            $this->deleteAll(['link_id' => $entity->link_id]);
        }
    }

    /**
     * Links two invoices
     *
     * @param string $id1 First invoice's id
     * @param string $id2 Second invoice's id
     * @return bool
     */
    public function two($id1, $id2)
    {
        $link_id = Text::uuid();
        $link = $this->newEntity(['invoice_id' => $id1, 'link_id' => $link_id]);
        $this->save($link);

        $link = $this->newEntity(['invoice_id' => $id2, 'link_id' => $link_id]);
        $this->save($link);

        return true;
    }

    /**
     * Fetch linked invoice for specified invoice's id.
     *
     * @param string $id Invoice id
     * @return \Cake\Datasource\ResultSetInterface|array
     */
    public function forInvoice($id)
    {
        $ret = [];
        $links = $this->find()
            ->where(['invoice_id' => $id])
            ->all()
            ->combine('link_id', 'invoice_id')
            ->toArray();

        if (!empty($links)) {
            $ret = $this->find()
                ->where(['invoice_id !=' => $id, 'link_id IN' => array_keys($links)])
                ->contain(['Invoices'])
                ->all();
        }

        return $ret;
    }
}
