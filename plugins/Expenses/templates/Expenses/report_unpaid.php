<?php
$report = [
    'title_for_layout' => __d('expenses', 'REPORT: Unpaid Invoices'),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => [null, ['type' => 'POST']],
            ],
            'fs_user_start' => empty($counters) ? null : '<fieldset>',
            'lg_user' => empty($counters) ? null : sprintf('<legend>%s</legend>', $this->Form->control('kind', [
                    'type' => 'checkbox',
                    'label' => ' ' . __d('expenses', 'Filter by Counter'),
                    'id' => 'FilterCounter',
                    'name' => 'filter_counter',
                    'autocomplete' => 'off',
                    'hiddenField' => false,
                ])),
            'filter_counter_unlock' => [
                'method' => 'unlockField',
                'parameters' => ['filter_counter'],
            ],
            'div_counter_start' => empty($counters) ? null : '<div id="FilterCounters">',
            'counter' => empty($counters) ? null : [
                'method' => 'control',
                'parameters' => ['counter', [
                    'type' => 'select',
                    'options' => $counters,
                    'multiple' => 'checkbox',
                    'label' => false,
                ]],
            ],
            'div_counter_end' => empty($counters) ? null : '</div>',
            'fs_user_end' => empty($counters) ? null : '</fieldset>',

            'fs_kind_span_start' => '<fieldset>',
            'lg_kind_span' => sprintf('<legend>%s</legend>', __d('expenses', 'Filters')),
            'start' => [
                'method' => 'control',
                'parameters' => ['start', [
                    'type' => 'date',
                    'label' => __d('expenses', 'Issued from') . ':',
                ]],
            ],
            'end' => [
                'method' => 'control',
                'parameters' => ['end', [
                    'type' => 'date',
                    'label' => __d('expenses', 'Issued to') . ':',
                ]],
            ],
            'overdue' => [
                'method' => 'control',
                'parameters' => ['overdue', [
                    'type' => 'checkbox',
                    'label' => __d('expenses', 'Only overdue invoices'),
                ]],
            ],
            'fs_kind_span_end' => '</fieldset>',

            'submit' => [
                'method' => 'button',
                'parameters' => [__d('expenses', 'Print')],
            ],
            'form_end' => [
                'method' => 'end',
                'parameters' => [],
            ],
        ],
    ],
];

echo $this->Lil->form($report, 'Expenses.Expenses.report_unpaid');

$this->Lil->jsReady('if ($("#FilterCounter").attr("checked") != "checked") $("#FilterCounters").hide();');
$this->Lil->jsReady('$("#FilterCounter").click(function(){ ' .
    '$("#FilterCounters").toggle(); ' .
    'if ($("#FilterCounter").attr("checked") != "checked") { ' .
    '   $("#FilterCounters input").attr("checked", false);' .
    '}' .
    '});');
