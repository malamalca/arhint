<?php
declare(strict_types=1);

namespace Calendar\Model\Table;

use Cake\Event\Event;
use Cake\I18n\FrozenDate;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Events Model
 *
 * @property \Calendar\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @method \Calendar\Model\Entity\Event newEmptyEntity()
 * @method \Calendar\Model\Entity\Event newEntity(array $data, array $options = [])
 * @method \Calendar\Model\Entity\Event[] newEntities(array $data, array $options = [])
 * @method \Calendar\Model\Entity\Event get($primaryKey, $options = [])
 * @method \Calendar\Model\Entity\Event findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Calendar\Model\Entity\Event patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Calendar\Model\Entity\Event[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Calendar\Model\Entity\Event|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Calendar\Model\Entity\Event saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Calendar\Model\Entity\Event[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Calendar\Model\Entity\Event[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Calendar\Model\Entity\Event[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Calendar\Model\Entity\Event[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class EventsTable extends Table
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

        $this->setTable('events');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'className' => 'Users',
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
            ->uuid('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('title')
            ->maxLength('title', 200)
            ->notEmptyString('title');

        $validator
            ->scalar('location')
            ->maxLength('location', 200)
            ->allowEmptyString('location');

        $validator
            ->scalar('body')
            ->allowEmptyString('body');

        $validator
            ->boolean('all_day')
            ->notEmptyString('all_day');

        $validator
            ->dateTime('dat_start')
            ->notEmptyDate('dat_start');

        $validator
            ->dateTime('dat_end')
            ->notEmptyDate('dat_end');

        $validator
            ->integer('reminder')
            ->allowEmptyString('reminder');

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
        $rules->add($rules->existsIn('user_id', 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }

    /**
     * beforeMarshal method
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \ArrayObject $data Post data.
     * @param \ArrayObject $options Array object.
     * @return void
     */
    public function beforeMarshal(Event $event, \ArrayObject $data, \ArrayObject $options)
    {
        if ($data['all_day'] == '1') {
            $data['dat_start'] .= ' 00:00:00';
            $data['dat_end'] .= ' 00:00:00';
        }
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
     * Filters accounts by query string
     *
     * @param array $filter Filter array.
     * @return array
     */
    public function filter(&$filter)
    {
        $ret = [];

        if (!empty($filter['calendar'])) {
            $ret['conditions']['Events.calendar_id'] = $filter['calendar'];
        }

        if (!empty($filter['month'])) {
            $startDate = FrozenDate::parseDate($filter['month'] . '-01', 'yyyy-MM-dd');
            if (!$startDate) {
                $startDate = (new FrozenDate())->startOfMonth();
            }
        } else {
            $startDate = (new FrozenDate())->startOfMonth();
        }

        $endDate = $startDate->endOfMonth()->addDay();

        $ret['conditions']['AND'] = [
            'Events.dat_start <=' => $endDate,
            'Events.dat_end >=' => $startDate,
        ];

        if (isset($filter['kind']) && $filter['kind'] != 'public') {
            unset($filter['kind']);
        }

        return $ret;
    }
}
