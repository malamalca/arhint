<div class="task-row">
    <div class="checkbox">o</div>
    <div class="status">o</div>
    <div class="title"><?= $this->Html->link($task->title, ['action' => 'edit', $task->id]) ?></div>
    <div class="details">#<?= $task->no ?> · <?= $users[$task->user_id] ?> opened <?= h($task->created->nice()) ?></div>
</div>