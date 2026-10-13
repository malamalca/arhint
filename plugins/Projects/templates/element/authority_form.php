<?php
use Cake\Utility\Hash;

/**
 * Shared Lil form layout for authority procedure screens.
 *
 * @var \App\View\AppView $this
 */
if ($entity->hasErrors()) {
    echo '<div class="card-panel error-message" role="alert"><ul>';
    foreach (array_unique(Hash::flatten($entity->getErrors())) as $message) {
        echo '<li>' . h($message) . '</li>';
    }
    echo '</ul></div>';
}
$lines = [
    'start' => ['method' => 'create', 'parameters' => [$entity]],
];
if (isset($fields['contact_id'])) {
    $lines['unlock_contact'] = ['method' => 'unlockField', 'parameters' => ['contact_id']];
}
if (isset($summary)) {
    $lines['snapshot'] = [
        'method' => 'hidden',
        'parameters' => ['snapshot_hash', ['value' => $summary['snapshotHash']]],
    ];
}
foreach ($fields as $field => $options) {
    $lines[$field] = ['method' => 'control', 'parameters' => [$field, $options]];
}
$lines['submit'] = [
    'method' => 'button',
    'parameters' => [__d('projects', 'Save'), ['type' => 'submit', 'class' => 'btn-small filled']],
];
$lines['end'] = ['method' => 'end'];
echo $this->Lil->form([
    'title_for_layout' => '<div class="small">' . h($project->getName()) . '</div>' . h($title),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => $lines,
    ],
], $formEvent);
echo $this->Html->link(__d('projects', 'Back to project'), [
    'controller' => 'Projects', 'action' => 'view', $project->id, '?' => ['tab' => 'authorities'],
], ['class' => 'btn-small tonal']);
