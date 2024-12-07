<?php
use Cake\I18n\DateTime;

$months = [];
for ($i = 1; $i <= 12; $i++) {
    $months[$i] = (new DateTime())->setDate(2024, $i, 1)->i18nFormat('MMMM');
}

$cur_year = (new DateTime())->i18nFormat('yyyy');
if (empty($min_year) || $min_year > $cur_year) {
    $min_year = $cur_year - 3;
}
$years = []; for (
    $i = $min_year; $i <= $cur_year + 1;
    $i++
) {
    $years[$i] = $i;
}

$time = new DateTime();
$report = [
    'title_for_layout' => __d('documents', 'EXPORT: eRačuni'),
    'form' => [
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'class' => $this->Form,
                'method' => 'create',
                'parameters' => [null, ['type' => 'GET']],
            ],

            'counters' => [
                'class' => $this->Form,
                'method' => 'control',
                'parameters' => [
                    'counter',
                    'options' => [
                        'type' => 'select',
                        'options' => $counters,
                        'label' => __d('documents', 'Counter') . ':',
                        'empty' => '-- ' . __d('documents', 'All Counters') . ' --',
                        'default' => $this->request->getQuery('counter'),
                    ],
                ],
            ],

            'fs_kind_basic_start' => '<fieldset>',
            'lg_kind_basic' => sprintf(
                '<legend>%s</legend>',
                $this->Form->control('kind', [
                    'type' => 'radio',
                    'options' => ['month' => ' ' . __d('documents', 'Specify Month')],
                    'hiddenField' => false,
                    'default' => 'month',
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
                        'default' => $this->request->getQuery('start'),
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
                        'default' => $this->request->getQuery('end'),
                    ],
                ],
            ],
            'fs_kind_span_end' => '</fieldset>',

            'submit' => [
                'class' => $this->Form,
                'method' => 'button',
                'parameters' => [
                    __d('documents', 'Export'), ['type' => 'submit'],
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
?>

<script type="text/javascript">
    $(document).ready(function() {
        $("#start, #end").change(function () {
            $("#kind-span").prop("checked", true);
        });

        $("#month, #year").change(function () {
            $("#kind-month").prop("checked", true);
        });
    });
</script>