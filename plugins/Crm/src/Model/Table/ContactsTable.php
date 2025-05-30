<?php
declare(strict_types=1);

namespace Crm\Model\Table;

use ArrayObject;
use Cake\Database\Expression\FunctionExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Rule\IsUnique;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Contacts Model
 *
 * @method bool touch(\Crm\Model\Entity\Contact $entity)
 * @property \Crm\Model\Table\ContactsAccountsTable $ContactsAccounts
 * @property \Crm\Model\Table\ContactsAddressesTable $ContactsAddresses
 * @property \Crm\Model\Table\ContactsEmailsTable $ContactsEmails
 * @property \Crm\Model\Table\ContactsPhonesTable $ContactsPhones
 */
class ContactsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config List of options for this table.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('contacts');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsTo('Companies', [
            'foreignKey' => 'company_id',
            'className' => 'Crm\Model\Table\ContactsTable',
        ]);

        $this->belongsTo('Users', [
            'foreignKey' => 'owner_id',
            'className' => 'App\Model\Table\UsersTable',
        ]);

        $this->hasMany('Employees', [
            'foreignKey' => 'company_id',
            'className' => 'Crm\Model\Table\ContactsTable',
        ]);

        $this->hasMany('Crm.ContactsEmails', [
            'dependent' => true,
            'sort' => 'ContactsEmails.primary DESC, ContactsEmails.email',
        ]);

        $this->hasMany('Crm.ContactsPhones', [
            'dependent' => true,
        ]);

        $this->hasMany('Crm.ContactsAddresses', [
            'dependent' => true,
            'sort' => 'ContactsAddresses.primary DESC, ContactsAddresses.street',
        ]);

        $this->hasMany('Crm.ContactsAccounts', [
            'dependent' => true,
            'sort' => 'ContactsAccounts.primary DESC, ContactsAccounts.bic',
        ]);

        $this->hasOne('PrimaryAddresses', [
            'className' => 'Crm\Model\Table\ContactsAddressesTable',
            'foreignKey' => 'contact_id',
            'conditions' => ['PrimaryAddresses.primary' => true],
        ]);
        $this->hasOne('PrimaryEmails', [
            'className' => 'Crm\Model\Table\ContactsEmailsTable',
            'foreignKey' => 'contact_id',
            'conditions' => ['PrimaryEmails.primary' => true],
        ]);
        $this->hasOne('PrimaryPhones', [
            'className' => 'Crm\Model\Table\ContactsPhonesTable',
            'foreignKey' => 'contact_id',
            'conditions' => ['PrimaryPhones.primary' => true],
        ]);
        $this->hasOne('PrimaryAccounts', [
            'className' => 'Crm\Model\Table\ContactsAccountsTable',
            'foreignKey' => 'contact_id',
            'conditions' => ['PrimaryAccounts.primary' => true],
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
            ->allowEmptyString('id', 'create')
            ->requirePresence('kind', 'create')
            ->notEmptyString('kind')
            //->allowEmptyString('name')
            //->allowEmptyString('surname')

            ->add('surname', 'nameorsurname', ['rule' => function ($value, $context) {
                return $context['data']['kind'] != 'T' ||
                    !empty($value) ||
                    !empty($context['data']['name']);
            }])

            //->requirePresence('title', 'create')
            ->notEmptyString('title')

            ->allowEmptyString('descript')
            ->allowEmptyString('mat_no')

            ->notEmptyString('tax_no')

            ->add('tax_status', 'valid', ['rule' => 'boolean'])

            ->allowEmptyString('tax_status')
            ->allowEmptyString('company_id')
            ->allowEmptyString('job')
            ->add('syncable', 'valid', ['rule' => 'boolean']);

            //->requirePresence('syncable', 'create')
            //->notEmptyString('syncable')

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
        $rules->add(new IsUnique(['owner_id', 'tax_no'], ['allowMultipleNulls' => true]), 'uniqueTax', [
            'errorField' => 'tax_no',
        ]);

        return $rules;
    }

    /**
     * beforeSave method
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \Crm\Model\Entity\Contact $entity Entity object.
     * @param \ArrayObject $options Options array.
     * @return void
     */
    public function beforeSave(Event $event, Entity $entity, ArrayObject $options): void
    {
        if ($entity->kind == 'T') {
            $entity->title = implode(' ', array_filter([$entity->surname, $entity->name]));
        }
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
     * filter method
     *
     * @param array<string, mixed> $filter Filter data.
     * @return array<string, mixed>
     */
    public function filter(array &$filter): array
    {
        $ret = ['conditions' => [], 'order' => []];
        if (empty($filter['kind'])) {
            $filter['kind'] = 'C';
        }

        $ret['conditions']['Contacts.kind'] = $filter['kind'];

        if (!empty($filter['search'])) {
            $subquery = $this->ContactsPhones->find()
                ->select(['id', 'no'])
                ->where([
                    function (QueryExpression $exp) {
                        return $exp->equalFields('Contacts.id', 'ContactsPhones.contact_id');
                    },
                    function (QueryExpression $exp) use ($filter) {
                        return $exp->like(
                            //$q->func()->replace(['ContactsPhones.no' => 'identifier', ' ', '']),
                            new FunctionExpression('replace', ['ContactsPhones.no' => 'identifier', ' ', '']),
                            '%' . preg_replace('/\s+/', '', $filter['search']) . '%',
                        );
                    },
                ]);

            $ret['conditions']['OR'] = [
                'Contacts.title LIKE' => '%' . $filter['search'] . '%',
                function (QueryExpression $exp) use ($subquery) {
                    return $exp->exists($subquery);
                },
            ];
        }

        return $ret;
    }
}
