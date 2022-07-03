<?php
use Cake\I18n\FrozenDate;

$counter = $document->documents_counter;

if ($document->isNew()) {
    $layoutTitle = __d(
        'documents',
        'Add a Travel Order #{0} <span class="light">({1})</span>',
        $counter->counter + 1,
        h($counter->title)
    );
} else {
    $layoutTitle = __d(
        'documents',
        'Edit a Travel Order #{0} <span class="light">({1})</span>',
        $document->counter,
        h($counter->title)
    );
}

$documentEdit = [
    'title_for_layout' => $layoutTitle,
    'menu' => [
        'editPreview' => [
            'title' => __d('documents', 'Preview'),
            'visible' => true,
            'url' => 'javascript:void();',
            'params' => ['id' => 'MenuEditPreview'],
        ],
    ],
    'form' => [
        'defaultHelper' => $this->Form,

        'pre' => '<div class="form">',
        'post' => '</div>',

        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => [
                    $document, [
                        'type' => 'file',
                        'id' => 'travel_orders-edit-form',
                        'idPrefix' => 'travel_order',
                        'url' => [
                            'action' => 'edit',
                            $document->id,
                            '?' => ['counter' => $document->counter_id],
                        ],
                    ],
                ],
            ],
            'referer' => [
                'method' => 'control',
                'parameters' => ['referer', [
                    'type' => 'hidden',
                    'default' => $this->getRequest()->getQuery('redirect'),
                ]],
            ],
            'id' => [
                'method' => 'control',
                'parameters' => ['id', ['type' => 'hidden']],
            ],
            'owner_id' => [
                'method' => 'control',
                'parameters' => ['owner_id', [
                    'type' => 'hidden',
                ]],
            ],
            'counter_id' => [
                'method' => 'control',
                'parameters' => ['counter_id', ['type' => 'hidden']],
            ],
            'counter' => [
                'method' => 'control',
                'parameters' => [
                    'counter', [
                        'type' => 'hidden',
                        'default' => $counter->counter + 1,
                    ],
                ],
            ],
            'status' => [
                'method' => 'control',
                'parameters' => [
                    'status', [
                        'type' => 'hidden',
                        'value' => 'new',
                    ],
                ],
            ],
            'duplicate' => [
                'method' => 'control',
                'parameters' => ['duplicate', ['type' => 'hidden']],
            ],
            'tpl_header_id' => [
                'method' => 'control',
                'parameters' => ['tpl_header_id', ['type' => 'hidden', 'default' => $counter->tpl_header_id]],
            ],
            'tpl_footer_id' => [
                'method' => 'control',
                'parameters' => ['tpl_footer_id', ['type' => 'hidden', 'default' => $counter->tpl_footer_id]],
            ],
            'tpl_body_id' => [
                'method' => 'control',
                'parameters' => ['tpl_body_id', ['type' => 'hidden', 'default' => $counter->tpl_body_id]],
            ],

            ////////////////////////////////////////////////////////////////////////////////////
            'fs_basic_start' => '<fieldset>',
            'fs_basic_legend' => sprintf('<legend>%s</legend>', __d('documents', 'Basics')),
            'no' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'no', [
                        'label' => __d('documents', 'Document no') . ':',
                        'disabled' => !empty($counter->mask),
                    ],
                ],
            ],
            'location' => [
                'method' => 'control',
                'parameters' => [
                    'location',
                    [
                        'label' => __d('documents', 'Location') . ':',
                        'error' => [
                            'empty' => __d('documents', 'Please enter location.'),
                        ],
                    ],
                ],
            ],
            'dat_issue' => [
                'method' => 'control',
                'parameters' => [
                    'dat_issue',
                    [
                        'label' => __d('documents', 'Date') . ':',
                        'default' => new FrozenDate(),
                        'error' => [
                            'empty' => __d('documents', 'Please date of order.'),
                        ],
                    ],
                ],
            ],
            'fs_basic_end' => '</fieldset>', // basics

            ////////////////////////////////////////////////////////////////////////////////////
            'tror_travel_start' => '<fieldset>',
            'tror_travel_legend' => sprintf('<legend>%s</legend>', __d('documents', 'Travel Details')),

            'employee_id' => [
                'method' => 'control',
                'parameters' => [
                    'employee_id',
                    $this->getCurrentUser()->hasRole('admin') ?
                        [
                            'type' => 'select',
                            'label' => [
                                'text' => __d('documents', 'Traveller') . ':',
                                'class' => 'active',
                            ],
                            'default' => $this->getCurrentUser()->get('id'),
                            'class' => 'browser-default',
                            'options' => $users,
                        ]
                        :
                        [
                            'type' => 'hidden',
                            'value' => $this->getCurrentUser()->get('id'),
                        ],
                ],
            ],
            /*'taskee' => [
                'method' => 'control',
                'parameters' => [
                    'taskee',
                    [
                        'label' => __d('documents', 'Travel ordered by') . ':',
                        'default' => $this->getCurrentUser()->get('name'),
                    ],
                ],
            ],*/
            'dat_task' => [
                'method' => 'control',
                'parameters' => [
                    'dat_task',
                    [
                        'label' => __d('documents', 'Travel Task Date') . ':',
                        'default' => new FrozenDate(),
                    ],
                ],
            ],
            'title' => [
                'method' => 'control',
                'parameters' => [
                    'title',
                    [
                        'label' => __d('documents', 'Travel Task') . ':',
                    ],
                ],
            ],
            'descript' => [
                'method' => 'control',
                'parameters' => [
                    'descript',
                    [
                        'label' => __d('documents', 'Travel Description') . ':',
                        'default' => $counter->template_descript,
                    ],
                ],
            ],
            'description_hint' => sprintf(
                '<div class="helper-text">%s</div>',
                __d('documents', 'Enter travel route (eg. New York - Jersey - New York)')
            ),
            'departure' => [
                'method' => 'control',
                'parameters' => [
                    'departure',
                    [
                        'step' => 60,
                        'label' => [
                            'text' => __d('documents', 'Travel Departure') . ':',
                            'class' => 'active',
                        ],
                    ],
                ],
                ],
            'arrival' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'arrival',
                    'options' => [
                        'step' => 60,
                        'label' => [
                            'text' => __d('documents', 'Travel Arrival') . ':',
                            'class' => 'active',
                        ],
                    ],
                ],
            ],
            'tror_travel_end' => '</fieldset>',

            ////////////////////////////////////////////////////////////////////////////////////
            'tror_vehicle_start' => '<fieldset>',
            'tror_vehicle_legend' => sprintf('<legend>%s</legend>', __d('documents', 'Vehicle')),
            'vehicle_title' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'vehicle_title',
                    'options' => [
                        'label' => __d('documents', 'Vehicle\'s name') . ':',
                    ],
                ],
            ],
            'vehicle_registration' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'vehicle_registration',
                    'options' => [
                        'label' => __d('documents', 'Vehicle\'s Registration') . ':',
                    ],
                ],
            ],
            'vehicle_owner' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'vehicle_owner',
                    'options' => [
                        'label' => __d('documents', 'Vehicle\'s Owner') . ':',
                    ],
                ],
            ],
            'tror_vehicle_end' => '</fieldset>',

            ////////////////////////////////////////////////////////////////////////////////////
            'tror_expenses_start' => '<fieldset>',
            'tror_expenses_legend' => sprintf('<legend>%s</legend>', __d('documents', 'Travel Advance')),

            /*'client' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'payer.title',
                    [
                        'label' => __d('documents', 'Travel\'s Payer') . ':',
                        'autocomplete' => 'off',
                    ],
                ],
            ],
            'client_error' => [
                'method' => 'error',
                'parameters' => ['payer.title', __d('documents', 'Please choose a payer')],
            ],
            'client_kind_error' => [
                'method' => 'error',
                'parameters' => ['payer.kind', __d('documents', 'Please choose a payer')],
            ],*/

            'advance' => [
                'method' => 'control',
                'parameters' => [
                    'advance',
                    [
                        'label' => __d('documents', 'Amount [€]') . ':',
                    ],
                ],
            ],
            'dat_advance' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'dat_advance',
                    'options' => [
                        'label' => __d('documents', 'Date of Advance Payment') . ':',
                    ],
                ],
            ],
            'tror_expenses_end' => '</fieldset>',

            ////////////////////////////////////////////////////////////////////////////////////
            'submit' => [
                'method' => 'button',
                'parameters' => [
                    __d('documents', 'Save'),
                ],
            ],

            'loop' => /*!$document->isNew()*/ 1 == 1 ? null : [
                'method' => 'control',
                'parameters' => [
                    'field' => 'loop',
                    'options' => [
                        'type' => 'checkbox',
                        'label' => __d('documents', 'Add another travel order after saving this one'),
                    ],
                ],
            ],
            'form_end' => [
                'method' => 'end',
                'parameters' => [],
            ],
        ],
    ],
];

echo $this->Lil->form($documentEdit, 'Documents.TravelOrders.edit');
