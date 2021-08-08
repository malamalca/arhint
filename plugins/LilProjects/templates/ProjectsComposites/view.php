<?php
    use Cake\Routing\Router;

    $compositeView = [
        'title_for_layout' => '<span class="small">' . $composite->no . ' </span><br />' . $composite->title,
        'menu' => [
            'edit' => [
                'title' => __d('lil_projects', 'Edit'),
                'visible' => true,
                'url' => [
                    'action' => 'edit',
                    $composite->id,
                ],
            ],
            'delete' => [
                'title' => __d('lil_projects', 'Delete'),
                'visible' => true,
                'url' => [
                    'action' => 'delete',
                    $composite->id,
                ],
                'params' => [
                    'confirm' => __d('lil_projects', 'Are you sure you want to delete this composite?'),
                ],
            ],
            'composite' => [
                'title' => __d('lil_projects', 'Add Material'),
                'visible' => true,
                'url' => [
                    'controller' => 'ProjectsCompMaterials',
                    'action' => 'add',
                    '?' => ['composite' => $composite->id],
                ],
                'params' => ['id' => 'add-composite-material'],
            ],
        ],
        'entity' => $composite,
        'panels' => [
            'materials' => [
                'table' => [
                    'params' => ['id' => 'composite-materials'],
                    'body' => ['rows' => []],
                    'foot' => ['rows' => [
                        ['columns' => [
                            'title' => [
                                'params' => ['colspan' => 2, 'class' => 'right-align'],
                                'html' => __d('lil_projects', 'Total Thickness'),
                            ],
                            'sum' => [
                                'params' => ['class' => 'right-align', 'id' => 'total-thickness'],
                                'html' => ''
                            ]
                        ]],
                    ]],
                ],
            ],
        ],
    ];

    $totalThickness = 0;
    foreach ($composite->composites_materials as $i => $material) {
        $totalThickness += $material->thickness;
        $compositeView['panels']['materials']['table']['body']['rows'][] = [
            'params' => ['id' => 'mat' . $material->id, 'class' => 'composite-material-row'],
            'columns' => [
                'no' => [
                    'params' => ['class' => 'actions'],
                    'html' => 
                        '<a class="btn btn-small btn-flat reorder-handle"><i class="material-icons tiny">dehaze</i></a>' .
                        '<a class="waves-effect waves-teal btn btn-small btn-flat delete-material"><i class="material-icons tiny">delete</i></a>',
                ],
                'descript' => [
                    'params' => ['class' => 'descript'],
                    'html' => $this->Html->link($material->descript, ['controller' => 'ProjectsCompMaterials', 'action' => 'edit', $material->id]),
                ],
                'thickness' => [
                    'params' => ['class' => 'thickness right-align'],
                    'html' => $this->Number->precision((float)$material->thickness, 1),
                ]
            ]
        ];
    }

    $compositeView['panels']['materials']['table']['foot']['rows'][0]['columns']['sum']['html'] = $this->Number->precision((float)$totalThickness, 1);

    echo $this->Lil->panels($compositeView, 'LilProjects.Composites.view');

    echo $this->Html->script('LilProjects.jquery-ui.min.js');
    echo $this->Html->script('LilProjects.materials_list.js');
    echo $this->Html->script('LilProjects.material_editor.js');
?>
<script type="text/javascript">
    $(document).ready(function () {
        $("table#composite-materials").MaterialsList({
            "editUrl": "<?= Router::url(['controller' => 'ProjectsCompMaterials', 'action' => 'edit', '__id__', 'material', '_ext' => 'aht']) ?>",
            "reorderUrl": "<?= Router::url(['controller' => 'ProjectsCompMaterials', 'action' => 'reorder', '__id__', '__position__']) ?>",
            "deleteUrl": "<?= Router::url(['controller' => 'ProjectsCompMaterials', 'action' => 'delete', '__id__']) ?>",
        });
    });
</script>
