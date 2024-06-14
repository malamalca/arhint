<?php

use Cake\Core\Configure;
use Cake\Routing\Router;

/**
 * This is admin_edit template file.
 */

$editForm = [
    'title_for_layout' =>
        $project->id ? __d('projects', 'Edit Project') : __d('projects', 'Add Project'),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => ['model' => $project, ['idPrefix' => 'project', 'type' => 'file']],
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['id'],
            ],
            'owner_id' => [
                'method' => 'hidden',
                'parameters' => ['owner_id'],
            ],
            'redirect' => [
                'method' => 'hidden',
                'parameters' => ['redirect', ['default' => base64_encode($this->getRequest()->referer() ?? '')]],
            ],

            'no' => [
                'method' => 'control',
                'parameters' => [
                    'no',
                    [
                        'type' => 'text',
                        'label' => __d('projects', 'No.') . ':',
                        'autofocus',
                        'error' => [
                            'uniqueNumber' => __d('projects', 'No already exists'),
                        ],
                    ],
                ],
            ],
            'title' => [
                'method' => 'control',
                'parameters' => [
                    'title',
                    [
                        'type' => 'text',
                        'label' => __d('projects', 'Title') . ':',
                    ],
                ],
            ],
            'status_label' => [
                'method' => 'label',
                'parameters' => ['status_id', __d('projects', 'Status') . ':'],
            ],
            'status' => empty($projectStatuses) ? null : [
                'method' => 'control',
                'parameters' => [
                    'status_id',
                    [
                        'type' => 'select',
                        'label' => false,
                        'class' => 'browser-default',
                        'empty' => '-- ' . __d('projects', 'status') . ' --',
                        'options' => $projectStatuses,
                    ],
                ],
            ],
            'active' => [
                'method' => 'control',
                'parameters' => [
                    'active',
                    [
                        'type' => 'checkbox',
                        'label' => __d('projects', 'Active'),
                        'default' => true,
                    ],
                ],
            ],

            'lat' => [
                'method' => 'control',
                'parameters' => [
                    'lat',
                    [
                        'type' => 'number',
                        'step' => '0.0001',
                        'label' => __d('projects', 'Latitude') . ':',
                    ],
                ],
            ],
            'lon' => [
                'method' => 'control',
                'parameters' => [
                    'lon',
                    [
                        'type' => 'number',
                        'step' => '0.0001',
                        'label' => __d('projects', 'Longitude') . ':',
                    ],
                ],
            ],
            'picker' => '<button data-target="modal1" class="btn modal-trigger">' .
                __d('projects', 'Pick on Map') . '</button>',

            'ico' => [
                'method' => 'control',
                'parameters' => [
                    'ico_file',
                    [
                        'type' => 'file',
                        //'label' => __d('projects', 'Icon') . ':',
                        'label' => false,
                    ],
                ],
            ],
            'colorize' => [
                'method' => 'control',
                'parameters' => [
                    'colorize',
                    [
                        'type' => 'color',
                        'label' => [
                            'text' => __d('projects', 'Colorize') . ':',
                            'class' => 'active',
                        ],
                    ],
                ],
            ],

            'submit' => [
                'method' => 'button',
                'parameters' => [
                    __d('projects', 'Save'),
                    [
                        'type' => 'submit',
                    ],
                ],
            ],
            'form_end' => [
                'method' => 'end',
            ],
        ],
    ],
];
$this->Lil->jsReady('$("#project-no").focus();');
echo $this->Lil->form($editForm, 'Projects.Projects.edit');
?>
<div id="modal1" class="modal">
<div class="modal-content">
<div id="map" style="min-height: 300px;"></div>
</div>
</div>
<script>
    var map;
    var marker = null;

    function initMap() {
        map = new google.maps.Map(document.getElementById('map'), {
            center: {lat: 46.056946, lng: 14.505751},
            zoom: 9
        });

        let latValue = $("#project-lat").val();
        let lonValue = $("#project-lon").val();

        if (latValue && lonValue) {
            let myLatlng = new google.maps.LatLng(latValue, lonValue);
        
            marker = new google.maps.Marker({
                position: myLatlng,
                title: "<?= __d('projects', 'Project Position') ?>",
                map: map
            });
        }

        google.maps.event.addListener(map, "click", function(event) {
            if (marker) {
                marker.setPosition(event.latLng);
            } else {
                marker = new google.maps.Marker({
                    position: event.latLng,
                    title: "Project Position",
                    map: map
                });
            }
            $("#project-lat").val(parseFloat(event.latLng.lat()).toFixed(4));
            $("#project-lon").val(parseFloat(event.latLng.lng()).toFixed(4));
        });
    }
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=<?= Configure::read('Projects.mapsApiKey') ?>&callback=initMap" async defer></script>
