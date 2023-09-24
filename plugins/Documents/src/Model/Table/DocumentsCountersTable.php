<?php
declare(strict_types=1);

namespace Documents\Model\Table;

use Cake\Cache\Cache;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * DocumentsCounters Model
 *
 * @method \Documents\Model\Entity\DocumentsCounter get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Documents\Model\Entity\DocumentsCounter newEmptyEntity()
 * @method \Documents\Model\Entity\DocumentsCounter patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 */
class DocumentsCountersTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config List of options for this table.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('documents_counters');
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
            ->allowEmptyString('direction')
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
     * @return string|bool Generated document number or false on failure.
     */
    public function generateNo(mixed $id): string|bool
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
                    '[[year]]' => (new DateTime())->i18nFormat('yyyy'),
                    '[[month]]' => (new DateTime())->i18nFormat('MM'),
                    '[[no]]' => (int)$data['counter'] + 1,
                    '[[no.2]]' => str_pad((string)((int)$data['counter'] + 1), 2, '0', STR_PAD_LEFT),
                    '[[no.3]]' => str_pad((string)((int)$data['counter'] + 1), 3, '0', STR_PAD_LEFT),
                ]
            );
        }

        return $ret;
    }

    /**
     * Fetch counters from cache
     *
     * @param string $userId Users id
     * @param \Cake\ORM\Query\SelectQuery $scopedQuery Query with applied scope
     * @param string|null $filterKind Filter by counter kind
     * @return mixed $counters
     */
    public function rememberForUser(string $userId, SelectQuery $scopedQuery, ?string $filterKind = null): mixed
    {
        $counters = Cache::remember(
            'Documents.sidebarCounters.' . $userId,
            function () use ($scopedQuery) {
                return $scopedQuery
                    ->where(['active' => true])
                    ->order(['active', 'direction DESC', 'title'])
                    ->all();
            }
        );

        if (!empty($filterKind)) {
            $counters = $counters->filter(fn ($value, $key) => $value->kind == $filterKind);
        }

        return $counters;
    }

    /**
     * findDefaultCounter method
     *
     * @param \Cake\ORM\Query\SelectQuery $query Query with applied scope
     * @param string $kind Counter kind (document, invoice, travelorder)
     * @param string|null $counterType Counter type
     * @return mixed Counter data or false on failure.
     */
    public function findDefaultCounter(SelectQuery $query, string $kind, ?string $counterType = null): mixed
    {
        $params = ['order' => null, 'conditions' => []];

        // no counter specified; find first (or default) counter
        $params['conditions'] = ['active' => true, 'kind' => $kind];
        $params['order'] = ['active', 'direction DESC', 'title'];

        if (!empty($counterType)) {
            if (in_array($counterType, ['received', 'issued', 'other'])) {
                $params['conditions']['direction'] = $counterType;
            } elseif ($counterType == 'archived') {
                $params['conditions']['active'] = false;
            }
        }

        $ret = $query
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

        if (isset($filter['type'])) {
            $ret['conditions']['DocumentsCounters.doc_type'] = $filter['type'];
        }

        if (!empty($filter['inactive'])) {
            $ret['conditions']['DocumentsCounters.active IN'] = [true, false];
        }

        if (!empty($filter['search'])) {
            $ret['conditions']['DocumentsCounters.title LIKE'] = '%' . $filter['search'] . '%';
        }

        return $ret;
    }
}
