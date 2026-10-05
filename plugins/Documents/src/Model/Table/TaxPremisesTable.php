<?php
declare(strict_types=1);

namespace Documents\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Documents\Lib\FursClient;
use Documents\Lib\FursXml;
use Documents\Model\Entity\TaxPremise;
use Throwable;

/**
 * TaxPremises Model
 *
 * @method \Documents\Model\Entity\TaxPremise get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Documents\Model\Entity\TaxPremise newEmptyEntity()
 * @method \Documents\Model\Entity\TaxPremise patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 */
class TaxPremisesTable extends Table
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

        $this->setTable('documents_tax_premises');
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
        $isMoveableCreate = fn($context) => $context['newRecord'] && ($context['data']['kind'] ?? 'RL') === 'MO';
        $isRealEstate = fn($context) => ($context['data']['kind'] ?? 'RL') === 'RL';
        $isMoveable = fn($context) => !$isRealEstate($context);

        $validator
            ->allowEmptyString('id', 'create')
            ->requirePresence('no', 'create')
            ->notEmptyString('no')
            ->maxLength('no', 20)
            ->regex('no', '/^[A-Za-z0-9]{1,20}$/', 'Only letters and digits are allowed.')
            ->allowEmptyString('title')
            ->inList('kind', ['RL', 'MO'])
            ->requirePresence('validity_date', 'create')
            ->notEmptyDate('validity_date')
            ->requirePresence('sw_taxno', 'create')
            ->notEmptyString('sw_taxno')
            ->add('sw_taxno', 'taxno', [
                'rule' => fn($value) => FursXml::normalizeTaxNo((string)$value) !== null,
                'message' => 'Tax number must have 8 digits.',
            ])

            ->add('mo_type', 'valid', ['rule' => ['inList', ['A', 'B', 'C']]])
            ->requirePresence('mo_type', $isMoveableCreate)
            ->notEmptyString('mo_type', __d('documents', 'Type of moveable premise is required.'), $isMoveable);

        foreach (
            [
                'casadral_number', 'building_number', 'building_section_number',
                'street', 'house_number', 'community', 'city', 'postal_code',
            ] as $field
        ) {
            $validator
                ->requirePresence($field, fn($context) => $context['newRecord'] && $isRealEstate($context))
                ->notEmptyString($field, __d('documents', 'This field is required.'), $isRealEstate);
        }

        return $validator->allowEmptyString('notes');
    }

    /**
     * Premises of an owner.
     *
     * @param string $ownerId Company id.
     * @param bool $activeOnly Return only premises registered at FURS and not closed.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findForOwner(string $ownerId, bool $activeOnly = false): SelectQuery
    {
        $query = $this->find()->where(['TaxPremises.owner_id' => $ownerId])->orderBy(['TaxPremises.no']);
        if ($activeOnly) {
            $query->where(['TaxPremises.active' => true, 'TaxPremises.closed' => false]);
        }

        return $query;
    }

    /**
     * Register premise (or its closing) at FURS.
     *
     * @param \Documents\Model\Entity\TaxPremise $premise Premise entity.
     * @param \Documents\Lib\FursClient $client Client with certificate.
     * @param string $issuerTaxNo Issuer tax number.
     * @return string|null Null on success or error message.
     */
    public function register(TaxPremise $premise, FursClient $client, string $issuerTaxNo): ?string
    {
        $taxNo = FursXml::normalizeTaxNo($issuerTaxNo);
        if ($taxNo === null) {
            return __d('documents', 'Company tax number is missing or invalid.');
        }

        $signed = $client->sign(FursXml::premise($premise, $taxNo), 'fu:BusinessPremiseRequest');
        if ($signed === null) {
            return __d('documents', 'Request could not be signed. Check certificate and password.');
        }
        $premise->last_request = $signed;

        try {
            $response = $client->sendPremise($signed);
        } catch (Throwable $e) {
            $this->save($premise);

            return $e->getMessage();
        }

        $premise->last_response = FursClient::toUtf8($response);
        $result = FursClient::parseResponse($response);
        if ($result['ok']) {
            $premise->active = !$premise->closed;
        }
        $this->save($premise);

        return $result['ok'] ? null : (string)$result['error'];
    }
}
