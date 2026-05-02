<?php
/**
 * @var \App\View\AppView $this
 * @var array<\Expenses\Model\Entity\Account> $accounts
 */
?>
<div>
    <div style="margin-bottom:10px">
        <input type="text" id="account-pick-filter"
               placeholder="<?= h(__d('expenses', 'Filter…')) ?>"
               autocomplete="off" class="browser-default"
               style="width:100%;box-sizing:border-box;padding:6px 8px;border:1px solid #9e9e9e"/>
    </div>
    <table class="striped" style="width:100%">
        <thead>
            <tr>
                <th><?= __d('expenses', 'Code') ?></th>
                <th><?= __d('expenses', 'Name') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($accounts as $account) : ?>
                <?php $isLeaf = $account->rght === $account->lft + 1; ?>
            <tr<?= $isLeaf ? ' class="account-pick-row" style="cursor:pointer"' : ' class="grey-text"' ?>
                data-id="<?= h($account->id) ?>"
                data-code="<?= h($account->code) ?>"
                data-name="<?= h($account->name) ?>">
                <td class="nowrap"><?= h($account->code) ?></td>
                <td><?= str_repeat('&nbsp;&nbsp;&nbsp;', max(0, ($account->level ?? 1) - 1)) . h($account->name) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
