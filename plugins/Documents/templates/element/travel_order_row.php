<?php
declare(strict_types=1);

use Documents\Model\Entity\TravelOrder;

/**
 * @var \App\View\AppView $this
 * @var \Documents\Model\Entity\TravelOrder $travelOrder
 * @var \Documents\Filter\TravelOrdersFilter $docFilter
 */

$statusIconMap = [
    TravelOrder::STATUS_WAITING_APPROVAL => ['icon' => 'hourglass_empty', 'class' => 'grey-text'],
    TravelOrder::STATUS_APPROVED => ['icon' => 'check_circle', 'class' => 'green-text'],
    TravelOrder::STATUS_WAITING_PROCESSING => ['icon' => 'schedule', 'class' => 'orange-text'],
    TravelOrder::STATUS_COMPLETED => ['icon' => 'done_all', 'class' => 'green-text'],
];
$iconData = $statusIconMap[$travelOrder->status ?? ''] ?? ['icon' => 'help_outline', 'class' => 'grey-text'];
$statusLabels = TravelOrder::statusLabels();

$departure = $travelOrder->departure ? $travelOrder->departure->format('d.m.Y H:i') : '';
$arrival = $travelOrder->arrival ? $travelOrder->arrival->format('d.m.Y H:i') : '';
?>
<div class="panel-row <?= h($travelOrder->status ?? '') ?>">
    <div class="checkbox"><input type="checkbox" /></div>
    <div class="status <?= $iconData['class'] ?>">
        <i class="material-icons"
            title="<?= h($statusLabels[$travelOrder->status ?? ''] ?? '') ?>"
        ><?= $iconData['icon'] ?></i>
    </div>
    <div class="title">
        <?= $this->Html->link(h($travelOrder->title), ['action' => 'view', $travelOrder->id]) ?>
        <div class="details">
            #<?= h($travelOrder->no) ?>
            <?php if ($departure) : ?>
                · <i class="material-icons tiny">flight_takeoff</i><?= h($departure) ?>
                <?php if ($arrival) : ?>
                    → <i class="material-icons tiny">flight_land</i><?= h($arrival) ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="employee">
        <?= $this->Html->link(
            h($travelOrder->employee->name ?? ''),
            ['?' => array_merge(
                $this->getRequest()->getQuery(),
                ['q' => $docFilter->buildQuery(
                    'employee',
                    $docFilter->check('employee', $travelOrder->employee->name ?? '')
                        ? null
                        : ($travelOrder->employee->name ?? ''),
                )],
            )],
            ['class' => $docFilter->check('employee', $travelOrder->employee->name ?? '') ? 'active' : ''],
        ) ?>
    </div>
    <?php if ($travelOrder->total !== null) : ?>
    <div class="total">
        <?= $this->Number->currency((float)$travelOrder->total) ?>
    </div>
    <?php endif; ?>
</div>
