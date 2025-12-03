<?php
    use Cake\Routing\Router;
?>
<div class="task-row">
    <div class="checkbox"><input type="checkbox" /></div>
    <div class="status"><?= $this->Html->image(
        sprintf('/projects/img/issue-%s-16.svg', empty($task->date_complete) ? 'opened' : 'closed'),
        ['class' => empty($task->date_complete) ? 'opened' : 'closed'],
    ) ?></div>
    <div class="title"><?= $this->Html->link(
        $task->title,
        [
            'action' => 'edit',
            $task->id,
            '?' => ['redirect' => Router::url(null, true)],
        ],
    ) ?></div>
    <div class="details">#<?= $task->no ?> · <?= $users[$task->user_id] ?> opened <?= h($task->created->nice()) ?></div>
</div>