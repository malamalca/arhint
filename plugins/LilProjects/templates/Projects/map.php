<?php
    use Cake\Core\Configure;
    use Cake\Routing\Router;

    $projectsMap = [
        'title_for_layout' => __d('lil_projects', 'Projects Map'),
        'menu' => [
            'add' => [
                'title' => __d('lil_projects', 'Add'),
                'visible' => true,
                'url' => [
                    'action' => 'edit',
                ],
            ],
        ],
        'panels' => [
            'map' => '<div id="map" style="height: 90%;"></div>',
        ],
    ];

    echo $this->Lil->panels($projectsMap, 'LilProjects.Projects.map');
    ?>

    <script>
        var map;
        var infowindow;

        function initMap() {
            map = new google.maps.Map(document.getElementById('map'), {
                center: {lat: 46.056946, lng: 14.505751},
                zoom: 9
            });

            infowindow = new google.maps.InfoWindow();

            google.maps.event.addListener(map, "click", function(event) {
                infowindow.close();
            });

            var icons = {
                house: {
                    icon: '<?= Router::url('/LilProjects/img/house.png') ?>'
                },
            };

            var projects = [];
            <?php
            foreach ($projects as $i => $project) {
                printf(
                    'projects[%1$s] = {type: "house", id:"%6$s", position: new google.maps.LatLng(%2$s, %3$s), info: "%4$s", icon: "%5$s", title: "%7$s"};' . PHP_EOL,
                    $i,
                    (float)$project->lat,
                    (float)$project->lon,
                    $this->element('map_popup', ['project' => $project]),
                    empty($project->ico) ? Router::url('/LilProjects/img/house.png') : Router::url(['plugin' => 'LilProjects', 'controller' => 'Projects', 'action' => 'picture', $project->id, 'thumb']),
                    $project->id,
                    htmlspecialchars($project->title)
                );
            }
            ?>

            projects.forEach(function(project) {
                var marker = new google.maps.Marker({
                    position: project.position,
                    icon: project.icon,
                    title: project.title,
                    map: map
                });
                marker.addListener('click', function() {
                    document.location.replace("<?= Router::url(['plugin' => 'LilProjects', 'controller' => 'Projects', 'action' => 'view']); ?>/" + project.id);
                    /*$.ajax({
                        url: "<?= Router::url(['plugin' => 'LilProjects', 'controller' => 'Projects', 'action' => 'view']); ?>/" + project.id,
                        success: function (contentString) {
                            infowindow.open(map, marker);
                            infowindow.setContent(contentString);
                        }
                    });*/
                });
            });

        }
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= Configure::read('LilProjects.mapsApiKey') ?>&callback=initMap" async defer></script>


    <script type="text/javascript">
        $(document).on ("click", "#submit-logs-btn", function () {
            if ($("textarea#descript").val().length > 0) {
                $.ajax({
                    type: "POST",
                    url: $(this).parent("form").attr("action"),
                    data: $(this).parent("form").serialize(),
                    success: function (contentString) {
                        infowindow.setContent(contentString);
                    },
                });
            }
            return false;
        });
    </script>
