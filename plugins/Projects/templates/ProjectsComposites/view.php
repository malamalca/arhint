<?php
    use Cake\Routing\Router;

    $compositeView = [
        'title_for_layout' => sprintf(
            '<div class="small">%1$s</div><span>%2$s</span> :: %3$s',
            $this->Html->link((string)$project, [
                'controller' => 'Projects',
                'action' => 'view',
                $project->id,
                '?' => ['tab' => 'composites']
            ]),
            $composite->no,
            $composite->title
        ),
        'menu' => [
            'edit' => [
                'title' => __d('projects', 'Edit'),
                'visible' => true,
                'url' => [
                    'action' => 'edit',
                    $composite->id,
                ],
            ],
            'delete' => [
                'title' => __d('projects', 'Delete'),
                'visible' => true,
                'url' => [
                    'action' => 'delete',
                    $composite->id,
                ],
                'params' => [
                    'confirm' => __d('projects', 'Are you sure you want to delete this composite?'),
                ],
            ],
            'composite' => [
                'title' => __d('projects', 'Add Material'),
                'visible' => true,
                'url' => [
                    'controller' => 'ProjectsCompMaterials',
                    'action' => 'edit',
                    '?' => ['composite' => $composite->id],
                ],
                'params' => ['id' => 'add-composite-material'],
            ],
        ],
        'entity' => $composite,
        'panels' => [
            'materials' => [
                'lines' => [
                    '<ul id="composite-materials">'
                ]
            ],
        ],
    ];

    $addMaterialRowTemplate = '<div class="add-material-bar row"><div class="col s1"></div>' .
        '<button class="add-material">Add Material</button>' .
        '<button class="add-group">Add Group</button>' .
        '<button class="add-lookup">Add From Lookup</button>' .
        '</div>';

    $itemTemplate = '<li id="mat__id__" class="composite-material-row__class__">' .
        '<div class="row">' .
        '<div class="actions col s1">' .
            '<a class="btn btn-small btn-flat reorder-handle"><i class="material-icons tiny">dehaze</i></a>' .
            '<a class="waves-effect waves-teal btn btn-small btn-flat delete-material"><i class="material-icons tiny">delete</i></a>' .
        '</div>' .
        '<div class="descript col s8">__descript__</div>' .
        '<div class="thickness right-align col s2">__thickness__</div>' .
        '<div class="unit col s1">__unit__</div>' .
        '</div>' .
        $addMaterialRowTemplate .
        '</li>';

    // add material to first position
    $compositeView['panels']['materials']['lines'][] =
        '<li class="composite-material-row first">' . $addMaterialRowTemplate . '</li>';


    $totalThickness = 0;
    foreach ($composite->composites_materials as $i => $material) {
        $totalThickness += $material->thickness;
        $compositeView['panels']['materials']['lines'][] =
            str_replace(
                ['__id__', '__descript__', '__thickness__', '__class__', '__unit__'],
                [
                    $material->id,
                    h($material->descript),
                    $material->is_group ? '' : $this->Number->precision((float)$material->thickness, 1),
                    $material->is_group ? ' material-group' : '',
                    $material->is_group ? '' : 'cm'
                ],
                $itemTemplate
            );
    }

    $compositeView['panels']['materials']['lines'][] =
        '<li id="composite-material-foot">' .
        '<div class="row">' .
        '<div class="actions col s1">' .
        '</div>' .
        '<div class="col s8 right-align">' . __d('projects', 'Total thickness'). '</div>' .
        '<div class="right-align col s2" id="total-thickness">' . $this->Number->precision((float)$totalThickness, 1) . '</div>' .
        '<div class="col s1">cm</div>' .
        '</div>' .
        '</li>';

    $compositeView['panels']['materials']['lines'][] = '</ul>';

    echo $this->Lil->panels($compositeView, 'Projects.Composites.view');

    echo $this->Html->script('Projects.jquery-ui.min.js');
    echo $this->Html->script('Projects.materials_list.js');
    echo $this->Html->script('Projects.material_editor.js');
?>
<script type="text/javascript">
    $(document).ready(function () {
        $("ul#composite-materials").MaterialsList({
            "newItemTemplate": <?= json_encode($itemTemplate) ?>,
            "addFromLibraryUrl": "<?= Router::url(['controller' => 'ProjectsMaterials', 'action' => 'lookup']) ?>",
            "editUrl": "<?= Router::url(['controller' => 'ProjectsCompMaterials', 'action' => 'edit', '__id__', 'material', '_ext' => 'aht']) ?>",
            "addUrl": "<?= Router::url(['controller' => 'ProjectsCompMaterials', 'action' => 'edit', 'material', '_ext' => 'aht', '?' => ['composite' => $composite->id, 'order' => '__order__']]) ?>",
            "reorderUrl": "<?= Router::url(['controller' => 'ProjectsCompMaterials', 'action' => 'reorder', '__id__', '__position__']) ?>",
            "deleteUrl": "<?= Router::url(['controller' => 'ProjectsCompMaterials', 'action' => 'delete', '__id__']) ?>",
        });
    });
</script>
