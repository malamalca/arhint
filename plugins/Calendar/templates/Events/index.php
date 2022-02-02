<?php
use Cake\I18n\FrozenDate;
use Cake\Routing\Router;

if (empty($filter['month'])) {
    $filter['month'] = (new FrozenDate())->format('Y-m');
}

$firstDayOfMonth = (new FrozenDate())->parse($filter['month'] . '-01');

$title = $firstDayOfMonth->i18nFormat('MMMM YYYY');

// buttons
$buttons = sprintf(
    '<div class="col s4"><a href="%1$s" id="go-prev" class="btn waves-effect waves-light"><</a>' .
    '<a href="%2$s" id="go-next" class="btn waves-effect waves-light">></a> ' .
    '<a href="%4$s" id="go-today" class="btn waves-effect waves-light">%3$s</a>' .
    '</div>',
    Router::url(['?' => array_merge($filter, ['month' => $firstDayOfMonth->addMonth(-1)->format('Y-m')])]),
    Router::url(['?' => array_merge($filter, ['month' => $firstDayOfMonth->addMonth(+1)->format('Y-m')])]),
    __d('calendar', 'Today'),
    Router::url(['?' => array_merge($filter, ['month' => (new FrozenDate())->format('Y-m')])])
);


$eventsIndex = [
    'title' => '<div class="row">' . $buttons . ' <div class="col s6">' . $title . '</div></div>',
    'menu' => [
        'add' => [
            'title' => __d('calendar', 'Add Event'),
            'visible' => true,
            'url' => [
                'action' => 'edit',
            ],
            'params' => [
                'id' => 'menuitem-add',
            ],
        ],
    ],
    'panels' => [
        'calendar' => [
            'params' => [
            ],
            'lines' => [
            ],
        ],
    ],
];

// header
$currentDay = $firstDayOfMonth->startOfWeek();
$eventsIndex['panels']['calendar']['lines']['head'] = '<div class="calendar-week">';
for ($i = 0; $i < 7; $i++) {
    $dayClasses = ['calendar-box', 'head'];
    if ($currentDay->isSunday()) {
        $dayClasses[] = 'sunday';
    }
    if ($currentDay->isSaturday()) {
        $dayClasses[] = 'saturday';
    }
    $eventsIndex['panels']['calendar']['lines']['head'] .= sprintf(
        '<div class="%2$s">%1$s</div>',
        $currentDay->i18nFormat('EE'),
        implode(' ', $dayClasses)
    );
    $currentDay = $currentDay->addDays(1);
}
$eventsIndex['panels']['calendar']['lines']['head'] .= '</div>';

// sort events by days
$eventsByDay = [];
foreach ($events as $event) {
    $eventsByDay[$event->dat_start->format('Y-m-d')][] = $event;

    $endDate = $event->dat_end ?? $event->dat_start;

    // when event spans across multiple weeks
    $currentFirstDayOfWeek = $event->dat_start->startOfWeek()->addWeek(1);
    while($currentFirstDayOfWeek->lte($endDate)) {
        $eventsByDay[$currentFirstDayOfWeek->format('Y-m-d')][] = $event;

        $currentFirstDayOfWeek = $currentFirstDayOfWeek->addWeek(1);
    }
}

// body
$weeksInMonth = ceil(($firstDayOfMonth->addMonths(1)->diffInDays($firstDayOfMonth->startOfWeek())) / 7);

$currentDay = $firstDayOfMonth->startOfWeek();
for ($weekNo = 1; $weekNo <= $weeksInMonth; $weekNo++) {
    $eventsIndex['panels']['calendar']['lines'][$weekNo] = '<div class="calendar-week">';
    for ($dow = 0; $dow < 7; $dow++) {
        $dayClasses = ['calendar-box', 'calendar-day'];

        if ($currentDay->lt($firstDayOfMonth)) {
            $dayClasses[] = 'prev-month';
        }
        if ($currentDay->gt($firstDayOfMonth->endOfMonth())) {
            $dayClasses[] = 'next-month';
        }
        if ($currentDay->isSunday()) {
            $dayClasses[] = 'sunday';
        }
        if ($currentDay->isSaturday()) {
            $dayClasses[] = 'saturday';
        }
        if ($currentDay->isToday()) {
            $dayClasses[] = 'today';
        }

        // process calendar events
        $eventLines = [];
        $eventPopupLines = [];
        $maxDisplayedEvents = 4;
        if (isset($eventsByDay[$currentDay->format('Y-m-d')])) {
            $eventIndex = 1;
            $eventCount = count($eventsByDay[$currentDay->format('Y-m-d')]);
            foreach ($eventsByDay[$currentDay->format('Y-m-d')] as $event) {
                $endDate = $event->dat_end ?? $event->dat_start;
                $eventClasses = ['event', 'truncate'];

                $eventTitle = h($event->title);
                if ($event->all_day) {
                    $eventClasses = array_merge($eventClasses, ['datespan', 'datespan-start', 'datespan-end', 'event.datespan-1']);
                } else {
                    if ($endDate->diffInDays($event->dat_start) == 0) {
                        $eventTitle = '<span class="bullet">&bull;</span> ' . $eventTitle;
                    }
                }


                // event duration multiple days
                if ($endDate->diffInDays($event->dat_start) > 0) {
                    $eventClasses[] = 'datespan';
                    if ($endDate->lte($currentDay->endOfWeek())) {
                        $eventClasses[] = 'datespan-' . ($endDate->diffInDays($currentDay)+1);
                    } else {
                        $eventClasses[] = 'datespan-' . ($currentDay->endOfWeek()->diffInDays($currentDay)+1);
                    }


                    if ($event->dat_start->diffInDays($currentDay) == 0) {
                        $eventClasses[] = 'datespan-start';
                    }
                    if ($endDate->endOfWeek()->diffInDays($currentDay->endOfWeek()) == 0) {
                        // is end of event in this week?
                        $eventClasses[] = 'datespan-end';
                    }
                }

                $eventLink = sprintf(
                    '<a class="%1$s" id="eventid-%3$s" href="%4$s">%2$s</a>',
                    implode(' ', $eventClasses),
                    $eventTitle,
                    $event->id,
                    Router::url(['action' => 'edit', $event->id])
                );

                if (($eventIndex < $maxDisplayedEvents) || ($maxDisplayedEvents == $eventCount)) {
                    $eventLines[] = $eventLink;
                } else {
                    $eventPopupLines[] = '<li>' . $eventLink . '</li>';
                }

                $eventIndex++;
            }

            // when tere are more than 4 events
            $moreEventsCount = $eventCount - $maxDisplayedEvents;
            if ($moreEventsCount > 0) {
                $eventLines[] = sprintf(
                    '<a class="dropdown-trigger more-events" data-target="dropdown-%1$s">%2$s</a>' .
                    '<ul id="dropdown-%1$s" class="dropdown-content dropdown-more-events">%3$s</ul>',
                    $currentDay->toDateString(),
                    __d('calendar', '+{0} More Events', $moreEventsCount + 1),
                    implode('', $eventPopupLines),
                );
            }
        }

        $eventsIndex['panels']['calendar']['lines'][$weekNo] .= sprintf(
            '<div class="%2$s" id="day-%4$s"><div class="day-no">%1$s</div>%3$s<div class="day-bg"></div></div>',
            $currentDay->day,
            implode(' ', $dayClasses),
            implode('', $eventLines),
            $currentDay->toDateString()
        );

        $currentDay = $currentDay->addDays(1);
    }
    $eventsIndex['panels']['calendar']['lines'][$weekNo] .= '</div>';
}

///////////////////////////////////////////////////////////////////////////////////////////////
// call plugin handlers and output data
echo $this->Lil->panels($eventsIndex, 'Calendar.Events.index');
?>
<script type="text/javascript">
    var addEventUrl = "<?= Router::url(['action' => 'edit', '?' => ['date' => '__date__']]) ?>";
    var popupTriggerElement = null;

    $(document).ready(function() {
        // onAddEventClick :: popup items
        $("#menuitem-add").modalPopup({
            title: "<?= __d('calendar', 'Add Event') ?>",
        })

        $(".calendar-day a.event").each(function() {
            $(this).modalPopup({
                title: "<?= __d('calendar', 'Edit Event') ?>",
            });
        });

        $(".calendar-day").on("click", function(e) {
            popupTriggerElement = $(this);
        });

        $(".calendar-day").modalPopup({
            title: "<?=__d('calendar', 'Add Event');?>",
            popupOnClick: false,
            popupOnDblClick: true,
            onBeforeRequest: function(e) {
                let rx_date = new RegExp("__date__", "i");
                let dayDate = $(popupTriggerElement).prop("id").substr(4);

                return addEventUrl.replace(rx_date, dayDate);
            },
        });
    });
</script>
