<?php
use Cake\Routing\Router;

$tableIndex = [
    'title_for_layout' => __d('lil_projects', 'Materials'),
    'table' => [
        'parameters' => [
            'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0, 'id' => 'MaterialsIndexTable',
        ],
        'head' => ['rows' => [
            [
                'columns' => [
                    'search' => [
                        'params' => ['colspan' => 1, 'class' => 'input-field'],
                        'html' => sprintf('<input placeholder="%s" id="SearchBox" />', __d('lil_projects', 'Search')),
                    ],
                    'pagination' => [
                        'params' => ['colspan' => 3, 'class' => 'right-align hide-on-small-only'],
                        'html' => '<ul class="paginator">' . $this->Paginator->numbers([
                            'first' => '<<',
                            'last' => '>>',
                            'modulus' => 3]) . '</ul>'],
                ],
            ],
            [
                'columns' => [
                    'descript' => __d('lil_projects', 'Title'),
                    'group' => __d('lil_projects', 'Group'),
                    'thickness' => __d('lil_projects', 'Thickness [cm]'),
                ],
            ],
        ]],
    ],
];

foreach ($projectsMaterials as $material) {
    $tableIndex['table']['body']['rows'][]['columns'] = [
        'descript' => h($material->descript),
        'group' => h($groups[$material->group_id] ?? ''),
        'thickness' => $this->Number->precision((float)$material->thickness, 2),
    ];
}

echo $this->Lil->index($tableIndex, 'LilProjects.ProjectsMaterials.index');
?>
<script type="text/javascript">
    var ajaxSearchUrl = "<?php echo Router::url([
        'plugin' => 'LilProjects',
        'controller' => 'ProjectsMaterials',
        'action' => 'lookup',
        '?' => ['search' => '__term__'],
    ]); ?>";

    var searchTimer = null;

    function searchMaterials()
    {
        let rx_term = new RegExp("__term__", "i");
        let searchTerm = $("#SearchBox").val();

        $.get(ajaxSearchUrl.replace(rx_term, encodeURIComponent(searchTerm)), function(response) {
            let tBody = response.substring(response.indexOf("<tbody>")+7, response.indexOf("</tbody>"));
            $("#MaterialsIndexTable tbody").html(tBody);

            let paginator = response.substring(
                response.indexOf("<ul class=\"paginator\">")+22,
                response.indexOf("</ul>", response.indexOf("<ul class=\"paginator\">"))
            );
            $("#MaterialsIndexTable ul.paginator").html(paginator);
        });
    }

    $(document).ready(function() {
        $("#SearchBox").on("input", function(e) {
            if ($(this).val().length > 1) {
                if (searchTimer) {
                    window.clearTimeout(searchTimer);
                    searchTimer = null;
                }
                searchTimer = window.setTimeout(searchMaterials, 500);
            }
        });

        $("#MaterialsIndexTable>tbody>tr").on("click", function(e) {
            var instance = M.Modal.getInstance($());
            console.log(instance);

        });
    });
</script>
