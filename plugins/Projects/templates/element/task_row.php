<?php
declare(strict_types=1);

use Cake\I18n\Date;
use Cake\Routing\Router;
use Projects\Lib\ProjectsFuncs;

switch ($task->status) {
    case ProjectsFuncs::STATUS_OPEN:
        $taskImageClass = 'opened';
        $taskImage = 'issue-opened-16.svg';
        break;
    case ProjectsFuncs::STATUS_REOPENED:
        $taskImageClass = 'reopened';
        $taskImage = 'issue-reopened-16.svg';
        break;
    case ProjectsFuncs::STATUS_CLOSED:
        $taskImageClass = 'closed';
        $taskImage = 'issue-closed-16.svg';
        break;
    default:
        $taskImageClass = 'invalid';
        $taskImage = 'skip-16.svg';
}

?>

<div class="panel-row task-row<?= $task->status === ProjectsFuncs::STATUS_CLOSED ? ' done' : '' ?>">
    <div class="checkbox"><?= $this->Form->checkbox('ids[]', ['value' => $task->id, 'hiddenField' => false]); ?></div>
    <div class="status"><?= $this->Html->image('/projects/img/' . $taskImage, ['class' => $taskImageClass]) ?></div>
    <div class="title"><?= $this->Html->link(
        $task->title,
        [
            'action' => 'view',
            $task->id,
            '?' => ['redirect' => Router::url(null, true)],
        ],
    ) ?>
        <div class="details">
        #<?= $task->no ?> ·
        <?php
            $details = [];

            // Created date
            $details[] = $this->Html->image('/projects/img/calendar-16.svg') . ' ' .
                (new Date($task->created))->nice();

            // Assigned user
        if (!empty($task->assigned_user_id) && isset($users[$task->assigned_user_id])) {
            $details[] = $this->Html->image('/projects/img/person-16.svg') . ' ' .
                __d('projects', 'Assigned to {0}', h((string)$users[$task->assigned_user_id]));
        }

            // Closing date
        if (!empty($task->closed)) {
            $details[] = $this->Html->image('/projects/img/issue-closed-16.svg') . ' ' .
                __d('projects', 'Closed {0}', h((new Date($task->closed))->nice()));
        }

            echo implode(' · ', $details);
        ?>
        </div>
    </div>
</div>