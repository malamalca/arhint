<?php
declare(strict_types=1);

namespace Tasks\Lib;

use App\View\AppView;
use Cake\I18n\DateTime;
use Cake\Chronos\ChronosDate;
use Cake\ORM\TableRegistry;
use Tasks\Model\Entity\Task;

class TasksUtils
{
    /**
     * Returns tasks panel
     *
     * @param \Tasks\Model\Entity\Task $task Task entity
     * @param \App\View\AppView $view View Class
     * @return array<string, mixed>
     */
    public static function taskPanel(Task $task, AppView $view): array
    {
        /** @var \App\Model\Table\UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('App.Users');
        $users = $UsersTable->fetchForCompany($view->getCurrentUser()->get('company_id'));

        $panelClass = 'lil-task-item';
        $tickImage = $view->Html->image('/tasks/img/tick_empty.png');

        $prioritySpan = '';
        switch ($task->priority) {
            case 2:
                $prioritySpan = '<span class="lil-task-priority-high">!</span> ';
                break;
            default:
                $prioritySpan = '';
        }

        $dueSpan = '';
        if (!empty($task->deadline)) {
            $dueSpanClass = 'lil-task-due';
            $dueDate = $task->deadline->timeAgoInWords(['accuracy' => 'hour'/* TODO: , 'end' => 30*/]);

            if ($view->Time->isToday($task->deadline)) {
                $dueSpanClass = 'lil-task-duetoday';
                if ($task->deadline->notEquals(new ChronosDate('today')) && $view->Time->isPast($task->deadline)) {
                    $dueSpanClass = 'lil-task-overdue';
                } else {
                    // time equals 00:00:00
                    $dueDate = __d('tasks', 'today');
                }
            } elseif ($view->Time->isTomorrow($task->deadline)) {
                $dueDate = __d('tasks', 'tomorrow');
            } elseif ($view->Time->isPast($task->deadline)) {
                $dueSpanClass = 'lil-task-overdue';
            }
            $dueSpan = __d('tasks', ' :: due {0}', sprintf(
                '<span class="%1$s">%2$s</span> ',
                $dueSpanClass,
                $dueDate,
            ));
        }

        if (!empty($task->completed)) {
            $panelClass .= ' lil-task-item-completed';
            $tickImage = $view->Html->image('/tasks/img/tick_completed.png');
        }

        $usersDescript = '';

        if (empty($task->tasker_id)) {
            if ($task->user_id != $view->getCurrentUser()->id) {
                $usersDescript = __d('tasks', '"{0}" to Anyone', h($users[$task->user_id]->name ?? ''));
            } else {
                $usersDescript = __d('tasks', 'Me to Anyone');
            }
        } else {
            if ($task->user_id != $view->getCurrentUser()->id) {
                $usersDescript = __d('tasks', '"{0}" to Me', h($users[$task->user_id]->name ?? ''));
            } else {
                $usersDescript = __d('tasks', 'For myself');
            }

            if ($task->tasker_id != $view->getCurrentUser()->id) {
                $usersDescript = __d('tasks', '"{0}" from Me', h($users[$task->tasker_id]->name ?? ''));
            }
        }

        $taskPanel = [
            'params' => ['class' => $panelClass],
            'lines' => [
                'actions' => '<div class="lil-task-swipe-actions">' .
                    $view->Html->link(__d('tasks', 'Edit'), [
                        'plugin' => 'Tasks',
                        'controller' => 'Tasks',
                        'action' => 'edit',
                        $task->id,
                    ], ['class' => 'lil-task-swipe-edit']) .
                    $view->Html->link(__d('tasks', 'Delete'), [
                        'plugin' => 'Tasks',
                        'controller' => 'Tasks',
                        'action' => 'delete',
                        $task->id,
                    ], [
                        'class' => 'lil-task-swipe-delete',
                        'confirm' => __d('tasks', 'Are you sure you want to delete this task?'),
                    ]) .
                    '</div>',
                'tick' => $view->Html->link($tickImage, [
                        'plugin' => 'Tasks',
                        'controller' => 'Tasks',
                        'action' => 'toggle',
                        $task->id,
                    ], [
                        'escape' => false,
                        'class' => 'lil-task-tick',
                    ]),

                'title' => $prioritySpan .
                    $view->getRequest()->is('mobile') ? h($task->title) : $view->Html->link(
                        $task->title,
                        [
                            'plugin' => 'Tasks',
                            'controller' => 'Tasks',
                            'action' => 'edit',
                            $task->id,
                        ],
                        [
                            'class' => 'lil-task-edit',
                        ],
                    ),
                'descript' => sprintf('<div class="userdue">%s</div>', $usersDescript . $dueSpan),
                'descript2' => empty($task->descript) ?
                    null :
                    sprintf('<div class="descript">%s</div>', h($task->descript)),
            ],
        ];

        return $taskPanel;
    }
}
