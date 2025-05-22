<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\User;
use Authentication\PasswordHasher\PasswordHasherFactory;
use Cake\Core\Configure;
use Cake\Datasource\QueryInterface;
use Cake\Http\ServerRequest;
use Cake\Mailer\Mailer;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

/**
 * Users Model
 *
 * @property \App\Model\Table\CompaniesTable|\Cake\ORM\Association\BelongsTo $Companies
 * @method \App\Model\Entity\User get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\User newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\User[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\User|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\User saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\User patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\User[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\User findOrCreate($search, array<array-key, mixed>|callable|null $callback = null, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class UsersTable extends Table
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

        $this->setTable('users');
        $this->setDisplayField('name');
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
            ->uuid('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('name')
            ->maxLength('name', 50)
            ->notEmptyString('name');

        $validator
            ->scalar('username')
            ->maxLength('username', 100)
            ->notEmptyString('username');

        $validator
            ->scalar('passwd')
            ->maxLength('passwd', 100)
            ->allowEmptyString('passwd');

        $validator
            ->integer('privileges')
            ->requirePresence('privileges', 'create')
            ->allowEmptyString('privileges');

        $validator
            ->boolean('active')
            ->requirePresence('active', 'create')
            ->allowEmptyString('active');

        $validator
            ->email('email')
            ->notEmptyString('email');

        $validator
            ->scalar('reset_key')
            ->maxLength('reset_key', 200)
            ->allowEmptyString('reset_key');

        $validator
            ->scalar('address')
            ->maxLength('address', 200)
            ->allowEmptyString('address');

        $validator
            ->scalar('zip')
            ->maxLength('zip', 50)
            ->allowEmptyString('zip');

        $validator
            ->scalar('city')
            ->maxLength('city', 200)
            ->allowEmptyString('city');

        $validator
            ->scalar('uid')
            ->maxLength('uid', 200)
            ->allowEmptyString('uid');

        $validator
            ->scalar('url_key')
            ->maxLength('url_key', 200)
            ->allowEmptyString('url_key');

        return $validator;
    }

    /**
     * validationResetPassword validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationResetPassword(Validator $validator): Validator
    {
        $validator = new Validator();
        $validator
            ->add('passwd', 'minLength', ['rule' => ['minLength', 4]])
            ->requirePresence(
                'repeat_passwd',
                function ($context) {
                    return !empty($context['data']['repeat_passwd']);
                },
            )
            ->notEmptyString('repeat_passwd')
            ->add('repeat_passwd', 'match', [
                    'rule' => function ($value, $context) {
                        return $value == $context['data']['passwd'];
                    },
                ]);

        return $validator;
    }

    /**
     * validationProperties validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationProperties(Validator $validator): Validator
    {
        $validator = new Validator();
        $validator
            ->allowEmptyFile('avatar_file')
            ->add('avatar_file', 'file', ['rule' => [['mimeType', ['image/png']], ['filesize', '<', '30KB']]])

            ->allowEmptyString('passwd')
            ->add('passwd', 'minLength', ['rule' => ['minLength', 4]])

            ->notEmptyString('repeat_passwd', 'empty', function ($context) {
                return !empty($context['data']['passwd']);
            })
            ->add('repeat_passwd', 'match', [
                    'rule' => function ($value, $context) {
                        return $value == $context['data']['passwd'];
                    },
                ])

            ->notEmptyString('old_passwd', 'empty', function ($context) {
                return !empty($context['data']['passwd']);
            })
            ->add('old_passwd', 'match', [
                'rule' => function ($value, $context) {
                    /** @var \App\Model\Table\UsersTable $UsersTable */
                    $UsersTable = TableRegistry::getTableLocator()->get('Users');
                    $user = $UsersTable->get($context['data']['id']);

                    $passwordHasher =
                        PasswordHasherFactory::build('Authentication\PasswordHasher\DefaultPasswordHasher');

                    return $passwordHasher->check($value, $user->passwd ?? '');
                },
            ]);

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
        $rules->add($rules->isUnique(['username']));
        $rules->add($rules->isUnique(['email']));

        return $rules;
    }

    /**
     * Filter by params
     *
     * @param \Cake\Datasource\QueryInterface $query Query object
     * @param \Cake\Http\ServerRequest $request Server request
     * @return array<string, mixed>
     */
    public function filter(QueryInterface $query, ServerRequest $request): array
    {
        $ret = [];

        if ($request->getQuery('active') !== null) {
            $query->andWhere(['active' => $request->getQuery('active')]);
        }

        return $ret;
    }

    /**
     * Sends reset email
     *
     * @param \App\Model\Entity\User $user User entity.
     * @return bool
     */
    public function sendResetEmail(User $user): bool
    {
        $email = new Mailer('default');
        $email->setFrom([Configure::read('App.fromEmail.from') => Configure::read('App.fromEmail.name')]);
        $email->setTo($user->email);
        $email->setSubject(__('Password Reset'));

        $email->viewBuilder()->setTemplate('reset');
        $email->setEmailFormat('text');
        $email->setViewVars(['reset_key' => $user->reset_key]);
        $email->viewBuilder()->addHelper('Html');

        $ret = $email->send();

        return (bool)$ret;
    }

    /**
     * Fetch users for specified company
     *
     * @param string $companyId Company id
     * @param array<string, mixed> $options Array of options
     * @return array<\App\Model\Entity\User>
     */
    public function fetchForCompany(string $companyId, array $options = []): array
    {
        $defaultOptions = [
            'inactive' => false,
            'includeUsers' => null,
        ];
        $options = array_merge($defaultOptions, $options);

        $query = $this->find('list', keyField: 'id', valueField: fn($e) => $e);
        $query->where(['Users.company_id' => $companyId]);

        if (!$options['inactive']) {
            if (!empty($options['includeUsers'])) {
                $query->andWhere(['OR' => ['Users.active' => 1, 'Users.id IN' => (array)$options['includeUsers']]]);
            } else {
                $query->andWhere(['Users.active' => 1]);
            }
        }

        $ret = $query->toArray();

        return $ret;
    }
}
