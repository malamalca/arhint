<?php
declare(strict_types=1);

use Expenses\Model\Entity\BookingOrder;

/**
 * @var \App\View\AppView $this
 * @var \Expenses\Model\Entity\BookingOrder $bookingOrder
 * @var \Expenses\Filter\BookingOrdersFilter $docFilter
 */

$statusIconMap = [
    BookingOrder::STATUS_DRAFT => ['icon' => 'edit', 'class' => 'grey-text'],
    BookingOrder::STATUS_POSTED => ['icon' => 'check', 'class' => 'green-text'],
    BookingOrder::STATUS_LOCKED => ['icon' => 'lock', 'class' => 'blue-text'],
];
$iconData = $statusIconMap[$bookingOrder->status ?? ''] ?? ['icon' => 'help_outline', 'class' => 'grey-text'];
$statusLabels = BookingOrder::statusLabels();

$totalDebit = 0.0;
$totalCredit = 0.0;
foreach ($bookingOrder->booking_order_entries ?? [] as $entry) {
    $totalDebit += (float)$entry->debit;
    $totalCredit += (float)$entry->credit;
}
$total = $totalDebit - $totalCredit;

$openerName = $bookingOrder->opener->name ?? '';

?>
<div class="panel-row <?= h($bookingOrder->status ?? '') ?>">
    <div class="checkbox"><input type="checkbox" /></div>
    <div class="status <?= $iconData['class'] ?>">
        <i class="material-icons"
            title="<?= h($statusLabels[$bookingOrder->status ?? ''] ?? '') ?>"
        ><?= $iconData['icon'] ?></i>
    </div>
    <div class="title">
        <?= $this->Html->link(h($bookingOrder->title), ['action' => 'view', $bookingOrder->id]) ?>
        <div class="details">
            #<?= h($bookingOrder->no) ?>
            · <?= h((string)$bookingOrder->date_created) ?>
            <?php if ($openerName) : ?>
                · <?= $this->Html->link(
                    h($openerName),
                    ['?' => array_merge(
                        $this->getRequest()->getQuery(),
                        ['q' => $docFilter->buildQuery(
                            'opener',
                            $docFilter->check('opener', $openerName) ? null : $openerName,
                        )],
                    )],
                    ['class' => 'details-link' . ($docFilter->check('opener', $openerName) ? ' active' : ''), 'style' => 'display:inline'],
                ) ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="total">
        <?= $this->Number->currency($total) ?>
    </div>
</div>
