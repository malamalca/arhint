<?php
use Cake\Core\Configure;
use Cake\Routing\Router;

    $labelSelectForm = [
        'title_for_layout' => __d('lil_crm', 'Print Adrema "{0}"', h($adrema->title)),
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
                            'label' => __d('lil_crm', 'Label') . ':',
                            'options' => Configure::read('LilCrm.labelTemplates'),
                            'value' => $label,
                        ],
                    ],
                ],

                'submit' => [
                    'method' => 'submit',
                    'parameters' => [
                        'label' => __d('lil_crm', 'Next'),
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
        $label_keys = array_keys(Configure::read('LilCrm.labelTemplates'));
        $label = reset($label_keys);
    }

    /** Show costum fields form selected label */
    $fields = Configure::read('LilCrm.label.' . $label . '.form');
    if (!empty($fields)) {
        $this->Lil->insertIntoArray($labelSelectForm['form']['lines'], $fields, ['after' => 'label']);
    }

    echo $this->Lil->form($labelSelectForm, 'LilCrm.Labels.label');

    $this->Lil->jsReady(sprintf(
        '$("#label").change(function() {document.location.href="%s&label="+$(this).val();});',
        Router::url(['?' => ['adrema' => $adrema->id]])
    ));
