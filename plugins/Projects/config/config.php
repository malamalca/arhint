<?php

use Cake\I18n\I18n;

$materialGroups = [];
$materialGroupsFile = dirname(__FILE__) . DS . I18n::getLocale() . DS . 'material_groups.php';
if (file_exists($materialGroupsFile)) {
    include $materialGroupsFile;
}

return [
    'Projects' => [
        //'mapsApiKey' => ''
        'materialGroups' => $materialGroups,
        // Knowledge base of projects, see plugins/Projects/docs/knowledgeBase.md. Off until enabled in app_local.php.
        'KnowledgeBase' => [
            'enabled' => false,
            // System-wide AI of the periodic update: provider "local" or "openai"; empty url/model = defaults.
            'ai' => ['provider' => 'local', 'url' => '', 'model' => '', 'api_key' => '', 'timeout' => 180],
            'hourlyProjects' => 5,
            'maxEvents' => 60,
            'maxSeconds' => 2400,
            'chunkChars' => 12000,
            'changelogEntries' => 50,
        ],
    ],
];
