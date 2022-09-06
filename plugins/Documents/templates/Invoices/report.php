<?php
use Cake\I18n\FrozenTime;

/**
 * This is tmtr_groups/admin_edit template file.
 */
$months = []; for (
    $i = 1; $i <= 12;
    $i++
) {
    $months[$i] = strftime('%B', mktime(0, 0, 0, $i));
}

$cur_year = strftime('%Y');
if (empty($min_year) || $min_year > $cur_year) {
    $min_year = $cur_year - 3;
}
$years = []; for (
    $i = $min_year; $i <= $cur_year + 1;
    $i++
) {
    $years[$i] = $i;
}

$time = new FrozenTime();
$report = [
    'title_for_layout' => __d('documents', 'REPORT: Invoices'),
    'form' => [
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'class' => $this->Form,
                'method' => 'create',
                'parameters' => [null, ['type' => 'GET']],
            ],

            'fs_kind_basic_start' => '<fieldset>',
            'lg_kind_basic' => sprintf(
                '<legend>%s</legend>',
                $this->Form->control('kind', [
                    'type' => 'radio',
                    'options'      => ['month' => ' ' . __d('documents', 'Specify Month')],
                    'hiddenField' => false,
                    'default'      => 'month',
                    'templates' => ['inputContainer' => '{{content}}'],
                    'label' => false,
                ])
            ),
            '<div>',
            'month2' => [
                'class' => $this->Form,
                'method' => 'control',
                'parameters' => [
                    'month',
                    'options' => [
                        'type' => 'select',
                        'templates' => ['inputContainer' => '{{content}}'],
                        'options' => $months,
                        'label' => false,
                        'default' => (int)$time->i18nFormat('MM'),
                        'class' => 'browser-default',
                    ],
                ],
            ],
            'year2' => [
                'class' => $this->Form,
                'method' => 'control',
                'parameters' => [
                    'year',
                    'options' => [
                        'type' => 'select',
                        'templates' => ['inputContainer' => '{{content}}'],
                        'options' => $years,
                        'label' => false,
                        'default' => $time->i18nFormat('yyyy'),
                        'class' => 'browser-default',
                    ],
                ],
            ],
            '</div>',
            'fs_kind_basic_end' => '</fieldset>',

            'fs_kind_span_start' => '<fieldset>',
            'lg_kind_span' => sprintf(
                '<legend>%s</legend>',
                $this->Form->control('kind', [
                    'type' => 'radio',
                    'options' => ['span' => ' ' . __d('documents', 'From - to Date')],
                    'hiddenField' => false,
                    'templates' => ['inputContainer' => '{{content}}'],
                    'label' => false,
                ])
            ),
            'start' => [
                'class' => $this->Form,
                'method' => 'control',
                'parameters' => [
                    'field' => 'start',
                    'options' => [
                        'type' => 'date',
                        'label' => __d('documents', 'Start') . ':',
                    ],
                ],
            ],
            'end' => [
                'class' => $this->Form,
                'method' => 'control',
                'parameters' => [
                    'field' => 'end',
                    'options' => [
                        'type' => 'date',
                        'label' => __d('documents', 'End') . ':',
                    ],
                ],
            ],
            'fs_kind_span_end' => '</fieldset>',

            'submit' => [
                'class' => $this->Form,
                'method' => 'submit',
                'parameters' => [
                    'label' => __d('documents', 'Print'),
                ],
            ],
            'form_end' => [
                'class' => $this->Form,
                'method' => 'end',
                'parameters' => [],
            ],
        ],
    ],
];

echo $this->Lil->form($report, 'Documents.Invoices.report');
