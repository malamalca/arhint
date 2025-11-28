<?php
    use Cake\Routing\Router;
?>
<div class="title"><?= $this->Html->image('/projects/img/milestone-16.svg') ?> 0 open 0 closed</div>
<?php

foreach ($milestones as $milestone):
    $completedPercent = ($milestone->tasks_done + $milestone->tasks_open) > 0 ? round($milestone->tasks_done / ($milestone->tasks_done + $milestone->tasks_open), 2) * 100 : 0;
?>
    <div class="milestone-item">
        <div class="milestone-info">
            <div class="milestone-title">
                <?= $this->Html->link(h($milestone->title), [
                    'controller' => 'ProjectsTasks',
                    'action' => 'index',
                    '?' => ['project' => $milestone->project_id, 'q' => 'milestone:' . escapeshellarg($milestone->title)]]) ?>
            </div>
            
            <span><?= $this->Html->image('/projects/img/calendar-16.svg') ?><?= __d('projects', 'Due') ?>: <?= $milestone->date_due ? h($milestone->date_due->nice()) : __d('projects', 'not set') ?></span>
            <span><?= $this->Html->image('/projects/img/clock-16.svg') ?><?= __d('projects', 'Last updated') ?> <?= h($milestone->modified->timeAgoInWords()) ?></span>
        </div>

        <div class="milestone-progress">
            <div class="total" style="width: 100%;">
                <div class="completed" style="width: <?= $completedPercent ?>%;"></div>
            </div>
            <div class="labels">
                <span><strong><?= $completedPercent ?> %</strong> complete</span>
                <span><a href="<?= $this->Url->build([
                    'controller' => 'ProjectsTasks',
                    'action' => 'index',
                    '?' => [
                        'project' => $milestone->project_id,
                        'q' => 'milestone:' . escapeshellarg($milestone->title) . ' status:open',
                    ]
                ]) ?>"><strong><?= $milestone->tasks_open ?></strong> open</a></span>
                <span><a href="<?= $this->Url->build([
                    'controller' => 'ProjectsTasks',
                    'action' => 'index',
                    '?' => [
                        'project' => $milestone->project_id,
                        'q' => 'milestone:' . escapeshellarg($milestone->title) . ' status:closed',
                    ],
                ]) ?>"><strong><?= $milestone->tasks_done ?></strong> closed</a></span>
            </div>
            <div class="actions">
                <?= $this->Html->link(
                        __d('projects', 'Add Task'),
                        [
                            'controller' => 'ProjectsTasks',
                            'action' => 'edit',
                            '?' => [
                                'project' => $milestone->project_id,
                                'milestone' => $milestone->id,
                                'redirect' => Router::url(),
                            ],
                        ],
                        ['class' => 'btn btn-small text btn-add-task', 'onclick' => 'popup(); return false;']
                    )
                ?>
                <?= $this->Html->link(
                    __d('projects', 'Edit'),
                    [
                        'controller' => 'ProjectsMilestones',
                        'action' => 'edit',
                        $milestone->id,
                        '?' => ['redirect' => Router::url()],
                    ],
                    ['class' => 'btn btn-small text btn-edit-milestone']
                ) ?>
                <?= $this->Html->link(
                    __d('projects', 'Close'),
                    [
                        'controller' => 'ProjectsMilestones',
                        'action' => 'close',
                        $milestone->id
                    ],
                    ['class' => 'btn btn-small text', 'confirm' => __d('projects', 'Are you sure?')]
                ) ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>