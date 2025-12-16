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
            'referer' => [
                'method' => 'hidden',
                'parameters' => ['referer', ['default' => Router::url($this->getRequest()->referer(), true)]],
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
            'descript' => [
                'method' => 'control',
                'parameters' => [
                    'descript',
                    [
                        'type' => 'textarea',
                        'label' => __d('projects', 'Description') . ':',
                        'style' => 'min-height: 150px; font-family: ui-monospace;',
                    ],
                ],
            ],
            'status' => empty($projectStatuses) ? null : [
                'method' => 'control',
                'parameters' => [
                    'status_id',
                    [
                        'type' => 'select',
                        'label' => __d('projects', 'Status') . ':',
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
            'picker' => '<button id="MapPicker" class="btn filled">' . __d('projects', 'Pick on Map') . '</button>',

            'ico' => [
                'method' => 'control',
                'parameters' => [
                    'ico_file',
                    [
                        'type' => 'file',
                        'label' => __d('projects', 'Icon') . ':',
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

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
     integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
     crossorigin=""/>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
     integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
     crossorigin=""></script>

<dialog id="modalMapPopup">
    <div class="dialog-header">
       <a href="#" class="btn-small filled modal-close" id="closeModalMapPopup"><i class="material-icons">close</i></a>
       <h3 id="modal-title"><?= __d('projects', 'Pick on Map') ?></h3>
    </div>
    <div id="map" style="min-height: 300px;"></div>
</dialog>
<script>
    var latValue = $("#project-lat").val();
    var lonValue = $("#project-lon").val();

    var map = L.map('map').setView([latValue || 46.056946, lonValue || 14.505751], latValue ? 15 : 9);
    var marker = null
    var modalInstance;

    $(document).ready(function() {
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        if (latValue && lonValue) {
            marker = L.marker([latValue, lonValue]).addTo(map);
        };

        map.on("click", addMarker);
        
        var modalEl = document.getElementById("modalMapPopup");

        $("#MapPicker").on("click", function(e) {
            modalEl.showModal();
            map.invalidateSize();
            e.preventDefault();
            return false;
        });

        $("#closeModalMapPopup").on("click", function(e) {
            modalEl.close();
            e.preventDefault();
            return false;
        });
    });

    function addMarker(e) {
        if (marker) {
            var newLatLng = new L.LatLng(e.latlng.lat, e.latlng.lng);
            marker.setLatLng(newLatLng); 
        } else {
            marker = new L.marker(e.latlng).addTo(map);
        }

        $("#project-lat").val(parseFloat(e.latlng.lat).toFixed(4));
        $("#project-lon").val(parseFloat(e.latlng.lng).toFixed(4));
    }

</script>
<script2 src="https://maps.googleapis.com/maps/api/js?key=<?= Configure::read('Projects.mapsApiKey') ?>&callback=initMap" async defer></script2>
