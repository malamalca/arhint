<?php

$foldersArray = [];
foreach ($tasksFolders as $folder) {
    $foldersArray[] = [
        'id' => $folder->id,
        'title' => (string)$folder->title,
    ];
}

echo json_encode(['folders' => $foldersArray], JSON_PRETTY_PRINT);