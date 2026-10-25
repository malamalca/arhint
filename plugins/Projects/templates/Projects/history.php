<?php
/**
 * History of the knowledge base of a project.
 *
 * @var \App\View\AppView $this
 * @var \Projects\Model\Entity\Project $project
 * @var iterable<\Projects\Model\Entity\ProjectsKbRevision> $revisions
 * @var array<int, array{0: string, 1: string}>|null $diff
 * @var mixed $version
 */

$sources = [
    'batch' => __d('projects', 'Automatic update'),
    'chat' => __d('projects', 'Conversation'),
    'manual' => __d('projects', 'Manual edit'),
    'backfill' => __d('projects', 'Conversion'),
    'tool' => __d('projects', 'Assistant'),
    'revert' => __d('projects', 'Return to a version'),
];

$latest = null;
foreach ($revisions as $revision) {
    $latest ??= $revision->version;
}

$tableIndex = [
    'title_for_layout' => __d('projects', 'Knowledge base history') . ': ' . h($project->no . ' ' . $project->title),
    'menu' => [
        'view' => [
            'title' => __d('projects', 'Back to project'),
            'visible' => true,
            'url' => ['action' => 'view', $project->id],
        ],
    ],
    'table' => [
        'parameters' => ['width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0],
        'head' => ['rows' => [['columns' => [
            'version' => __d('projects', 'Version'),
            'created' => __d('projects', 'Time'),
            'source' => __d('projects', 'Source'),
            'user' => __d('projects', 'User'),
            'summary' => __d('projects', 'Change'),
            'actions' => [],
        ]]]],
    ],
];

foreach ($revisions as $revision) {
    $actions = $this->Html->link(
        __d('projects', 'Changes'),
        ['action' => 'history', $project->id, '?' => ['version' => $revision->version]],
    );
    if ($revision->version !== $latest && $revision->version > 0) {
        $actions .= ' ' . $this->Form->postLink(
            __d('projects', 'Return to this version'),
            ['action' => 'revert', $revision->id],
            ['confirm' => __d('projects', 'Return the knowledge base to version {0}?', $revision->version)],
        );
    }

    $tableIndex['table']['body']['rows'][]['columns'] = [
        'version' => (string)$revision->version,
        'created' => $revision->created ? h($revision->created->i18nFormat('dd.MM.yyyy HH:mm')) : '',
        'source' => h($sources[$revision->source] ?? $revision->source),
        'user' => h($revision->user->name ?? ($revision->user_id ? '' : 'AI')),
        'summary' => h((string)$revision->summary),
        'actions' => ['parameters' => ['class' => 'right-align'], 'html' => $actions],
    ];
}

echo $this->Lil->index($tableIndex, 'Projects.Projects.history');

if ($diff !== null) {
    echo '<h5>' . h(__d('projects', 'Changes in version {0}', $version)) . '</h5>';
    if ($diff === []) {
        echo '<p>' . h(__d('projects', 'No changes.')) . '</p>';
    }
    echo '<pre class="kb-diff" style="white-space: pre-wrap;">';
    foreach ($diff as [$type, $line]) {
        $style = match ($type) {
            'added' => 'background: #e6ffed;',
            'removed' => 'background: #ffeef0; text-decoration: line-through;',
            default => 'color: #888;',
        };
        $prefix = match ($type) {
            'added' => '+ ',
            'removed' => '- ',
            default => '  ',
        };
        echo '<div style="' . $style . '">' . h($prefix . $line) . '</div>';
    }
    echo '</pre>';
}
