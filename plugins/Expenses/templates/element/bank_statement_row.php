<?php
declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 * @var \Expenses\Model\Entity\BankStatement $bankStatement
 * @var \Expenses\Filter\BankStatementsFilter $docFilter
 */
?>
<div class="panel-row">
    <div class="title">
        <?= $this->Html->link(h($bankStatement->no), ['action' => 'view', $bankStatement->id]) ?>
        <div class="details">
            <?= h($bankStatement->iban) ?>
            · <?= h((string)$bankStatement->dat_issue) ?>
            · <?= h($bankStatement->currency) ?>
            <?php if ($bankStatement->user) : ?>
                · <?= h($bankStatement->user->name) ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="total">
        <span class="green-text">+<?= $this->Number->currency((float)$bankStatement->total_credit) ?></span>
        &nbsp;
        <span class="red-text">-<?= $this->Number->currency((float)$bankStatement->total_debit) ?></span>
        &nbsp;
        <?= $this->Number->currency((float)$bankStatement->saldo) ?>
    </div>
</div>
