<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\Expenses\Model\Entity\BookingRule> $data
 * @var array<string, string> $modelList
 */

$brIndex = [
    'title_for_layout' => __d('expenses', 'Booking Rules'),
    'menu' => [
        'add' => [
            'title' => __d('expenses', 'Add Rule'),
            'visible' => $this->getCurrentUser()->hasRole('admin'),
            'url' => ['action' => 'edit'],
        ],
    ],
    'table' => [
        'head' => [
            'rows' => [
                [
                    'columns' => [
                        ['html' => __d('expenses', 'Model')],
                        ['html' => __d('expenses', 'Title')],
                        ['html' => __d('expenses', 'Filters')],
                        ['html' => __d('expenses', 'Account Entries')],
                        ['html' => '', 'params' => ['style' => 'width:5em']],
                    ],
                ],
            ],
        ],
        'body' => [
            'rows' => array_map(function ($rule) use ($modelList) {
                return [
                    'columns' => [
                        ['html' => h($modelList[$rule->model] ?? $rule->model)],
                        [
                            'html' => $this->Html->link(h($rule->title), [
                                'plugin' => 'Expenses',
                                'controller' => 'BookingRules',
                                'action' => 'view',
                                $rule->id,
                            ]),
                        ],
                        ['html' => h((string)count($rule->booking_rule_filters ?? []))],
                        ['html' => h((string)count($rule->booking_rule_account_entries ?? []))],
                        [
                            'html' => $this->Lil->editLink([
                                'plugin' => 'Expenses',
                                'controller' => 'BookingRules',
                                'action' => 'edit',
                                $rule->id,
                            ]) . ' ' . $this->Lil->deleteLink([
                                'plugin' => 'Expenses',
                                'controller' => 'BookingRules',
                                'action' => 'delete',
                                $rule->id,
                            ], ['confirm' => __d('expenses', 'Are you sure you want to delete this booking rule?')]),
                            'params' => ['class' => 'right-align nowrap'],
                        ],
                    ],
                ];
            }, iterator_to_array($data)),
        ],
    ],
];

echo $this->Lil->index($brIndex, 'Expenses.BookingRules.index');
