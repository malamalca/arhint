<?php
if ($data->count() > 0) {
    $this->Paginator->options([
        'url' => $sourceRequest
    ]);

    $workhoursTable = [
        'parameters' => [
            'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0, 'id' => 'ProjectsWorkhoursList', 'width' => '700',
        ],
        'head' => ['rows' => [['columns' => [
            'user' => [
                'parameters' => ['class' => 'left-align'],
                'html' => __d('projects', 'User'),
            ],
            'started' => [
                'parameters' => ['class' => 'center-align'],
                'html' => $this->Paginator->sort(
                    'started',
                    __d('projects', 'Started'),
                ),
            ],
            'duration' => [
                'parameters' => ['class' => 'center-align'],
                'html' => $this->Paginator->sort(
                    'duration',
                    __d('projects', 'Duration'),
                ),
            ],
            'actions' => [
                'parameters' => ['class' => 'center-align min-width'],
                'html' => '',
            ],
        ]]]],
        'foot' => ['rows' => [['columns' => [
            'paginator' => [
                'parameters' => ['class' => 'left-align', 'colspan' => 2],
                'html' => '<ul class="paginator">' .
                    $this->Paginator->numbers([
                        'first' => 1,
                        'last' => 1,
                        'modulus' => 3,
                    ]) .
                    '</ul>',
            ],
            'total' => [
                'parameters' => ['class' => 'center-align'],
                'html' => '',
            ],
            'actions' => [
                'parameters' => ['class' => 'center-align min-width'],
                'html' => '',
            ],
         ]]]],
    ];

    $total = 0;

    foreach ($data as $workhour) {
        $canEdit = $this->getCurrentUser()->hasRole('admin') || 
            (empty($workhour->dat_confirmed) && ($this->getCurrentUser()->id == $workhour->user_id));

        $workhoursTable['body']['rows'][]['columns'] = [
            'user' => [
                'parameters' => ['class' => 'left-align'],
                'html' => $users[$workhour->user_id]->name,
            ],
            'started' => [
                'parameters' => ['class' => 'center-align'],
                'html' => (string)$workhour->started,
            ],
            'duration' => [
                'parameters' => ['class' => 'center-align'],
                'html' => $this->Arhint->duration($workhour->duration),
            ],
            'actions' => [
                'parameters' => ['class' => 'center-align'],
                'html' => !$canEdit ? ' <i class="material-icons">lock_outline</i>' : $this->Lil->deleteLink($workhour->id),
            ],
        ];

        $total += $workhour->duration;
    }

    $workhoursTable['foot']['rows'][0]['columns']['total']['html'] = $this->Arhint->duration($total);

    echo $this->Lil->table($workhoursTable, 'Projects.ProjectsWorkhours.Aht.index');
} else {
    echo '<div class="hint">' . __d('projects', 'No workhours for this project found.') . '</div>';
}
