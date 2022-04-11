<?php
declare(strict_types=1);

namespace Documents\Model\Table;

use Cake\Core\Plugin;
use Cake\I18n\FrozenDate;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

/**
 * Documents Model
 *
 * @property \Documents\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \Documents\Model\Table\CountersTable&\Cake\ORM\Association\BelongsTo $Counters
 * @property \Documents\Model\Table\ProjectsTable&\Cake\ORM\Association\BelongsTo $Projects
 * @property \Documents\Model\Table\DocumentsAttachmentsTable&\Cake\ORM\Association\HasMany $DocumentsAttachments
 * @method \Documents\Model\Entity\Document newEmptyEntity()
 * @method \Documents\Model\Entity\Document newEntity(array $data, array $options = [])
 * @method \Documents\Model\Entity\Document[] newEntities(array $data, array $options = [])
 * @method \Documents\Model\Entity\Document get($primaryKey, $options = [])
 * @method \Documents\Model\Entity\Document findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Documents\Model\Entity\Document patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Documents\Model\Entity\Document[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Documents\Model\Entity\Document|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Documents\Model\Entity\Document saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Documents\Model\Entity\Document[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Documents\Model\Entity\Document[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Documents\Model\Entity\Document[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Documents\Model\Entity\Document[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class DocumentsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('documents');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('DocumentsCounters', [
            'foreignKey' => 'counter_id',
            'className' => 'Documents\Model\Table\DocumentsCountersTable',
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
            ->uuid('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->integer('counter')
            ->allowEmptyString('counter');

        $validator
            ->scalar('no')
            ->maxLength('no', 50)
            ->allowEmptyString('no');

        $validator
            ->date('dat_issue')
            ->allowEmptyDate('dat_issue');

        $validator
            ->scalar('title')
            ->maxLength('title', 200)
            ->allowEmptyString('title');

        $validator
            ->scalar('descript')
            ->allowEmptyString('descript');

        $validator
            ->scalar('location')
            ->maxLength('location', 70)
            ->allowEmptyString('location');

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
        return $rules;
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
            $ret['conditions']['Documents.counter_id'] = $filter['counter'];
        }

        // from-to date
        if (isset($filter['start'])) {
            $filter['start'] = FrozenDate::parseDate($filter['start'], 'yyyy-MM-dd');
            if (!empty($filter['start'])) {
                $ret['conditions']['Documents.dat_issue >='] = $filter['start'];
            }
        }

        if (isset($filter['end'])) {
            $filter['end'] = FrozenDate::parseDate($filter['end'], 'yyyy-MM-dd');
            if (!empty($filter['end'])) {
                $ret['conditions']['Documents.dat_issue <='] = $filter['end'];
            }
        }

        if (isset($filter['month'])) {
            $start = FrozenDate::parseDate($filter['month'] . '-01', 'yyyy-MM-dd');
            if (!empty($start)) {
                $ret['conditions']['Documents.dat_issue >='] = $start;
                $ret['conditions']['Documents.dat_issue <'] = $start->addMonth();
                $filter['start'] = $start;
                $filter['end'] = $start->addMonth()->subDays(1);
            }
        }

        // override all conditions if Document.id is set
        if (!empty($filter['id'])) {
            $ret['conditions'] = ['Documents.id' => $filter['id']];
        }

        // manual search
        if (!empty($filter['search']) && ($filter['search'] != '[[search]]')) {
            if (substr($filter['search'], 0, 1) == '#') {
                $ret['conditions'][] = ['Documents.counter' => substr($filter['search'], 1)];
            } else {
                $ret['conditions'][] = ['OR' => [
                    'Documents.no LIKE' => '%' . $filter['search'] . '%',
                    'Documents.location LIKE' => '%' . $filter['search'] . '%',
                    //'Client.title LIKE' => '%' . $filter['search'] . '%',
                ]];
            }
        }

        $ret['contain'] = [];

        if (isset($filter['sort'])) {
            $ret['order'] = [];
        } else {
            $ret['order'] = $filter['order'] ?? [];
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
                'start' => $query->func()->min('Documents.dat_issue', ['string']),
                'end' => $query->func()->max('Documents.dat_issue', ['string']),
            ])
            ->where(['Documents.counter_id' => $counterId]);
        $ret = $query->first()->toArray();

        if (empty($ret['start'])) {
            $ret['start'] = new FrozenDate();
        } else {
            $ret['start'] = FrozenDate::parse($ret['start']);
        }
        if (empty($ret['end'])) {
            $ret['end'] = new FrozenDate();
        } else {
            $ret['end'] = FrozenDate::parse($ret['end']);
        }

        return $ret;
    }

    /**
     * Creates entity by parsing request
     *
     * @param \Cake\Http\ServerRequest $request Request object
     * @param string|null $id Document id.
     * @return \Documents\Model\Entity\Document
     */
    public function parseRequest($request, $id = null)
    {
        if (!empty($id)) {
            $document = $this->get($id, ['contain' => ['DocumentsCounters', 'Issuers', 'Receivers']]);
        } else {
            /** @var \Documents\Model\Table\DocumentsClientsTable $DocumentsClients */
            $DocumentsClients = TableRegistry::getTableLocator()->get('Documents.DocumentsClients');
            /** @var \Documents\Model\Table\DocumentsCountersTable $DocumentsCounters */
            $DocumentsCounters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');

            $sourceId = $request->getQuery('duplicate');
            if (!empty($sourceId)) {
                // clone
                $document = $this->get($sourceId, ['contain' => ['DocumentsCounters', 'Issuers', 'Receivers']]);

                $document->setNew(true);
                unset($document->id);

                $document->issuer->setNew(true);
                unset($document->issuer->id);
                unset($document->issuer->document_id);

                $document->receiver->setNew(true);
                unset($document->receiver->id);
                unset($document->receiver->document_id);

                $counterId = $request->getQuery('counter', $document->counter_id);
                $document->documents_counter = $DocumentsCounters->get($counterId);
            } else {
                // new entity
                $document = $this->newEmptyEntity();
                $document->owner_id = $request->getAttribute('identity')->get('company_id');
                $counterId = $request->getQuery('counter');
                $document->documents_counter = $DocumentsCounters->get($counterId);

                $document->issuer = $DocumentsClients->newEntity(['kind' => 'II']);
                $document->receiver = $DocumentsClients->newEntity(['kind' => 'IV']);

                switch ($document->documents_counter->direction) {
                    case 'issued':
                        $document->issuer->patchWithAuth($request->getAttribute('identity'));
                        break;
                    case 'received':
                        $document->receiver->patchWithAuth($request->getAttribute('identity'));
                        break;
                }
            }

            $document->counter_id = $document->documents_counter->id;
            $document->no = $DocumentsCounters->generateNo($document->counter_id);
        }

        return $document;
    }
}
