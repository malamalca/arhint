<?php
declare(strict_types=1);

namespace Documents\Model\Table;

use ArrayObject;
use Cake\Core\Plugin;
use Cake\Event\Event;
use Cake\I18n\FrozenDate;
use Cake\ORM\Entity;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

/**
 * Invoices Model
 *
 * @property \Documents\Model\Table\DocumentsCountersTable $DocumentsCounters
 * @property \Documents\Model\Table\DocumentsClientsTable|\Cake\ORM\Association\hasOne $Buyers
 * @property \Documents\Model\Table\DocumentsClientsTable|\Cake\ORM\Association\hasOne $Issuers
 * @property \Documents\Model\Table\DocumentsClientsTable|\Cake\ORM\Association\hasOne $Receivers
 * @method \Documents\Model\Entity\Invoice get($primaryKey, array $options = [])
 * @method \Documents\Model\Entity\Invoice newEmptyEntity()
 * @method \Documents\Model\Entity\Invoice patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 */
class InvoicesTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('invoices');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->hasOne('Buyers', [
            'className' => 'Documents\Model\Table\DocumentsClientsTable',
            'foreignKey' => 'document_id',
            'conditions' => ['Buyers.kind' => 'BY'],
            'dependent' => true,
        ]);
        $this->hasOne('Issuers', [
            'className' => 'Documents\Model\Table\DocumentsClientsTable',
            'foreignKey' => 'document_id',
            'conditions' => ['Issuers.kind' => 'II'],
            'dependent' => true,
        ]);
        $this->hasOne('Receivers', [
            'className' => 'Documents\Model\Table\DocumentsClientsTable',
            'foreignKey' => 'document_id',
            'conditions' => ['Receivers.kind' => 'IV'],
            'dependent' => true,
        ]);

        $this->belongsTo('Documents.DocumentsCounters', [
            'foreignKey' => 'counter_id',
        ]);
        $this->hasMany('Documents.InvoicesItems', [
            'foreignKey' => 'invoice_id',
            'dependant' => true,
            'saveStrategy' => 'replace',
        ]);
        $this->hasMany('Documents.InvoicesTaxes', [
            'foreignKey' => 'invoice_id',
            'dependant' => true,
            'saveStrategy' => 'replace',
        ]);
        $this->hasMany('Documents.DocumentsLinks', [
            'foreignKey' => 'document_id',
            'dependant' => true,
        ]);
        $this->hasMany('Documents.DocumentsAttachments', [
            'foreignKey' => 'document_id',
            'dependant' => true,
        ]);

        $this->belongsTo('TplHeaders', [
            'foreignKey' => 'tpl_header_id',
            'className' => 'Documents\Model\Table\DocumentsTemplatesTable',
        ]);
        $this->belongsTo('TplBodies', [
            'foreignKey' => 'tpl_body_id',
            'className' => 'Documents\Model\Table\DocumentsTemplatesTable',
        ]);
        $this->belongsTo('TplFooters', [
            'foreignKey' => 'tpl_footer_id',
            'className' => 'Documents\Model\Table\DocumentsTemplatesTable',
        ]);

        if (Plugin::isLoaded('Projects')) {
            $this->belongsTo('Projects', [
                'foreignKey' => 'project_id',
                'className' => 'Projects\Model\Table\ProjectsTable',
            ]);
        }
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
            ->allowEmptyString('id', 'create')

            ->allowEmptyString('contact_id')
            ->add('counter_id', 'valid', ['rule' => 'uuid'])

            ->requirePresence('counter_id', 'create')
            ->notEmptyString('counter_id')
            //->add('attachments_count', 'valid', ['rule' => 'numeric'])
            //->requirePresence('attachments_count', 'create')
            //->notEmptyString('attachments_count')
            ->add('counter', 'valid', ['rule' => 'numeric'])
            ->allowEmptyString('counter')
            ->allowEmptyString('no')

            ->notEmptyString('title')

            ->allowEmptyString('descript')

            ->add('dat_issue', 'valid', ['rule' => 'date'])
            ->notEmptyString('dat_issue')
            ->add('dat_service', 'valid', ['rule' => 'date'])
            ->notEmptyString('dat_service')
            ->add('dat_expire', 'valid', ['rule' => 'date'])
            ->notEmptyString('dat_expire')
            ->add('dat_approval', 'valid', ['rule' => 'date'])
            ->allowEmptyString('dat_approval')

            ->add('net_total', 'valid', ['rule' => 'decimal'])
            ->allowEmptyString('net_total')
            ->add('total', 'valid', ['rule' => 'decimal'])
            ->allowEmptyString('total')

            ->allowEmptyString('pmt_type')
            ->allowEmptyString('pmt_module')
            ->allowEmptyString('pmt_ref');

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
        //$rules->add($rules->existsIn(['contact_id'], 'Clients'), 'exists', ['errorField' => 'contact_id']);
        $rules->add($rules->existsIn(['counter_id'], 'DocumentsCounters'));

        return $rules;
    }

    /**
     * beforeSave method
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \Documents\Model\Entity\Invoice $invoice Entity object.
     * @param \ArrayObject $options Array object.
     * @return bool
     */
    public function beforeSave(Event $event, Entity $invoice, ArrayObject $options)
    {
        if ($invoice->isDirty('invoices_taxes')) {
            $invoice->net_total = 0;
            foreach ((array)$invoice->invoices_taxes as $tax) {
                $invoice->net_total += $tax->base;
            }
        }
        if ($invoice->isDirty('invoices_items')) {
            $invoice->net_total = 0;
            $invoice->total = 0;
            foreach ($invoice->invoices_items as $item) {
                $invoice->net_total += $item->net_total;
                $invoice->total += $item->total;
            }
        }

        return true;
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

        if (isset($filter['counter'])) {
            $ret['conditions']['Invoices.counter_id'] = $filter['counter'];
        }

        // from-to date
        if (isset($filter['start'])) {
            $filter['start'] = FrozenDate::parseDate($filter['start'], 'yyyy-MM-dd');
            if (!empty($filter['start'])) {
                $ret['conditions']['Invoices.dat_issue >='] = $filter['start'];
            }
        }

        if (isset($filter['end'])) {
            $filter['end'] = FrozenDate::parseDate($filter['end'], 'yyyy-MM-dd');
            if (!empty($filter['end'])) {
                $ret['conditions']['Invoices.dat_issue <='] = $filter['end'];
            }
        }

        if (isset($filter['month'])) {
            $start = FrozenDate::parseDate($filter['month'] . '-01', 'yyyy-MM-dd');
            if (!empty($start)) {
                $ret['conditions']['Invoices.dat_issue >='] = $start;
                $ret['conditions']['Invoices.dat_issue <'] = $start->addMonth();
                $filter['start'] = $start;
                $filter['end'] = $start->addMonth()->subDays(1);
            }
        }

        if (!empty($filter['expired'])) {
            $expired = FrozenDate::parseDate($filter['expired'] . '-01', 'yyyy-MM-dd');
            if (!empty($expired)) {
                $ret['conditions']['Invoices.dat_expire <='] = $expired;
            }
        }

        // override all conditions if Document.id is set
        if (!empty($filter['document'])) {
            $ret['conditions'] = ['Invoices.id' => $filter['document']];
        }
        if (!empty($filter['id'])) {
            $ret['conditions'] = ['Invoices.id' => $filter['id']];
        }

        // manual search
        if (!empty($filter['search']) && ($filter['search'] != '[[search]]')) {
            if (substr($filter['search'], 0, 1) == '#') {
                $ret['conditions'][] = ['Invoices.counter' => substr($filter['search'], 1)];
            } else {
                $ret['conditions'][] = ['OR' => [
                    'Invoices.no LIKE' => '%' . $filter['search'] . '%',
                    'Invoices.title LIKE' => '%' . $filter['search'] . '%',
                    'Client.title LIKE' => '%' . $filter['search'] . '%',
                ]];
            }
        }

        if (!empty($filter['contact_id'])) {
            $matchingDocuments = TableRegistry::getTableLocator()->get('Documents.DocumentsClients')->query()
                ->select(['document_id'])
                ->distinct()
                ->where(['contact_id' => $filter['contact_id']]);
                $ret['conditions'][]['Invoices.id IN'] = $matchingDocuments;
        }

        if (!empty($filter['project'])) {
            $ret['conditions'][]['Invoices.project_id'] = $filter['project'];
        }

        $ret['contain'] = ['Issuers', 'Receivers', 'Buyers'];

        if (isset($filter['sort'])) {
            $ret['order'] = [];
        } else {
            $ret['order'] = $filter['order'] ?? [];
        }

        if (isset($filter['limit'])) {
            $ret['limit'] = $filter['limit'];
        } else {
            $ret['limit'] = null;
        }

        return $ret;
    }

    /**
     * maxSpan method
     *
     * Method returns array
     *
     * @param string $counterId Counter id
     * @return array
     */
    public function maxSpan($counterId)
    {
        $ret = [];

        $query = $this->find();
        $query
            ->select([
                'start' => $query->func()->min('Invoices.dat_issue', ['string']),
                'end' => $query->func()->max('Invoices.dat_issue', ['string']),
            ])
            ->where(['Invoices.counter_id' => $counterId]);
        $ret = $query->first()->toArray();

        if (empty($ret['start'])) {
            $ret['start'] = new FrozenDate();
        } else {
            $ret['start'] = FrozenDate::parseDate($ret['start'], 'yyyy-MM-dd');
        }
        if (empty($ret['end'])) {
            $ret['end'] = new FrozenDate();
        } else {
            $ret['end'] = FrozenDate::parseDate($ret['end'], 'yyyy-MM-dd');
        }

        return $ret;
    }

    /**
     * parseRequest method
     *
     * @param \Cake\Http\ServerRequest $request Request object
     * @param string|null $id Document id.
     * @return \Documents\Model\Entity\Invoice
     */
    public function parseRequest($request, $id = null)
    {
        if (!empty($id)) {
            $invoice = $this->get($id, ['contain' => ['Issuers', 'Buyers', 'Receivers',
                'InvoicesTaxes', 'InvoicesItems', 'DocumentsCounters']]);
        } else {
            /** @var \Documents\Model\Table\DocumentsClientsTable $DocumentsClients */
            $DocumentsClients = TableRegistry::getTableLocator()->get('Documents.DocumentsClients');
            /** @var \Documents\Model\Table\DocumentsCountersTable $DocumentsCounters */
            $DocumentsCounters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');

            $sourceId = $request->getQuery('duplicate');
            if (!empty($sourceId)) {
                // clone
                $invoice = $this->get($sourceId, ['contain' => ['Issuers', 'Buyers', 'Receivers',
                    'InvoicesTaxes', 'InvoicesItems', 'DocumentsCounters']]);

                $invoice->setNew(true);
                unset($invoice->id);

                foreach ($invoice->invoices_items as &$item) {
                    $item->setNew(true);
                    unset($item->id);
                    unset($item->document_id);
                }

                foreach ($invoice->invoices_taxes as &$tax) {
                    $tax->setNew(true);
                    unset($tax->id);
                    unset($tax->document_id);
                }

                $invoice->issuer->setNew(true);
                unset($invoice->issuer->id);
                unset($invoice->issuer->document_id);

                $invoice->buyer->setNew(true);
                unset($invoice->buyer->id);
                unset($invoice->buyer->document_id);

                $invoice->receiver->setNew(true);
                unset($invoice->receiver->id);
                unset($invoice->receiver->document_id);

                $counterId = $request->getQuery('counter', $invoice->counter_id);

                $invoice->documents_counter = $DocumentsCounters->get($counterId);

                $invoice->counter_id = $invoice->documents_counter->id;
                $invoice->doc_type = $invoice->documents_counter->doc_type;
            } else {
                // new entity
                $invoice = $this->newEmptyEntity();

                $invoice->owner_id = $request->getAttribute('identity')->get('company_id');

                $invoice->issuer = $DocumentsClients->newEntity(['kind' => 'II']);
                $invoice->receiver = $DocumentsClients->newEntity(['kind' => 'IV']);
                $invoice->buyer = $DocumentsClients->newEntity(['kind' => 'BY']);

                $counterId = $request->getQuery('counter');
                if (empty($counterId)) {
                    $counterId = $request->getData('counter_id');
                }

                $invoice->documents_counter = $DocumentsCounters->get($counterId);

                $invoice->counter_id = $invoice->documents_counter->id;
                $invoice->doc_type = $invoice->documents_counter->doc_type;

                switch ($invoice->documents_counter->direction) {
                    case 'issued':
                        $invoice->issuer->patchWithAuth($request->getAttribute('identity'));
                        break;
                    case 'received':
                        $invoice->receiver->patchWithAuth($request->getAttribute('identity'));
                        $invoice->buyer->patchWithAuth($request->getAttribute('identity'));
                        break;
                }
            }

            $invoice->no = $DocumentsCounters->generateNo($invoice->counter_id);
        }

        return $invoice;
    }
}
