<?php

$templatesIndex = [
    'title_for_layout' => __d('lil_invoices', 'Templates'),
    'menu' => [
        'add' => [
            'title' => __d('lil_invoices', 'Add'),
            'visible' => true,
            'url' => [
                'plugin' => 'LilInvoices',
                'controller' => 'InvoicesTemplates',
                'action' => 'add',
            ],
        ],
    ],
    'table' => [
        'parameters' => [
            'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0, 'id' => 'AdminTemplatesIndex'
        ],
        'head' => ['rows' => [['columns' => [
            'title' => __d('lil_invoices', 'Title'),
            'actions' => [],
        ]]]],
    ],
];

foreach ($templates as $template) {
    $templatesIndex['table']['body']['rows'][]['columns'] = [
        'title' => $this->Html->link($template->title, ['action' => 'edit', $template->id]),
        'actions' => [
            'parameters' => ['class' => 'right-align'],
            'html' => $this->Lil->editLink($template->id) . ' ' . $this->Lil->deleteLink($template->id),
        ],
    ];
}

echo $this->Lil->index($templatesIndex, 'LilInvoices.InvoicesTemplates.index');
