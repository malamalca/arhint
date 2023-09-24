<?php
declare(strict_types=1);

namespace Documents\Model\Table;

use Cake\Core\Plugin;
use Cake\Http\ServerRequest;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Documents\Model\Entity\TravelOrder;

/**
 * TravelOrders Model
 *
 * @property \Documents\Model\Table\OwnersTable&\Cake\ORM\Association\BelongsTo $Owners
 * @property \Documents\Model\Table\PayersTable&\Cake\ORM\Association\BelongsTo $Payers
 * @property \Documents\Model\Table\EmployeesTable&\Cake\ORM\Association\BelongsTo $Employees
 * @property \Documents\Model\Table\VehiclesTable&\Cake\ORM\Association\BelongsTo $Vehicles
 * @property \Documents\Model\Table\CountersTable&\Cake\ORM\Association\BelongsTo $Counters
 * @property \Documents\Model\Table\TplHeadersTable&\Cake\ORM\Association\BelongsTo $TplHeaders
 * @property \Documents\Model\Table\TplBodiesTable&\Cake\ORM\Association\BelongsTo $TplBodies
 * @property \Documents\Model\Table\TplFootersTable&\Cake\ORM\Association\BelongsTo $TplFooters
 * @method \Documents\Model\Entity\TravelOrder newEmptyEntity()
 * @method \Documents\Model\Entity\TravelOrder newEntity(array $data, array $options = [])
 * @method \Documents\Model\Entity\TravelOrder[] newEntities(array $data, array $options = [])
 * @method \Documents\Model\Entity\TravelOrder get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Documents\Model\Entity\TravelOrder findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Documents\Model\Entity\TravelOrder patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Documents\Model\Entity\TravelOrder[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Documents\Model\Entity\TravelOrder|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class TravelOrdersTable extends Table
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

        $this->setTable('travel_orders');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('DocumentsCounters', [
            'foreignKey' => 'counter_id',
            'className' => 'Documents\Model\Table\DocumentsCountersTable',
        ]);

        $this->belongsTo('Employees', [
            'className' => 'App\Model\Table\UsersTable',
            'foreignKey' => 'employee_id',
            'dependent' => true,
        ]);

        $this->belongsTo('Payers', [
            'className' => 'Documents\Model\Table\DocumentsClientsTable',
            'foreignKey' => 'payer_id',
            'dependent' => true,
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
            ->scalar('doc_type')
            ->maxLength('doc_type', 5)
            ->allowEmptyString('doc_type');

        $validator
            ->integer('attachment_count')
            ->notEmptyString('attachment_count');

        $validator
            ->scalar('no')
            ->maxLength('no', 50)
            ->allowEmptyString('no');

        $validator
            ->date('dat_issue')
            ->allowEmptyDate('dat_issue');

        $validator
            ->scalar('location')
            ->maxLength('location', 200)
            ->allowEmptyString('location');

        $validator
            ->scalar('descript')
            ->allowEmptyString('descript');

        $validator
            ->scalar('title')
            ->maxLength('title', 200)
            ->notEmptyString('title');

        $validator
            ->scalar('taskee')
            ->maxLength('taskee', 200)
            ->allowEmptyString('taskee');

        $validator
            ->date('dat_task')
            ->notEmptyDate('dat_task');

        $validator
            ->dateTime('departure')
            ->allowEmptyDateTime('departure');

        $validator
            ->dateTime('arrival')
            ->allowEmptyDateTime('arrival');

        $validator
            ->scalar('vehicle_registration')
            ->maxLength('vehicle_registration', 200)
            ->allowEmptyString('vehicle_registration');

        $validator
            ->scalar('vehicle_owner')
            ->maxLength('vehicle_owner', 200)
            ->allowEmptyString('vehicle_owner');

        $validator
            ->decimal('advance')
            ->allowEmptyString('advance');

        $validator
            ->date('dat_advance')
            ->allowEmptyDate('dat_advance');

        $validator
            ->decimal('total')
            ->allowEmptyString('total');

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
        /*$rules->add($rules->existsIn('owner_id', 'Owners'), ['errorField' => 'owner_id']);
        $rules->add($rules->existsIn('payer_id', 'Payers'), ['errorField' => 'payer_id']);
        $rules->add($rules->existsIn('employee_id', 'Employees'), ['errorField' => 'employee_id']);
        $rules->add($rules->existsIn('vehicle_id', 'Vehicles'), ['errorField' => 'vehicle_id']);
        $rules->add($rules->existsIn('counter_id', 'Counters'), ['errorField' => 'counter_id']);
        $rules->add($rules->existsIn('tpl_header_id', 'TplHeaders'), ['errorField' => 'tpl_header_id']);
        $rules->add($rules->existsIn('tpl_body_id', 'TplBodies'), ['errorField' => 'tpl_body_id']);
        $rules->add($rules->existsIn('tpl_footer_id', 'TplFooters'), ['errorField' => 'tpl_footer_id']);*/

        return $rules;
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
     * filter method
     *
     * @param array<string, mixed> $filter Filter data.
     * @return array<string, mixed>
     */
    public function filter(array &$filter): array
    {
        $ret = ['conditions' => [], 'contain' => []];

        if (isset($filter['counter'])) {
            $ret['conditions']['TravelOrders.counter_id'] = $filter['counter'];
        }

        // from-to date
        if (isset($filter['start'])) {
            $filter['start'] = DateTime::parseDate($filter['start'], 'yyyy-MM-dd');
            if (!empty($filter['start'])) {
                $ret['conditions']['TravelOrders.dat_task >='] = $filter['start'];
            }
        }

        if (isset($filter['end'])) {
            $filter['end'] = DateTime::parseDate($filter['end'], 'yyyy-MM-dd');
            if (!empty($filter['end'])) {
                $ret['conditions']['TravelOrders.dat_task <='] = $filter['end'];
            }
        }

        if (isset($filter['month'])) {
            $start = DateTime::parseDate($filter['month'] . '-01', 'yyyy-MM-dd');
            if (!empty($start)) {
                $ret['conditions']['TravelOrders.dat_task >='] = $start;
                $ret['conditions']['TravelOrders.dat_task <'] = $start->addMonths(1);
                $filter['start'] = $start;
                $filter['end'] = $start->addMonths(1)->subDays(1);
            }
        }

        // override all conditions if Document.id is set
        if (!empty($filter['id'])) {
            $ret['conditions'] = ['TravelOrders.id' => $filter['id']];
        }

        // manual search
        if (!empty($filter['search']) && ($filter['search'] != '[[search]]')) {
            if (substr($filter['search'], 0, 1) == '#') {
                $ret['conditions'][] = ['TravelOrders.counter' => substr($filter['search'], 1)];
            } else {
                $ret['conditions'][] = ['OR' => [
                    'TravelOrders.no LIKE' => '%' . $filter['search'] . '%',
                    'TravelOrders.location LIKE' => '%' . $filter['search'] . '%',
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
     * @return array<string, \Cake\I18n\Date>
     */
    public function maxSpan(string $counterId): array
    {
        $ret = [];

        $query = $this->find();
        $query
            ->select([
                'start' => $query->func()->min('TravelOrders.dat_task', ['string']),
                'end' => $query->func()->max('TravelOrders.dat_task', ['string']),
            ])
            ->where(['TravelOrders.counter_id' => $counterId]);
        $ret = $query->first()->toArray();

        if (empty($ret['start'])) {
            $ret['start'] = new Date();
        } else {
            $ret['start'] = Date::parseDate($ret['start'], 'yyyy-MM-dd');
        }
        if (empty($ret['end'])) {
            $ret['end'] = new Date();
        } else {
            $ret['end'] = Date::parseDate($ret['end'], 'yyyy-MM-dd');
        }

        return $ret;
    }

    /**
     * Creates entity by parsing request
     *
     * @param \Cake\Http\ServerRequest $request Request object
     * @param string|null $id Document id.
     * @return \Documents\Model\Entity\TravelOrder
     */
    public function parseRequest(ServerRequest $request, ?string $id = null): TravelOrder
    {
        if (!empty($id)) {
            $document = $this->get($id, contain: ['DocumentsCounters']);
        } else {
            /** @var \Documents\Model\Table\DocumentsCountersTable $DocumentsCounters */
            $DocumentsCounters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');

            $sourceId = $request->getQuery('duplicate');
            if (!empty($sourceId)) {
                // clone
                $document = $this->get($sourceId, contain: ['DocumentsCounters']);

                $document->setNew(true);
                unset($document->id);

                $document->payer->setNew(true);
                unset($document->payer->id);
                unset($document->payer->document_id);

                $counterId = $request->getQuery('counter', $document->counter_id);
            } else {
                // new entity
                $document = $this->newEmptyEntity();

                $document->owner_id = $request->getAttribute('identity')->get('company_id');
                //$document->payer = $DocumentsClients->newEntity(['kind' => 'IV']);

                $counterId = $request->getQuery('counter');
            }

            $document->documents_counter = $DocumentsCounters->get($counterId);
            $document->counter_id = $document->documents_counter->id;
            $document->no = (string)$DocumentsCounters->generateNo($document->counter_id);
        }

        return $document;
    }
}
