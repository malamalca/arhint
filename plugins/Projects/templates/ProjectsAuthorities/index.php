<?php
echo $this->Lil->panels([
    'title_for_layout' => '<div class="small">' . h($project->getName()) . '</div>' .
        h(__d('projects', 'Conditions and opinions')),
    'menu' => [
        'add' => [
            'title' => __d('projects', 'Add'),
            'visible' => $this->getCurrentUser()->hasRole('editor'),
            'submenu' => [
                'authority' => [
                    'title' => __d('projects', 'Add authority'),
                    'url' => ['action' => 'editCase', '?' => ['project' => $project->id]],
                ],
            ],
        ],
    ],
    'panels' => [
        'procedures' => ['lines' => [
            $this->element('Projects.authorities_list', compact('project', 'cases', 'summary', 'reviews')),
        ]],
        'back' => ['lines' => [$this->Html->link(__d('projects', 'Back to project'), [
            'controller' => 'Projects', 'action' => 'view', $project->id, '?' => ['tab' => 'authorities'],
        ], ['class' => 'btn-small tonal'])]],
    ],
 ], 'Projects.ProjectsAuthorities.index');
