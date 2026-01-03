<?php

$projectsArray = [];
foreach ($projects as $project) {
    $projectsArray[] = [
        'id' => $project->id,
        'title' => (string)$project->title,
    ];
}

echo json_encode(['projects' => $projectsArray], JSON_PRETTY_PRINT);