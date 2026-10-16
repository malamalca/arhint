<?php
use App\AppPluginsEnum;
use Projects\Lib\AuthorityWorkflow;

$rows = [];
$last = $summary['rows'][$case->id]['DGD']['last'];
$calendarActions = [];
$canUseCalendar = $this->getCurrentUser()->hasAccess(AppPluginsEnum::Calendar);
if ($canUseCalendar && ($calendarLink || ($last && $last->date_due))) {
    $calendarActions[] = $this->Form->postLink(
        $calendarLink ? __d('projects', 'Remove calendar link') : __d('projects', 'Add deadline to my calendar'),
        ['action' => 'calendar', $case->id],
        ['class' => 'btn-small tonal', 'data' => ['remove' => $calendarLink ? '1' : '0']],
    );
}
if ($calendarLink && $calendarLink->event_id && $last && $last->date_due) {
    $calendarActions[] = $this->Html->link(__d('projects', 'Open calendar'), [
        'plugin' => 'Calendar', 'controller' => 'Events', 'action' => 'index',
        '?' => ['month' => $last->date_due->format('Y-m')],
    ], ['class' => 'btn-small tonal']);
}
foreach ($case->events as $event) {
    $documents = '';
    foreach ($event->event_documents as $link) {
        if ($link->document) {
            $documents .= $this->Html->link($link->document->title ?: $link->document->no, [
                'plugin' => 'Documents', 'controller' => 'Documents', 'action' => 'view', $link->document_id,
            ]) . '<br>';
        }
    }
    $eventActions = '';
    if ($this->getCurrentUser()->hasRole('editor')) {
        if (in_array($event->id, $effectiveIds, true)) {
            $eventActions .= $this->Html->link(
                __d('projects', 'Edit'),
                ['action' => 'editEvent', $event->id],
                ['class' => 'btn-small tonal'],
            );
        }
        if (in_array($event->id, $deletableIds, true)) {
            $eventActions .= ' ' . $this->Form->postLink(
                __d('projects', 'Delete'),
                ['action' => 'deleteEvent', $event->id],
                [
                    'class' => 'btn-small tonal',
                    'confirm' => __d('projects', 'Are you sure you want to delete this event?'),
                ],
            );
        }
    }
    $rows[] = ['columns' => [
        'date' => h((string)$event->event_date),
        'kind' => h($event->sequence . '. ' . AuthorityWorkflow::kinds()[$event->kind]) .
            (!in_array($event->id, $selectableIds, true) ? '<br>' . h(__d('projects', 'Invalidated')) : ''),
        'details' => nl2br(h($event->descript ?? '')) . '<br>' . h((string)$event->date_due) .
            ($event->date_valid ? '<br>' . h(__d('projects', 'Valid until') . ': ' . $event->date_valid) : ''),
        'documents' => $documents,
        'author' => h($event->user->name ?? '') . '<br>' . h((string)$event->created),
        'edit' => $eventActions,
    ]];
}
if ($this->getCurrentUser()->hasRole('editor') && !$case->events) {
    $calendarActions[] = $this->Form->postLink(
        __d('projects', 'Delete case'),
        ['action' => 'deleteCase', $case->id],
        ['class' => 'btn-small tonal', 'confirm' => __d('projects', 'Are you sure you want to delete this case?')],
    );
}
$exemptNote = AuthorityWorkflow::isExempt($case) ? ['lines' => [
    '<p><strong>' . h(__d('projects', 'Not required')) . ':</strong> ' . h($case->exempt_reason) . '</p>',
]] : ['lines' => []];
echo $this->Lil->panels([
    'title_for_layout' => '<div class="small">' . h($project->getName()) . '</div>' . h($case->title),
    'menu' => [
        'edit' => [
            'title' => __d('projects', 'Edit'),
            'url' => ['action' => 'editCase', $case->id],
            'visible' => $this->getCurrentUser()->hasRole('editor'),
        ],
        'add_event' => [
            'title' => __d('projects', 'Record procedure event'),
            'url' => ['action' => 'event', $case->id],
            'visible' => $this->getCurrentUser()->hasRole('editor'),
        ],
    ],
    'panels' => [
        'exempt' => $exemptNote,
        'calendar' => ['lines' => $calendarActions],
        'history' => ['table' => [
            'params' => ['class' => 'striped'],
            'head' => ['rows' => [['columns' => [
                'date' => __d('projects', 'Date'), 'kind' => __d('projects', 'Event'),
                'details' => __d('projects', 'Summary / due'),
                'documents' => __d('projects', 'Documents'), 'author' => __d('projects', 'Recorded by'),
                'edit' => '',
            ]]]],
            'body' => ['rows' => $rows],
        ]],
        'back' => ['lines' => [$this->Html->link(__d('projects', 'Back to project'), [
            'controller' => 'Projects', 'action' => 'view', $project->id, '?' => ['tab' => 'authorities'],
        ], ['class' => 'btn-small tonal'])]],
    ],
], 'Projects.ProjectsAuthorities.view');
