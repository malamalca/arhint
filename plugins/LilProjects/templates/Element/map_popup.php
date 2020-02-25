<?php
    sprintf('<h3>%1$s</h3>',
        $this->Html->link($project->title, ['plugin' => 'LilProjects', 'controller' => 'Projects', 'action' => 'view', $project->id]));
