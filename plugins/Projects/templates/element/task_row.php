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

<div class="task-row">
    <div class="checkbox"><input type="checkbox" /></div>
    <div class="status"><?= $this->Html->image('/projects/img/' . $taskImage, ['class' => $taskImageClass]) ?></div>
    <div class="title"><?= $this->Html->link(
        $task->title,
        [
            'action' => 'view',
            $task->id,
            '?' => ['redirect' => Router::url(null, true)],
        ],
    ) ?></div>
    <div class="details">
        #<?= $task->no ?> ·
        <?php
            $userLink = $this->Html->link(
                (string)$users[$task->user_id],
                [$project->id, '?' => ['q' => $filter->buildQuery('user', (string)$users[$task->user_id])]],
            );
            if ($task->status === ProjectsFuncs::STATUS_OPEN) {
                echo __('{0} opened {1}', $userLink, h((new Date($task->created))->nice()));
            } else {
                echo __('{0} closed {1}', $userLink, h((new Date($task->date_complete))->nice()));
            }

            if (isset($milestones[$task->milestone_id])) {
                $milestoneLink = $this->Html->link(
                    (string)$milestones[$task->milestone_id],
                    [
                        $project->id,
                        '?' => ['q' => $filter->buildQuery('milestone', (string)$milestones[$task->milestone_id])],
                    ],
                );

                echo ' · ' . $this->Html->image('/projects/img/milestone-16.svg') . ' ' . $milestoneLink;
            }
        ?>
    </div>
</div>