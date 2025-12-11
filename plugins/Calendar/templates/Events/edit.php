<?php
use Cake\I18n\DateTime;
use Cake\Routing\Router;

$suggestedDate = $this->getRequest()->getQuery('date', (new DateTime())->toDateString());
$suggestedTime = (new DateTime())->toTimeString();

$defultDateStart = DateTime::parseDateTime($suggestedDate . ' ' . $suggestedTime, 'yyyy-MM-dd H:mm');
if (!$defultDateStart) {
    $defultDateStart = (new DateTime())->minute(0)->second(0)->microsecond(0);
}
$defultDateEnd = $defultDateStart->addHours(1);

$diffSeconds = $defultDateEnd->diffInSeconds($defultDateStart);
if (!$event->isNew() && $event->date_end) {
    $diffSeconds = $event->date_end->diffInSeconds($event->date_start);
}

$eventEdit = [
    'title_for_layout' =>
        $event->id ? __d('calendar', 'Edit Event') : __d('calendar', 'Add Event'),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method'     => 'create',
                'parameters' => ['model' => $event, ['idPrefix' => 'event']]
            ],
            'id' => [
                'method'     => 'hidden',
                'parameters' => ['field' => 'id']
            ],
            'calendar_id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'calendar_id']
            ],
            'referer' => [
                'method' => 'hidden',
                'parameters' => ['referer', ['default' => ($referer = $this->request->getQuery('redirect')) ? $referer : null]],
            ],

            'title' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'title', [
                        'type' => 'text',
                        'label' => __d('calendar', 'Title') . ':',
                    ],
                ],
            ],
            'location' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'location', [
                        'type' => 'text',
                        'label' => __d('calendar', 'Location') . ':',
                    ],
                ],
            ],
            'allday' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'all_day', [
                        'type' => 'checkbox',
                        'label' => __d('calendar', 'All-day'),
                    ],
                ],
            ],
            'dat_start' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'dat_start', [
                        'type' => $event->all_day ? 'date' : 'datetime',
                        'default' => $defultDateStart,
                        'step' => $event->all_day ? 1 : 60,
                        'label' => [
                            'text' => __d('calendar', 'Starts') . ':',
                            'class' => 'active',
                        ],
                    ],
                ],
            ],
            'dat_end' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'dat_end', [
                        'type' => $event->all_day ? 'date' : 'datetime',
                        'default' => $defultDateEnd,
                        'step' => $event->all_day ? 1 : 60,
                        'label' => [
                            'text' => __d('calendar', 'Ends') . ':',
                            'class' => 'active',
                        ],
                    ],
                ],
            ],
            'reminder-label' => [
                'method' => 'label',
                'parameters' => [
                    __d('calendar', 'Alarm') . ':',
                ],
            ],
            'reminder' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'reminder', [
                        'type' => 'select',
                        'label' =>  false,
                        'class' => 'browser-default',
                        'empty' => __d('calendar', 'None'),
                        'options' => [
                            0 => __d('calendar', 'At time of event'),
                            5 => __d('calendar', '5 minutes before'),
                            10 => __d('calendar', '10 minutes before'),
                            15 => __d('calendar', '15 minutes before'),
                            30 => __d('calendar', '30 minutes before'),
                            60 => __d('calendar', '1 hour before'),
                            2*60 => __d('calendar', '2 hours before'),
                            24*60 => __d('calendar', '1 day before'),
                            48*60 => __d('calendar', '2 days before'),
                            7*24*60 => __d('calendar', '1 week before'),
                        ],
                    ],
                ],
            ],
            '<div class="row"><div class="col s6">',
            'submit' => [
                'method' => 'button',
                'parameters' => [
                    'label' => __d('calendar', 'Save')
                ]
            ],
            '</div><div class="col s6 right-align">',
            'delete' => $event->isNew() ? null : [
                'method' => 'button',
                'parameters' => [
                    __d('calendar', 'Delete'),
                    [
                        'type' => 'submit',
                        'name' => 'delete',
                        'value' => 'delete',
                        'onclick' => sprintf('return confirm("%s");', __d('calendar', 'Are you sure?')),
                    ],
                ],
            ],
            '</div>',
            'form_end' => [
                'method'     => 'end',
            ],
        ]
    ]
];
$this->Lil->jsReady('$("#event-title").focus();');
echo $this->Lil->form($eventEdit, 'Calendar.Events.edit');
?>
<script type="text/javascript">
    var hoursToggleTransfer = '00:00';
    var diffSeconds = <?= $diffSeconds ?>;

    function convertDateTimeToDate(field) {
        let tmpDateVal = new Date($(field).val());

        $(field).get(0).type = "date";
        $(field)
            .val('')
            .prop("step", 1)

        if (tmpDateVal instanceof Date && !isNaN(tmpDateVal)) {
            const tzOffset = tmpDateVal.getTimezoneOffset();
            tmpDateVal = new Date(tmpDateVal.getTime() - (tzOffset * 60 * 1000));
            let tmpDateUnixFormat = tmpDateVal.toISOString().split('T')[0];

            $(field)
                .val(tmpDateUnixFormat)
                .data("old-time", tmpDateVal.toISOString().split('T')[1].substr(0, 5));
        }
    }

    function convertDateToDateTime(field) {
        let tmpDateVal = new Date($(field).val());

        $(field).get(0).type = "datetime-local";
        $(field)
            .val('')
            .prop("step", 60)


        if (tmpDateVal instanceof Date && !isNaN(tmpDateVal)) {
            const tzOffset = tmpDateVal.getTimezoneOffset();
            tmpDateVal = new Date(tmpDateVal.getTime() - (tzOffset * 60 * 1000));
            let tmpDateUnixFormat = tmpDateVal.toISOString().substr(0, 11);

            let oldTime = $(field).data("old-time");
            if (!oldTime) {
                oldTime = (new Date()).toISOString().substr(11, 5);
            }

            $(field).val(tmpDateUnixFormat + oldTime);
        }
    }

    function toggleAllDay() {
        if ($("#event-all-day").prop("checked")) {
            convertDateTimeToDate("#event-dat-start");
            convertDateTimeToDate("#event-dat-end");
        } else {
            convertDateToDateTime("#event-dat-start");
            convertDateToDateTime("#event-dat-end");
        }
    }

    function changeStartDate() {
        if (!$("#event-all-day").prop("checked")) {
            let tmpDateVal = new Date($(this).val());
            const tzOffset = tmpDateVal.getTimezoneOffset();

            tmpDateVal.setSeconds(tmpDateVal.getSeconds() + diffSeconds);
            tmpDateVal = new Date(tmpDateVal.getTime() - (tzOffset * 60 * 1000));

            $("#event-dat-end").val(tmpDateVal.toISOString().substr(0, 16));
        }
    }

    function changeEndDate() {
        if (!$("#event-all-day").prop("checked")) {
            let tmpEndDateVal = new Date($(this).val());
            let tmpStartDateVal = new Date($("#event-dat-start").val());

            diffSeconds = (tmpEndDateVal - tmpStartDateVal) / 1000;
        }
    }

    $(document).ready(function() {
        $("#event-all-day").on("change", toggleAllDay);
        $("#event-dat-start").on("change", changeStartDate);
        $("#event-dat-end").on("change", changeEndDate);
    });
</script>
