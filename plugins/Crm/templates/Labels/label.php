<?php
use Cake\Core\Configure;
use Cake\Routing\Router;

    $labelSelectForm = [
        'title_for_layout' => __d('crm', 'Print Adrema "{0}"', h($adrema->title)),
        'form' => [
            'defaultHelper' => $this->Form,
            'pre' => '<div class="form">',
            'post' => '</div>',
            'lines' => [
                'form_start' => [
                    'method' => 'create',
                    'parameters' => [
                        null,
                        'parameters' => [
                            'url' => ['action' => 'export'],
                            'type' => 'get',
                        ],
                    ],
                ],
                'adrema' => [
                    'method' => 'control',
                    'parameters' => [
                        'field' => 'adrema',
                        'options' => [
                            'type' => 'hidden',
                            'value' => $adrema->id,
                        ],
                    ],
                ],
                'label' => [
                    'method' => 'control',
                    'parameters' => [
                        'field' => 'label',
                        'options' => [
                            'type' => 'select',
                            'label' => __d('crm', 'Label') . ':',
                            'options' => Configure::read('Crm.labelTemplates'),
                            'value' => $label,
                        ],
                    ],
                ],

                'submit' => [
                    'method' => 'button',
                    'parameters' => [
                        __d('crm', 'Print'),
                        ['type' => 'submit'],
                    ],
                ],
                'form_end' => [
                    'method' => 'end',
                    'parameters' => [],
                ],
            ],
        ],
    ];

    if (!$label) {
        $label_keys = array_keys(Configure::read('Crm.labelTemplates'));
        $label = reset($label_keys);
    }

    /** Show costum fields form selected label */
    $fields = Configure::read('Crm.label.' . $label . '.form');
    if (!empty($fields)) {
        $this->Lil->insertIntoArray($labelSelectForm['form']['lines'], $fields, ['after' => 'label']);
    }

    echo $this->Lil->form($labelSelectForm, 'Crm.Labels.label');

    $this->Lil->jsReady(sprintf(
        '$("#label").change(function() {document.location.href="%s&label="+$(this).val();});',
        Router::url(['?' => ['adrema' => $adrema->id]])
    ));
