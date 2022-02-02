<?php
use Cake\I18n\FrozenTime;
use Cake\Routing\Router;

$defultDateStart = (new FrozenTime())->minute(0)->second(0)->microsecond(0);
$defultDateEnd = (new FrozenTime())->minute(0)->second(0)->microsecond(0)->addHour();

$suggestedDate = $this->getRequest()->getQuery('date', (new FrozenTime())->toDateString());
$suggestedTime = (new FrozenTime())->toTimeString();

$defultDateStart = FrozenTime::parseDateTime($suggestedDate . ' ' . $suggestedTime, 'yyyy-MM-dd H:mm');
if (!$defultDateStart) {
    $defultDateStart = (new FrozenTime())->minute(0)->second(0)->microsecond(0);
}
$defultDateEnd = $defultDateStart->addHour();

$eventEdit = [
    'title_for_layout' =>
        $event->id ? __d('calendar', 'Edit Event') : __d('calendar', 'Add Event'),
    'menu' => [
        'delete' => $event->isNew() ? null : [
            'title' => __d('calendar', 'Delete'),
            'visible' => true,
            'url' => [
                'action'     => 'delete',
                $event->id
            ],
            'params' => [
                'confirm' => __d('calendar', 'Are you sure you want to delete this folder?')
            ]
        ],
    ],
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
                'parameters' => ['referer', ['default' => Router::url($this->getRequest()->referer(), true)]],
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
            'reminder' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'reminder', [
                        'type' => 'select',
                        'label' => [
                            'text' => __d('calendar', 'Alert') . ':',
                            'class' => 'active'
                        ],
                        'class' => 'browser-default',
                        'empty' => __d('calendar', 'None'),
                        'options' => [
                            0 => __d('calendar', 'At time of event'),
                            5*60 => __d('calendar', '5 minutes before'),
                            10*60 => __d('calendar', '10 minutes before'),
                            15*60 => __d('calendar', '15 minutes before'),
                            30*60 => __d('calendar', '30 minutes before'),
                            60*60 => __d('calendar', '1 hour before'),
                            2*60*60 => __d('calendar', '2 hours before'),
                            24*60*60 => __d('calendar', '1 day before'),
                            48*60*60 => __d('calendar', '2 days before'),
                            7*24*60*60 => __d('calendar', '1 week before'),
                        ]
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
    $(document).ready(function() {
        $("#event-all-day").on("change", toggleAllDay);
    });
</script>
