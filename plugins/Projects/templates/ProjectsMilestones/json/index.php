<?php

$milestonesArray = [];
foreach ($milestones as $milestone) {
    $milestonesArray[] = [
        'id' => $milestone->id,
        'title' => (string)$milestone->title,
    ];
}

echo json_encode(['milestones' => $milestonesArray], JSON_PRETTY_PRINT);