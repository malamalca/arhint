<?php
declare(strict_types=1);

use Cake\I18n\Date;
use Cake\Routing\Router;
use Projects\Lib\ProjectsFuncs;
?>

<div class="task-row">
    <div class="checkbox"><input type="checkbox" /></div>
    <div class="status"><?= empty($workhour->dat_confirmed) ? '&nbsp;' : '<i class="material-icons small red-text text-lighten-2">beenhere</i>' ?></div>
    <div class="title"><?= h($workhour->descript) ?></div>
    <div class="details">
    <?php
        $descriptData = [];
        if (!empty($workhour->project)) {
            $descriptData[] = $this->Html->image('/projects/img/home-16.svg') . ' ' . h((string)$workhour->project);
        }
        if (!empty($workhour->user)) {
            $descriptData[] = $this->Html->image('/projects/img/person-16.svg') . ' ' . h((string)$workhour->user->name);
        }
        $descriptData[] = $this->Html->image('/projects/img/calendar-16.svg') . ' ' . h($workhour->started->nice());
        $descriptData[] = $this->Html->image('/projects/img/clock-16.svg') . ' ' . $this->Arhint->durationNice($workhour->duration);

        echo implode(' · ', $descriptData);
    ?>
    </div>
</div>