<?php

$templatesIndex = [
    'title_for_layout' => __d('documents', 'Templates'),
    'menu' => [
        'add' => [
            'title' => __d('documents', 'Add'),
            'visible' => $this->getCurrentUser()->hasRole('editor'),
            'url' => [
                'plugin' => 'Documents',
                'controller' => 'DocumentsTemplates',
                'action' => 'edit',
            ],
        ],
    ],
    'table' => [
        'parameters' => [
            'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0, 'id' => 'AdminTemplatesIndex',
        ],
        'head' => ['rows' => [['columns' => [
            'title' => __d('documents', 'Title'),
            'actions' => [],
        ]]]],
    ],
];

foreach ($templates as $template) {
    $templatesIndex['table']['body']['rows'][]['columns'] = [
        'title' => h($template->title),
        'actions' => !$this->getCurrentUser()->hasRole('editor') ? '' : [
            'parameters' => ['class' => 'right-align'],
            'html' => $this->Lil->editLink($template->id) . ' ' . $this->Lil->deleteLink($template->id),
        ],
    ];
}

echo $this->Lil->index($templatesIndex, 'Documents.DocumentsTemplates.index');
