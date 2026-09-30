<?php
declare(strict_types=1);

namespace Documents\Model\Table;

use Cake\I18n\DateTime;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Documents\Model\Entity\ApiRequest;
use Throwable;

/**
 * ApiRequests Model - Idempotency-Key bookkeeping for the REST API.
 *
 * @method \Documents\Model\Entity\ApiRequest newEmptyEntity()
 */
class ApiRequestsTable extends Table
{
    public const CLAIMED = 'claimed';
    public const REPLAY = 'replay';
    public const MISMATCH = 'mismatch';
    public const IN_PROGRESS = 'in_progress';

    /**
     * Requests without a result older than this are considered abandoned (crashed process).
     */
    public const STALE_AFTER = '-5 minutes';

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config List of options for this table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('documents_api_requests');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
    }

    /**
     * Rules
     *
     * @param \Cake\ORM\RulesChecker $rules Rules checker.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        return $rules->add($rules->isUnique(['user_id', 'idempotency_key']), ['errorField' => 'idempotency_key']);
    }

    /**
     * Try to claim the key for a request.
     *
     * @param string $userId User id.
     * @param string $key Idempotency-Key.
     * @param string $hash Hash of the request.
     * @return array{0: string, 1: \Documents\Model\Entity\ApiRequest} Status and the request record.
     */
    public function claim(string $userId, string $key, string $hash): array
    {
        $record = $this->newEmptyEntity();
        $record->user_id = $userId;
        $record->idempotency_key = $key;
        $record->request_hash = $hash;

        try {
            if ($this->save($record)) {
                return [self::CLAIMED, $record];
            }
        } catch (Throwable $e) {
            // lost a race against a parallel request with the same key; handled below
        }

        /** @var \Documents\Model\Entity\ApiRequest|null $existing */
        $existing = $this->find()->where(['user_id' => $userId, 'idempotency_key' => $key])->first();
        if (!$existing) {
            // released between the failed insert and the lookup
            return $this->claim($userId, $key, $hash);
        }

        if ($existing->request_hash !== $hash) {
            return [self::MISMATCH, $existing];
        }
        if ($existing->invoice_id) {
            return [self::REPLAY, $existing];
        }

        if ($existing->modified !== null && $existing->modified->lessThan(new DateTime(self::STALE_AFTER))) {
            // abandoned attempt, take it over
            $existing->setDirty('modified', true);
            $this->saveOrFail($existing);

            return [self::CLAIMED, $existing];
        }

        return [self::IN_PROGRESS, $existing];
    }

    /**
     * Store the result of a request.
     *
     * @param \Documents\Model\Entity\ApiRequest $record Claimed record.
     * @param string $invoiceId Created invoice id.
     * @return void
     */
    public function complete(ApiRequest $record, string $invoiceId): void
    {
        $this->updateAll(['invoice_id' => $invoiceId], ['id' => $record->id]);
    }

    /**
     * Forget the key so the request can be retried (used when the request failed and nothing was created).
     *
     * @param \Documents\Model\Entity\ApiRequest $record Claimed record.
     * @return void
     */
    public function release(ApiRequest $record): void
    {
        $this->deleteAll(['id' => $record->id, 'invoice_id IS' => null]);
    }
}
