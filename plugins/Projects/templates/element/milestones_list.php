<div class="title"><?= $this->Html->image('/projects/img/milestone-16.svg') ?> 0 open 0 closed</div>
<?php foreach ($milestones as $milestone): ?>
    <div class="milestone-item">
        <h3><?= h($milestone->title) ?></h3>
        <div class="dates">
            <?= $this->Html->image('/projects/img/calendar-16.svg') ?><?= __d('projects', 'Due') ?>: <?= h($milestone->due->nice()) ?>
            <?= $this->Html->image('/projects/img/clock-16.svg') ?><?= __d('projects', 'Last updated') ?> <?= h($milestone->modified->timeAgoInWords()) ?>
        </div>
    </div>
    <div class="progress-bar">
        <div class="progress" style="width: <?= $milestone->progress_percentage ?>%;"></div>
    </div>
<?php endforeach; ?></li>