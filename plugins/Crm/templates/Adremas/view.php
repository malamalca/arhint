<?php
    use Cake\Routing\Router;

    $label_select = [
        'title_for_layout' => h($adrema->title),
        'menu' => [
            /*'save' => [
                'title' => __d('crm', 'Save As'),
                'visible' => !empty($adremaId),
                'url' => [
                    'plugin' => 'Crm',
                    'controller' => 'Adremas',
                    'action' => 'duplicate',
                    '?' => ['adrema' => $adremaId ?? 'default'],
                ],
            ],*/
            'edit' => [
                'title' => __d('crm', 'Edit'),
                'visible' => true,
                'url' => [
                    'action' => 'edit',
                    $adrema->id,
                ],
            ],
            'delete' => [
                'title' => __d('crm', 'Delete'),
                'visible' => true,
                'url' => [
                    'action' => 'delete',
                    $adrema->id,
                ],
                'params' => [
                    'confirm' => __d('crm', 'Are you sure you want to delete the adrema?'),
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
                        $adrema,
                        'parameters' => [
                            'url' => ['controller' => 'Labels', 'action' => 'label'],
                            'type' => 'get',
                        ],
                    ],
                ],

                'adrema' => [
                    'method' => 'hidden',
                    'parameters' => ['adrema', ['value' => $adrema->id]],
                ],

                'fs_adrema_spacer' => '<br />',
                'fs_adrema_start' => '<fieldset>',
                'fs_adrema_legend' => sprintf('<legend>%s</legend>', 'Adrema'),
                'fs_table_header' => '<table cellpadding="0" cellspacing="0" id="crm-adrema">' .
                    '<thead><tr>' .
                    sprintf('<th class="td-adrema-link">&nbsp;</th>') .
                    sprintf('<th class="td-adrema-title">%s</th>', __d('crm', 'Title')) .
                    sprintf('<th class="td-adrema-address">%s</th>', __d('crm', 'Address')) .
                    sprintf('<th class="td-adrema-email nowrap">%s</th>', __d('crm', 'Email')) .
                    sprintf('<th class="td-adrema-remove">&nbsp;</th>') .
                    '</tr></thead>',
                'fs_table_footer' =>
                    '<tfoot><tr>' .
                    sprintf(
                        '<th colspan="5">%s</th>',
                        $this->Html->link(
                            __d('crm', 'Add new address'),
                            [
                                'controller' => 'AdremasContacts',
                                'action' => 'edit',
                                '?' => ['adrema' => $adrema->id],
                            ],
                            [
                                'class' => 'btn-small',
                            ]
                        )
                    ) .
                    '</tr></tfoot></table>',
                'fs_adrema_end' => '</fieldset>',
                'submit_print' => [
                    'method' => 'button',
                    'parameters' => [
                        __d('crm', 'Print'),
                        ['type' => 'submit', 'name' => 'process', 'value' => 'print'],
                    ],
                ],
                'submit_spacer' => '&nbsp;',
                'submit_email' => [
                    'method' => 'button',
                    'parameters' => [
                        __d('crm', 'Email'),
                        ['type' => 'submit', 'name' => 'process', 'value' => 'email'],
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
        $adr['adr' . $k . '_1'] = '<tr>';
        $adr['adr' . $k . '_2'] = sprintf(
            '<td class="td-adrema-link"><img src="%s" /></td>',
            empty($address->contact) ? '' : Router::url('/crm/img/link.gif')
        );
        $adr['adr' . $k . '_3'] = sprintf(
            '<td class="td-adrema-title nowrap"><a href="%1$s">%2$s</a></td>',
            Router::url(['controller' => 'AdremasContacts', 'action' => 'edit', $address->id]),
            h($address->contact->title ?? 'N/A')
        );
        $adr['adr' . $k . '_4'] = sprintf('<td class="td-adrema-address">%s</td>',
            h($address->contacts_address ?? '')
        );
        $adr['adr' . $k . '_5'] = sprintf(
            '<td class="td-adrema-email">%s</td>',
            h($address->contacts_email->email ?? '')
        );
        $adr['adr' . $k . '_6'] = sprintf(
            '<td class="td-adrema-remove">' .
            '<a href="%1$s" onclick="return confirm(\'%3$s\');" class="btn-small btn-flat"><img src="%2$s" /></a></td>',
            Router::url(['controller' => 'AdremasContacts', 'action' => 'delete', $address->id]),
            Router::url('/crm/img/remove.gif'),
            h(__d('crm', 'Are you sure you want to remove this contact from adrema?'))
        );
        $adr['adr' . $k . '_7'] = '</tr>';
    }

    $this->Lil->insertIntoArray($label_select['form']['lines'], $adr, ['after' => 'fs_table_header']);

    echo $this->Lil->form($label_select, 'Crm.Adremas.view');
?>
<script type="text/javascript">
    var step2Url = "<?php echo Router::url(['action' => 'adrema']); ?>";

    $(document).ready(function() {
        $("#adrema").change(function() {
            document.location.href = step2Url + "/" + $(this).val();
        });
    });
</script>
