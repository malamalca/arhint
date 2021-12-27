<?php
    use Cake\Routing\Router;

    $label_select = [
        'title_for_layout' => __d('crm', 'Select and Edit Adrema'),
        'menu' => [
            'save' => [
                'title' => __d('crm', 'Save As'),
                'visible' => !empty($adremaId),
                'url' => [
                    'plugin' => 'Crm',
                    'controller' => 'Adremas',
                    'action' => 'duplicate',
                    '?' => ['adrema' => $adremaId ?? 'default'],
                ],
            ],
            'edit' => [
                'title' => __d('crm', 'Edit'),
                'visible' => !empty($adremaId),
                'url' => [
                    'plugin' => 'Crm',
                    'controller' => 'Adremas',
                    'action' => 'edit',
                    $adremaId,
                ],
            ],
            'delete' => [
                'title' => __d('crm', 'Delete'),
                'visible' => !empty($adremaId),
                'url' => [
                    'plugin' => 'Crm',
                    'controller' => 'Adremas',
                    'action' => 'delete',
                    $adremaId,
                ],
                'params' => [
                    'confirm' => __d('crm', 'Are you sure you want to delete current adrema?'),
                ],
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
                        null,
                        'parameters' => [
                            'url' => ['action' => 'label'],
                            'type' => 'get',
                        ],
                    ],
                ],

                'adrema' => [
                    'method' => 'control',
                    'parameters' => [
                        'field' => 'adrema',
                        'options' => [
                            'type' => 'select',
                            'label' => __d('crm', 'Adrema') . ':',
                            'options' => $adremas,
                            'value' => $adremaId,
                        ],
                    ],
                ],
                'fs_adrema_start' => '<fieldset>',
                'fs_adrema_legend' => sprintf('<legend>%s</legend>', 'Adrema'),
                'fs_table_header' => '<table cellpadding="0" cellspacing="0" class="index-static" id="crm-adrema">' .
                    '<thead><tr>' .
                    sprintf('<th class="td-adrema-link">&nbsp;</th>') .
                    sprintf('<th class="td-adrema-title">%s</th>', __d('crm', 'Title')) .
                    sprintf('<th class="td-adrema-street">%s</th>', __d('crm', 'Street')) .
                    sprintf('<th class="td-adrema-city">%s</th>', __d('crm', 'City')) .
                    sprintf('<th class="td-adrema-zip">%s</th>', __d('crm', 'ZIP')) .
                    sprintf('<th class="td-adrema-contry">%s</th>', __d('crm', 'Country')) .
                    sprintf('<th class="td-adrema-remove">&nbsp;</th>') .
                    '</tr></thead>',
                'fs_table_footer' =>
                    '<tfoot><tr>' .
                    sprintf(
                        '<th colspan="7">%s</th>',
                        $this->Html->link(
                            __d('crm', 'Add new address'),
                            [
                                'plugin' => 'Crm',
                                'controller' => 'Labels',
                                'action' => 'editAddress',
                                '?' => ['adrema' => $adremaId],
                            ]
                        )
                    ) .
                    '</tr></tfoot></table>',
                    'fs_adrema_end' => '</fieldset>',
                    'submit' => [
                    'method' => 'submit',
                    'parameters' => [
                        'label' => __d('crm', 'Next'),
                    ],
                ],
                'form_end' => [
                    'method' => 'end',
                    'parameters' => [],
                ],
            ],
        ],
    ];

    $adr = [];
    foreach ($addresses as $k => $address) {
        $source = $address;
        if ($address->contacts_address) {
            $source = $address->contacts_address;
        }

        $adr['adr' . $k . '_1'] = '<tr>';
        $adr['adr' . $k . '_11'] = sprintf(
            '<td class="td-adrema-link"><img src="%s" /></td>',
            empty($address->contacts_address) ? '' : Router::url('/crm/img/link.gif')
        );
        $adr['adr' . $k . '_2'] = sprintf(
            '<td class="td-adrema-title"><a href="%1$s">%2$s</a></td>',
            Router::url(['action' => 'edit-address', $address->id]),
            h($address->title)
        );
        $adr['adr' . $k . '_3'] = sprintf('<td class="td-adrema-street">%s</td>', h($source->street));
        $adr['adr' . $k . '_4'] = sprintf(
            '<td class="td-adrema-city">%s</td>',
            h($source->city)
        );
        $adr['adr' . $k . '_5'] = sprintf(
            '<td class="td-adrema-zip">%s</td>',
            h($source->zip)
        );
        $adr['adr' . $k . '_6'] = sprintf(
            '<td class="td-adrema-country">%s</td>',
            h($source->country)
        );
        $adr['adr' . $k . '_7'] = sprintf(
            '<td class="td-adrema-remove">' .
            '<a href="%1$s" onclick="return confirm(\'%3$s\');"><img src="%2$s" /></a></td>',
            Router::url(['action' => 'delete-address', $address->id]),
            Router::url('/crm/img/remove.gif'),
            h(__d('crm', 'Are you sure you want to remove this contact from adrema?'))
        );
        $adr['adr' . $k . '_99'] = '</tr>';
    }

    $this->Lil->insertIntoArray($label_select['form']['lines'], $adr, ['after' => 'fs_table_header']);

    if (!$adremas) {
        echo __d('crm', 'No adremas found. Please {0}.', $this->Html->link(
            __d('crm', 'add new adrema'),
            ['controller' => 'adremas', 'action' => 'edit']
        ));
    } else {
        echo $this->Lil->form($label_select, 'Crm.Labels.adrema');
    }
    ?>
<script type="text/javascript">
    var step2Url = "<?php echo Router::url(['action' => 'adrema']); ?>";

    $(document).ready(function() {
        $("#adrema").change(function() {
            document.location.href = step2Url + "/" + $(this).val();
        });
    });
</script>
