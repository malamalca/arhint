<?php
    printf(
        '<span class="small">%2$s, %3$s</span><div class="truncate">%1$s</div>',
        $projectsLog->descript,
        $projectsLog->created,
        h((string)$user)
    );
