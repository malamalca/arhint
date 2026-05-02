<?php
/**
 * @var \App\View\AppView $this
 * @var \Expenses\Model\Entity\BookingRuleFilter $filter
 * @var array<string, string> $operatorList
 * @var array<string, string> $endOperatorList
 */

$brfEdit = [
    'title_for_layout' => $filter->id
        ? __d('expenses', 'Edit Filter')
        : __d('expenses', 'Add Filter'),
    'menu' => [
        'delete' => [
            'title'   => __d('expenses', 'Delete'),
            'visible' => (bool)$filter->id,
            'url'     => [
                'plugin'     => 'Expenses',
                'controller' => 'BookingRuleFilters',
                'action'     => 'delete',
                $filter->id,
            ],
            'params' => [
                'confirm' => __d('expenses', 'Are you sure you want to delete this filter?'),
            ],
        ],
    ],
    'form' => [
        'defaultHelper' => $this->Form,
        'pre'  => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method'     => 'create',
                'parameters' => ['model' => $filter],
            ],
            'id' => [
                'method'     => 'hidden',
                'parameters' => ['field' => 'id'],
            ],
            'rule_id' => [
                'method'     => 'hidden',
                'parameters' => ['field' => 'rule_id'],
            ],

            'fs_start' => '<fieldset>',
            'lg'       => sprintf('<legend>%s</legend>', __d('expenses', 'Filter Condition')),

            'left_bracket_count' => [
                'method'     => 'control',
                'parameters' => ['field' => 'left_bracket_count', [
                    'type'  => 'number',
                    'min'   => 0,
                    'step'  => 1,
                    'label' => __d('expenses', 'Opening brackets (') . ':',
                ]],
            ],
            'field' => [
                'method'     => 'control',
                'parameters' => ['field' => 'field', [
                    'type'        => 'text',
                    'label'       => __d('expenses', 'Field') . ':',
                    'placeholder' => __d('expenses', 'e.g. no, iban, descript, net_total'),
                ]],
            ],
            'operator' => [
                'method'     => 'control',
                'parameters' => ['field' => 'operator', [
                    'type'    => 'select',
                    'label'   => __d('expenses', 'Operator') . ':',
                    'options' => $operatorList,
                ]],
            ],
            'value' => [
                'method'     => 'control',
                'parameters' => ['field' => 'value', [
                    'type'        => 'text',
                    'label'       => __d('expenses', 'Value') . ':',
                    'placeholder' => __d('expenses', 'e.g. IV, SI56610006100000062, Placa'),
                ]],
            ],
            'right_bracket_count' => [
                'method'     => 'control',
                'parameters' => ['field' => 'right_bracket_count', [
                    'type'  => 'number',
                    'min'   => 0,
                    'step'  => 1,
                    'label' => __d('expenses', 'Closing brackets )') . ':',
                ]],
            ],
            'end_operator' => [
                'method'     => 'control',
                'parameters' => ['field' => 'end_operator', [
                    'type'    => 'select',
                    'label'   => __d('expenses', 'Connect next') . ':',
                    'options' => $endOperatorList,
                ]],
            ],
            'sort' => [
                'method'     => 'control',
                'parameters' => ['field' => 'sort', [
                    'type'  => 'number',
                    'step'  => 1,
                    'label' => __d('expenses', 'Sort order') . ':',
                ]],
            ],

            'fs_end' => '</fieldset>',

            'submit' => [
                'method'     => 'submit',
                'parameters' => ['label' => __d('expenses', 'Save')],
            ],
            'form_end' => [
                'method' => 'end',
            ],
        ],
    ],
];

echo $this->Lil->form($brfEdit, 'Expenses.BookingRuleFilters.edit');
