<?php
    use Cake\Routing\Router;
?>
<div class="task-row">
    <div class="checkbox">o</div>
    <div class="status">o</div>
    <div class="title"><?= $this->Html->link(
        $task->title,
        [
            'action' => 'view',
            $task->id,
            '?' => ['redirect' => Router::url()]
        ]) ?></div>
    <div class="details">#<?= $task->no ?> · <?= $users[$task->user_id] ?> opened <?= h($task->created->nice()) ?></div>
</div>