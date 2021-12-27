<?php

foreach ($projects as $project) {
    echo h($project->id) . ';' . h($project->title) . PHP_EOL;
}
