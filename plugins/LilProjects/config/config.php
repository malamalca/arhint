<?php

use Cake\I18n\I18n;

$materialGroups = [];
$materialGroupsFile = dirname(__FILE__) . DS . I18n::getLocale() . DS . 'material_groups.php';
if (file_exists($materialGroupsFile)) {
    include $materialGroupsFile;
}

return [
    'LilProjects' => [
        //'mapsApiKey' => ''
        'materialGroups' => $materialGroups,
    ],
];
