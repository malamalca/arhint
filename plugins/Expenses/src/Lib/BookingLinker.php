<?php
declare(strict_types=1);

namespace Expenses\Lib;

use Cake\Collection\CollectionInterface;
use Cake\Datasource\EntityInterface;
use Cake\Http\Exception\ForbiddenException;
use Cake\ORM\TableRegistry;
use Expenses\Service\BookingRuleMatcher;

/**
 * Encapsulates cross-model logic for the BookingOrders/links action:
 * model resolution, ownership checks, entity display info, and
 * display-entry row building (prefill or propose from rule).
 */
class BookingLinker
{
    /**
     * Whitelisted source models and their ORM table alias + contains.
     *
     * @var array<string, array{0: string, 1: array<string>}>
     */
    private const SUPPORTED_MODELS = [
        'BankStatementEntry' => ['Expenses.BankStatementEntries', ['BankStatements']],
        'Invoice' => ['Documents.Invoices', []],
        'TravelOrder' => ['Documents.TravelOrders', []],
    ];

    /**
     * Returns true when the given model name is on the whitelist.
     *
     * @param string $model Model class name.
     * @return bool
     */
    public function isSupportedModel(string $model): bool
    {
        return isset(self::SUPPORTED_MODELS[$model]);
    }

    /**
     * Returns [ORM Table instance, contain array] for a whitelisted model name,
     * or [null, []] when the model is unknown / not allowed.
     *
     * @param string $model Model class name from the request query param.
     * @return array{0: \Cake\ORM\Table|null, 1: array<string>}
     */
    public function resolveSourceTable(string $model): array
    {
        if (!isset(self::SUPPORTED_MODELS[$model])) {
            return [null, []];
        }
        [$tableAlias, $contain] = self::SUPPORTED_MODELS[$model];

        return [TableRegistry::getTableLocator()->get($tableAlias), $contain];
    }

    /**
     * Verifies the entity belongs to the current company.
     * Throws ForbiddenException when the check fails.
     *
     * @param \Cake\Datasource\EntityInterface $entity Source entity.
     * @param string $model Model name.
     * @param string $ownerId Current company id.
     * @return void
     * @throws \Cake\Http\Exception\ForbiddenException
     */
    public function assertOwnership(EntityInterface $entity, string $model, string $ownerId): void
    {
        $entityOwnerId = match ($model) {
            'BankStatementEntry' => $entity->bank_statement?->owner_id ?? null,
            default => $entity->get('owner_id') ?? null,
        };
        if ($entityOwnerId !== null && $entityOwnerId !== $ownerId) {
            throw new ForbiddenException();
        }
    }

    /**
     * Returns a human-readable name for the entity.
     *
     * @param \Cake\Datasource\EntityInterface $entity Source entity.
     * @param string $model Model name.
     * @return string
     */
    public static function entityName(EntityInterface $entity, string $model): string
    {
        return match ($model) {
            'BankStatementEntry' => __d('expenses', 'Bank Statement Entry #{0}', (string)$entity->get('no')),
            'Invoice' => __d('expenses', 'Invoice #{0}', (string)$entity->get('no')),
            'TravelOrder' => __d('expenses', 'Travel Order #{0}', (string)$entity->get('no')),
            default => (string)$entity->get('id'),
        };
    }

    /**
     * Builds a label→value display array for the source entity info panel.
     *
     * @param \Cake\Datasource\EntityInterface $entity Source entity.
     * @param string $model Model name.
     * @return array<string, string>
     */
    public function entityInfo(EntityInterface $entity, string $model): array
    {
        return match ($model) {
            'BankStatementEntry' => [
                __d('expenses', 'Date') => h((string)$entity->get('dat_issue')),
                __d('expenses', 'Ref#') => h((string)$entity->get('no')),
                __d('expenses', 'Client') => h((string)$entity->get('client')),
                __d('expenses', 'Description') => h((string)$entity->get('descript')),
                __d('expenses', 'Debit') => (string)$entity->get('debit'),
                __d('expenses', 'Credit') => (string)$entity->get('credit'),
            ],
            'Invoice' => [
                __d('expenses', 'No') => h((string)$entity->get('no')),
                __d('expenses', 'Date') => h((string)$entity->get('dat_issue')),
                __d('expenses', 'Title') => h((string)($entity->get('title') ?? '')),
            ],
            'TravelOrder' => [
                __d('expenses', 'No') => h((string)$entity->get('no')),
                __d('expenses', 'Date') => h((string)$entity->get('dat_issue')),
                __d('expenses', 'Title') => h((string)($entity->get('title') ?? '')),
            ],
            default => ['ID' => h((string)$entity->get('id'))],
        };
    }

    /**
     * Returns the description string to stamp on every booking entry row.
     *
     * @param \Cake\Datasource\EntityInterface $entity Source entity.
     * @param string $model Model name.
     * @return string
     */
    public function entityDescript(EntityInterface $entity, string $model): string
    {
        return (string)($entity->get('descript') ?? '');
    }

    /**
     * Builds the editable form-row data for the booking entries form.
     *
     * Tries in order:
     * 1. Prefill from existing draft entries (when present and not locked).
     * 2. Propose entries from the first matching BookingRule.
     * 3. Fall back to a single empty row.
     *
     * Returns an empty array when all existing entries are already in a
     * locked/posted order (caller should not show the edit form).
     *
     * @param \Cake\Collection\CollectionInterface<\Expenses\Model\Entity\BookingOrderEntry> $existing Existing entries.
     * @param bool $hasLocked Whether any existing entry belongs to a locked/posted order.
     * @param \Cake\Datasource\EntityInterface $entity Source entity.
     * @param string $model Model name.
     * @param string $ownerId Current company id.
     * @return array<int, array<string, mixed>>
     */
    public function buildDisplayEntries(
        CollectionInterface $existing,
        bool $hasLocked,
        EntityInterface $entity,
        string $model,
        string $ownerId,
        int $nextNo = 1,
    ): array {
        // Prefill from existing entries (draft or locked).
        if (!$existing->isEmpty()) {
            return $existing->map(fn($e) => [
                'id' => (string)$e->id,
                'no' => (int)$e->no,
                'account_id' => (string)$e->account_id,
                'account_label' => $e->account ? (string)$e->account : '',
                'debit' => (string)$e->debit,
                'credit' => (string)$e->credit,
            ])->toList();
        }

        // Propose from a matching BookingRule.
        $matcher = new BookingRuleMatcher();
        $rule = $matcher->findMatchingRule($entity, $model, $ownerId);
        if ($rule) {
            $proposed = $matcher->buildProposedEntries($rule, $entity);
            if (!empty($proposed)) {
                $accountIds = array_unique(array_column($proposed, 'account_id'));
                /** @var \Expenses\Model\Table\AccountsTable $acctTable */
                $acctTable = TableRegistry::getTableLocator()->get('Expenses.Accounts');
                $accounts = $acctTable->find()
                    ->where(['id IN' => $accountIds])
                    ->all()
                    ->indexBy('id')
                    ->toArray();

                $result = [];
                $position = $nextNo;
                foreach ($proposed as $pe) {
                    $result[] = [
                        'id' => null,
                        'no' => $position++,
                        'account_id' => (string)$pe['account_id'],
                        'account_label' => isset($accounts[$pe['account_id']])
                            ? (string)$accounts[$pe['account_id']] : '',
                        'debit' => $pe['debit'],
                        'credit' => $pe['credit'],
                    ];
                }

                return $result;
            }
        }

        // Default: one empty row.
        return [[
            'id' => null,
            'no' => $nextNo,
            'account_id' => '',
            'account_label' => '',
            'debit' => '0.00',
            'credit' => '0.00',
        ]];
    }
}
