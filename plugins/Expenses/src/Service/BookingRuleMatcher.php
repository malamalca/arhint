<?php
declare(strict_types=1);

namespace Expenses\Service;

use Cake\Datasource\EntityInterface;
use Cake\ORM\TableRegistry;
use Expenses\Model\Entity\BookingRule;
use Expenses\Model\Entity\BookingRuleFilter;

/**
 * BookingRuleMatcher
 *
 * Finds the first BookingRule whose filters match the given entity, then
 * builds a proposed list of BookingOrderEntry data from the rule's account entries.
 *
 * Usage:
 *   $matcher = new BookingRuleMatcher();
 *   $rule    = $matcher->findMatchingRule($entity, 'Invoices', $ownerId);
 *   if ($rule) {
 *       $proposed = $matcher->buildProposedEntries($rule, $entity);
 *   }
 */
class BookingRuleMatcher
{
    /**
     * Find the first BookingRule whose filters match the given entity.
     *
     * @param \Cake\Datasource\EntityInterface $entity    Source entity (Invoice, TravelOrder, …)
     * @param string                           $modelName Model name, e.g. 'Invoices'
     * @param string                           $ownerId   Company/owner UUID
     * @return \Expenses\Model\Entity\BookingRule|null
     */
    public function findMatchingRule(
        EntityInterface $entity,
        string $modelName,
        string $ownerId,
    ): ?BookingRule {
        /** @var \Expenses\Model\Table\BookingRulesTable $rulesTable */
        $rulesTable = TableRegistry::getTableLocator()->get('Expenses.BookingRules');

        /** @var array<\Expenses\Model\Entity\BookingRule> $rules */
        $rules = $rulesTable->find()
            ->where([
                'owner_id' => $ownerId,
                'model' => $modelName,
            ])
            ->contain([
                'BookingRuleFilters',
                'BookingRuleAccountEntries' => ['Accounts'],
            ])
            ->orderBy(['BookingRules.title' => 'ASC'])
            ->all();

        foreach ($rules as $rule) {
            if ($this->ruleMatchesEntity($rule, $entity)) {
                return $rule;
            }
        }

        return null;
    }

    /**
     * Build an array of proposed BookingOrderEntry data from the matched rule
     * and the source entity's field values.
     *
     * Each element has the same shape as a new BookingOrderEntry:
     *   ['account_id' => int, 'descript' => string, 'debit' => string, 'credit' => string]
     *
     * The `value` stored in BookingRuleAccountEntry is a field name on the entity
     * (e.g. 'net_total', 'total') or a plain numeric literal.  Positive resolved
     * values go into `debit`; negative values go into `credit` (absolute).
     *
     * @param \Expenses\Model\Entity\BookingRule $rule Matched rule
     * @param \Cake\Datasource\EntityInterface $entity Source entity
     * @return array<int, array<string, mixed>>
     */
    public function buildProposedEntries(BookingRule $rule, EntityInterface $entity): array
    {
        $proposed = [];
        $no = 1;

        foreach ($rule->booking_rule_account_entries as $ruleEntry) {
            $amount = $this->resolveValue($ruleEntry->value, $entity);
            $abs = abs($amount);

            $proposed[] = [
                'account_id' => $ruleEntry->account_id,
                'no' => $no++,
                'descript' => '',
                'debit' => $amount >= 0 ? number_format($abs, 2, '.', '') : '0.00',
                'credit' => $amount < 0 ? number_format($abs, 2, '.', '') : '0.00',
            ];
        }

        return $proposed;
    }

    /**
     * Evaluate whether all filters of a rule match the given entity.
     *
     * @param \Expenses\Model\Entity\BookingRule  $rule   Booking rule
     * @param \Cake\Datasource\EntityInterface    $entity Source entity
     * @return bool
     */
    private function ruleMatchesEntity(BookingRule $rule, EntityInterface $entity): bool
    {
        $filters = $rule->booking_rule_filters ?? [];
        if (empty($filters)) {
            return true;
        }

        $tokens = $this->buildTokenStream($filters, $entity);
        if (empty($tokens)) {
            return true;
        }

        $pos = 0;
        $result = $this->parseOr($tokens, $pos);

        return $result;
    }

    /**
     * Build a flat token stream from filter rows.
     *
     * Each filter is converted to:
     *   <left_bracket_count × '('> <bool> <right_bracket_count × ')'> [end_operator]
     *
     * @param array<\Expenses\Model\Entity\BookingRuleFilter> $filters
     * @param \Cake\Datasource\EntityInterface $entity
     * @return array<int, bool|string>
     */
    private function buildTokenStream(array $filters, EntityInterface $entity): array
    {
        $tokens = [];

        foreach ($filters as $filter) {
            for ($i = 0; $i < (int)$filter->left_bracket_count; $i++) {
                $tokens[] = '(';
            }

            $tokens[] = $this->evaluateCondition($filter, $entity);

            for ($i = 0; $i < (int)$filter->right_bracket_count; $i++) {
                $tokens[] = ')';
            }

            if (!empty($filter->end_operator)) {
                $tokens[] = strtolower((string)$filter->end_operator);
            }
        }

        return $tokens;
    }

    /**
     * Recursive-descent: OR level.
     *
     * @param array<int, bool|string> $tokens
     * @param int $pos Current position (passed by reference)
     * @return bool
     */
    private function parseOr(array $tokens, int &$pos): bool
    {
        $left = $this->parseAnd($tokens, $pos);
        $count = count($tokens);

        while ($pos < $count && $tokens[$pos] === 'or') {
            $pos++;
            $right = $this->parseAnd($tokens, $pos);
            $left = $left || $right;
        }

        return $left;
    }

    /**
     * Recursive-descent: AND level.
     *
     * @param array<int, bool|string> $tokens
     * @param int $pos Current position (passed by reference)
     * @return bool
     */
    private function parseAnd(array $tokens, int &$pos): bool
    {
        $left = $this->parsePrimary($tokens, $pos);
        $count = count($tokens);

        while ($pos < $count && $tokens[$pos] === 'and') {
            $pos++;
            $right = $this->parsePrimary($tokens, $pos);
            $left = $left && $right;
        }

        return $left;
    }

    /**
     * Recursive-descent: primary (bool literal or parenthesised sub-expression).
     *
     * @param array<int, bool|string> $tokens
     * @param int $pos Current position (passed by reference)
     * @return bool
     */
    private function parsePrimary(array $tokens, int &$pos): bool
    {
        if (isset($tokens[$pos]) && $tokens[$pos] === '(') {
            $pos++;
            $result = $this->parseOr($tokens, $pos);
            if (isset($tokens[$pos]) && $tokens[$pos] === ')') {
                $pos++;
            }

            return $result;
        }

        $value = $tokens[$pos] ?? false;
        $pos++;

        return (bool)$value;
    }

    /**
     * Evaluate a single BookingRuleFilter condition against the entity.
     *
     * @param \Expenses\Model\Entity\BookingRuleFilter $filter Filter row
     * @param \Cake\Datasource\EntityInterface $entity Source entity
     * @return bool
     */
    private function evaluateCondition(BookingRuleFilter $filter, EntityInterface $entity): bool
    {
        $fieldValue = (string)($entity->get($filter->field) ?? '');
        $ruleValue = (string)$filter->value;

        return match ($filter->operator) {
            'isEqual' => $fieldValue === $ruleValue,
            'isNotEqual' => $fieldValue !== $ruleValue,
            'startsWith' => str_starts_with($fieldValue, $ruleValue),
            'endsWith' => str_ends_with($fieldValue, $ruleValue),
            'contains' => str_contains($fieldValue, $ruleValue),
            'notContains' => !str_contains($fieldValue, $ruleValue),
            'isGreaterThan' => $fieldValue > $ruleValue,
            'isLessThan' => $fieldValue < $ruleValue,
            'isEmpty' => $fieldValue === '',
            'isNotEmpty' => $fieldValue !== '',
            default => false,
        };
    }

    /**
     * Resolve a value string from a BookingRuleAccountEntry against the entity.
     *
     * If `$valueExpr` is a field name present on the entity, its numeric value
     * is returned.  Otherwise the expression is cast to float (allowing plain
     * numeric literals like '150.00' or '-50').
     *
     * @param string $valueExpr Field name or numeric literal
     * @param \Cake\Datasource\EntityInterface $entity Source entity
     * @return float
     */
    private function resolveValue(string $valueExpr, EntityInterface $entity): float
    {
        $trimmed = trim($valueExpr);

        // Check if it looks like a field reference (alphanumeric + underscore, no spaces)
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $trimmed)) {
            $fieldValue = $entity->get($trimmed);
            if ($fieldValue !== null) {
                return (float)$fieldValue;
            }
        }

        return (float)$trimmed;
    }
}
