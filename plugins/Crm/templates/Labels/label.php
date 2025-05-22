<?php
use Cake\Core\Configure;
use Cake\Routing\Router;

$process = $this->getRequest()->getQuery('process', 'print');

$labelSelectForm = [
    'title_for_layout' => $process == 'print' ? __d('crm', 'Print Adrema "{0}"', h($adrema->title)) : __d('crm', 'Email Adrema "{0}"', h($adrema->title)),
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
                        'url' => ['action' => $process == 'print' ? 'export' : 'email'],
                        'type' => 'file',
                    ],
                ],
            ],
            'process' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'process',
                    'options' => [
                        'type' => 'hidden',
                        'value' => $process,
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
                        'options' => $process == 'print' ? Configure::read('Crm.labelTemplates') : Configure::read('Crm.emailTemplates'),
                        'value' => $label,
                    ],
                ],
            ],

            'atch.1' => $process == 'print' ? null : [
                'method' => 'control',
                'parameters' => [
                    'field' => 'attachment.1',
                    'options' => [
                        'type' => 'file',
                        'label' => __d('crm', 'Attachment 1') . ':',
                    ],
                ],
            ],
            'atch.2' => $process == 'print' ? null : [
                'method' => 'control',
                'parameters' => [
                    'field' => 'attachment.2',
                    'options' => [
                        'type' => 'file',
                        'label' => __d('crm', 'Attachment 2') . ':',
                    ],
                ],
            ],
            'atch.3' => $process == 'print' ? null : [
                'method' => 'control',
                'parameters' => [
                    'field' => 'attachment.3',
                    'options' => [
                        'type' => 'file',
                        'label' => __d('crm', 'Attachment 3') . ':',
                    ],
                ],
            ],

            'submit' => [
                'method' => 'button',
                'parameters' => [
                    $process == 'print' ? __d('crm', 'Print') : __d('crm', 'Send All'),
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
    $label_keys = array_keys(Configure::read($process == 'print' ? 'Crm.labelTemplates' : 'Crm.emailTemplates'));
    $label = reset($label_keys);
}

/** Show costum fields form selected label */
$fields = Configure::read($process == 'print' ? 'Crm.label.' . $label . '.form' : 'Crm.email.' . $label . '.form');
if (!empty($fields)) {
    $this->Lil->insertIntoArray($labelSelectForm['form']['lines'], $fields, ['after' => 'label']);
}

echo $this->Lil->form($labelSelectForm, 'Crm.Labels.label');

$this->Lil->jsReady(sprintf(
    '$("#label").change(function() {document.location.href="%s&label="+$(this).val();});',
    Router::url(['?' => ['adrema' => $adrema->id, 'process' => $process]])
));
