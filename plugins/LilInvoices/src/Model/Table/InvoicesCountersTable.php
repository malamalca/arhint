<?php
declare(strict_types=1);

namespace LilInvoices\Model\Table;

use Cake\Core\Configure;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * InvoicesCounters Model
 *
 * @method \LilInvoices\Model\Entity\InvoicesCounter get(string $id)
 * @method \LilInvoices\Model\Entity\InvoicesCounter newEmptyEntity()
 * @method \LilInvoices\Model\Entity\InvoicesCounter patchEntity($entity, array $data = [])
 */
class InvoicesCountersTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('invoices_counters');
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
            ->add('id', 'valid', ['rule' => 'uuid'])
            ->allowEmptyString('id', 'create')
            ->allowEmptyString('kind')
            ->add('counter', 'valid', ['rule' => 'numeric'])
            ->requirePresence('counter', 'create')
            ->notEmptyString('counter')
            ->allowEmptyString('title')
            ->allowEmptyString('mask')

            ->allowEmptyString('template_descript')
            ->allowEmptyString('tpl_title')
            ->allowEmptyString('tpl_header_id')
            ->allowEmptyString('tpl_body_id')
            ->allowEmptyString('tpl_footer_id')
            ->add('active', 'valid', ['rule' => 'boolean'])
            ->requirePresence('active', 'create')
            ->notEmptyString('active');

        return $validator;
    }

    /**
     * generateNo method
     *
     * @param mixed $id Counter id or counter data from which a new no is generated.
     * @return string|bool Generated invoice number or false on failure.
     */
    public function generateNo($id)
    {
        $ret = false;

        if (is_string($id)) {
            $data = $this->find()
                ->select(['id', 'counter', 'mask'])
                ->where(['id' => $id])
                ->first()
                ->toArray();
        } elseif (is_array($id)) {
            $data = $id;
        }

        if (isset($data['mask']) && isset($data['counter'])) {
            $ret = strtr(
                $data['mask'],
                [
                    '[[year]]' => strftime('%Y'),
                    '[[month]]' => strftime('%m'),
                    '[[no]]' => (int)$data['counter'] + 1,
                    '[[no.2]]' => str_pad((string)((int)$data['counter'] + 1), 2, '0', STR_PAD_LEFT),
                    '[[no.3]]' => str_pad((string)((int)$data['counter'] + 1), 3, '0', STR_PAD_LEFT),
                ]
            );
        }

        return $ret;
    }

    /**
     * findInvoicesList costum finder
     *
     * @param \Cake\ORM\Query $query Query object
     * @param array $options Options array
     * @return \Cake\ORM\Query
     */
    public function findInvoicesList(Query $query, array $options)
    {
        $invoicesTypes = (array)Configure::read('LilInvoices.invoiceDocTypes');

        $query->where(['doc_type IN' => $invoicesTypes]);

        $query->formatResults(function ($results) {
            return $results->combine('id', 'title');
        });

        return $query;
    }

    /**
     * findDefaultCounter method
     *
     * @param string $ownerId Counter type :: issued, received or archived.
     * @param array $counterType Filter data from $params['url']['filter'].
     * @return mixed Counter data or false on failure.
     */
    public function findDefaultCounter($ownerId, $counterType = [])
    {
        $params = ['order' => null, 'conditions' => []];

        // no counter specified; find first (or default) counter
        $params['conditions'] = ['owner_id' => $ownerId, 'active' => true];
        $params['order'] = ['active', 'kind DESC', 'title'];

        if (!empty($counterType)) {
            if (in_array($counterType, ['received', 'issued', 'other'])) {
                $params['conditions']['kind'] = $counterType;
            } elseif ($counterType == 'archived') {
                $params['conditions']['active'] = false;
            }
        }

        $ret = $this
            ->find()
            ->select()
            ->where($params['conditions'])
            ->order($params['order'])
            ->first();

        return $ret;
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
     * filter method
     *
     * @param array $filter Filter data.
     * @return array
     */
    public function filter(&$filter)
    {
        $ret = ['conditions' => [], 'contain' => []];

        if (isset($filter['type'])) {
            $ret['conditions']['InvoicesCounters.doc_type'] = $filter['type'];
        }

        if (!empty($filter['inactive'])) {
            $ret['conditions']['InvoicesCounters.active IN'] = [true, false];
        }

        if (!empty($filter['search'])) {
            $ret['conditions']['InvoicesCounters.title LIKE'] = '%' . $filter['search'] . '%';
        }

        return $ret;
    }
}
