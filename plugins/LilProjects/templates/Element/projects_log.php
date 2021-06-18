<?php
    printf(
        '<span class="small">%2$s, %3$s</span><div>%1$s</div>',
        h($projectsLog->descript),
        $projectsLog->created,
        $user->name
    );
