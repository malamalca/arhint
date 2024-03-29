<?php
    use Cake\Core\Configure;
    use Cake\Routing\Router;

    $projectsMap = [
        'title_for_layout' => __d('projects', 'Projects Map'),
        'menu' => [
            'add' => [
                'title' => __d('projects', 'Add'),
                'visible' => true,
                'url' => [
                    'action' => 'edit',
                ],
            ],
        ],
        'panels' => [
            'map' => '<div id="map" style="min-height: 300px;"></div>',
        ],
    ];

    echo $this->Lil->panels($projectsMap, 'Projects.Projects.map');
    ?>

    <script>
        var map;
        var infowindow;

        function placeMarker(position, map) {
        var marker = new google.maps.Marker({
          position: position,
          map: map
        });
        map.panTo(position);
      }

        function initMap() {
            map = new google.maps.Map(document.getElementById('map'), {
                center: {lat: 46.056946, lng: 14.505751},
                zoom: 9
            });

            infowindow = new google.maps.InfoWindow();

            google.maps.event.addListener(map, "click", function(event) {
                infowindow.close();
                //console.log(event.latLng.lat());
                //placeMarker(event.latLng, map);
            });

            var icons = {
                house: {
                    icon: '<?= Router::url('/Projects/img/house.png') ?>'
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
                    empty($project->ico) ? Router::url('/Projects/img/house.png') : Router::url(['plugin' => 'Projects', 'controller' => 'Projects', 'action' => 'picture', $project->id, 'thumb']),
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
                    document.location.replace("<?= Router::url(['plugin' => 'Projects', 'controller' => 'Projects', 'action' => 'view']); ?>/" + project.id);
                    /*$.ajax({
                        url: "<?= Router::url(['plugin' => 'Projects', 'controller' => 'Projects', 'action' => 'view']); ?>/" + project.id,
                        success: function (contentString) {
                            infowindow.open(map, marker);
                            infowindow.setContent(contentString);
                        }
                    });*/
                });
            });

        }
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= Configure::read('Projects.mapsApiKey') ?>&callback=initMap" async defer></script>

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

        $(document).ready(function() {
            function resizeMap()
            {
                let iFrameHeight = $(window).height() - $(".navbar-fixed").height() - 140;
                //let iFrameWidth = $("#map").parent(".container").width();

                $("#map")
                    .height(iFrameHeight);
                    //.width(iFrameWidth);

            }
            
            resizeMap();

            $(window).on("resize", function() {
                resizeMap();
            });
        });
    </script>