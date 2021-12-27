<?php
    sprintf(
        '<h3>%1$s</h3>',
        $this->Html->link($project->title, ['plugin' => 'Projects', 'controller' => 'Projects', 'action' => 'view', $project->id])
    );
