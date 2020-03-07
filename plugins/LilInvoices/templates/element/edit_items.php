<?php
$itmIx = 0;
$analytics['itm_' . $itmIx++] =
    '<div class="index2">' .
    '<table cellspacing="0" cellpadding="0" id="invoice-items-table" class="index-static">' .
    '<thead><tr>' .
    sprintf('<th class="actions">%s</th>', $this->Html->image('/lil_invoices/img/link.gif')) .
    sprintf('<th class="left-align">%s</th>', __d('lil_invoices', 'Description')) .
    sprintf('<th class="right-align">%s</th>', __d('lil_invoices', 'Quantity')) .
    sprintf('<th class="left-align">%s</th>', __d('lil_invoices', 'Unit')) .
    sprintf('<th class="right-align">%s</th>', __d('lil_invoices', 'Price per Unit')) .
    sprintf('<th class="right-align nowrap">%s</th>', __d('lil_invoices', 'Discount [%]')) .
    sprintf('<th class="right-align">%s</th>', __d('lil_invoices', 'Unit Total')) .
    sprintf('<th class="right-align nowrap">%s</th>', __d('lil_invoices', 'Tax [%]')) .
    sprintf('<th class="right-align">%s</th>', __d('lil_invoices', 'Total with Tax')) .
    '<th class="center-align">&nbsp;</th>' .
    '</tr></thead>';

$analytics['itm_' . $itmIx++] = '<tbody>';

$itemsGrandTotal = 0;
$itemsTotal = 0;

// create empty item if one does not exist
$invoices_items = $invoice->invoices_items;
if (empty($invoices_items)) {
    $invoices_items[] = (object)[
        'id' => null, 'item_id' => null, 'vat_id' => null, 'vat_percent' => 0, 'vat_title' => '',
        'price' => null, 'discount' => null, 'qty' => null,
    ];
}

$vatOptions = [];
foreach ($vatLevels as $vat) {
    $vatOptions[$vat->id] = $vat->descript;
}

foreach ($invoices_items as $k => $item) {
    $analytics['itm_' . $itmIx++] = '<tr>';

    $analytics['itm_' . $itmIx++] = '<td class="center actions">';
    $analytics['itm_' . $itmIx++] = $this->Html->image('/lil_invoices/img/link.gif', [
        'style' => $item->item_id ? '' : 'display: none;',
        'class' => 'image-item-check',
    ]);
    $analytics['itm_' . $itmIx++] = '</td>';

    $analytics['itm_' . $itmIx++] = '<td class="td-invoices-item-descript">';
    $analytics['itm_' . $itmIx++] = [
        'method' => 'control',
        'parameters' => [
            'field' => 'invoices_items.' . $k . '.item_id',
            ['type' => 'hidden', 'class' => 'invoices-item-item_id'],
        ],
    ];
    $analytics['itm_' . $itmIx++] = [
        'method' => 'control',
        'parameters' => [
            'field' => 'invoices_items.' . $k . '.invoice_id',
            ['type' => 'hidden', 'class' => 'invoices-item-invoice_id'],
        ],
    ];
    $analytics['itm_' . $itmIx++] = [
        'method' => 'control',
        'parameters' => [
            'field' => 'invoices_items.' . $k . '.descript', [
                'type' => 'text',
                'label' => false,
                'size' => 30,
                'class' => 'invoices-item-descript',
                'error' => __d('lil_invoices', 'Blank'),
            ],
        ],
    ];
    $analytics['itm_' . $itmIx++] = '</td>';

    $analytics['itm_' . $itmIx++] = '<td class="td-invoices-item-qty right-align">';
    $analytics['itm_' . $itmIx++] = [
        'method' => 'number',
        'parameters' => [
            'field' => 'invoices_items.' . $k . '.qty', [
                'type' => 'number',
                'step' => '0.01',
                'label' => false,
                'size' => 5,
                'class' => 'invoices-item-qty',
                'error' => __d('lil_invoices', 'Blank'),
            ],
        ],
    ];
    $analytics['itm_' . $itmIx++] = '</td>';

    $analytics['itm_' . $itmIx++] = '<td class="td-invoices-item-unit right-align">';
    $analytics['itm_' . $itmIx++] = [
        'method' => 'text',
        'parameters' => [
            'field' => 'invoices_items.' . $k . '.unit', [
                'type' => 'text',
                'label' => false,
                'size' => 5,
                'class' => 'invoices-item-unit',
                'error' => __d('lil_invoices', 'Blank'),
            ],
        ],
    ];
    $analytics['itm_' . $itmIx++] = '</td>';

    $analytics['itm_' . $itmIx++] = '<td class="td-invoices-item-price right-align">';
    $analytics['itm_' . $itmIx++] = [
        'method' => 'number',
        'parameters' => [
            'field' => 'invoices_items.' . $k . '.price', [
                'type' => 'number',
                'step' => 0.01,
                'label' => false,
                'size' => 12,
                'class' => 'invoices-item-price',
                'error' => __d('lil_invoices', 'Blank'),
            ],
        ],
    ];
    $analytics['itm_' . $itmIx++] = '</td>';

    $analytics['itm_' . $itmIx++] = '<td class="td-invoices-item-discount right-align">';
    $analytics['itm_' . $itmIx++] = [
        'method' => 'number',
        'parameters' => [
            'field' => 'invoices_items.' . $k . '.discount', [
                'type' => 'number',
                'step' => 0.1,
                'label' => false,
                'size' => 5,
                'class' => 'invoices-item-discount',
                'error' => __d('lil_invoices', 'Blank'),
            ],
        ],
    ];
    $analytics['itm_' . $itmIx++] = '</td>';

    $itemTotal = round(round($item->price * (100 - $item->discount) / 100, 4) * $item->qty, 2);
    $itemsTotal += $itemTotal;

    $analytics['itm_' . $itmIx++] = '<td class="td-invoices-item-total right-align">';
    $analytics['itm_' . $itmIx++] = [
        'method' => 'control',
        'parameters' => [
            'field' => 'invoices_items.' . $k . '.id',
            ['type' => 'hidden', 'class' => 'invoices-item-id'],
        ],
    ];
    $analytics['itm_' . $itmIx++] = sprintf('<span>%s</span>', $this->Number->precision($itemTotal, 2));
    $analytics['itm_' . $itmIx++] = '</td>';

    $analytics['itm_' . $itmIx++] = '<td class="td-invoices-item-vat right-align nowrap">';
    $analytics['itm_' . $itmIx++] = [
        'method' => 'control',
        'parameters' => [
            'field' => 'invoices_items.' . $k . '.vat_title',
            ['type' => 'hidden', 'value' => $item->vat_title, 'class' => 'invoices-item-vat_title'],
        ],
    ];
    $analytics['itm_' . $itmIx++] = [
        'method' => 'control',
        'parameters' => [
            'field' => 'invoices_items.' . $k . '.vat_percent', [
                'type' => 'hidden',
                'value' => $this->Number->precision($item->vat_percent, 1),
                'class' => 'invoices-item-vat_percent',
            ],
        ],
    ];
    $analytics['itm_' . $itmIx++] = [
        'method' => 'select',
        'parameters' => [
            'field' => 'invoices_items.' . $k . '.vat_id',
            $vatOptions,
            [
                'type' => 'select',
                'label' => false,
                'value' => $item->vat_id,
                'class' => 'invoices-item-vat_id browser-default',
                'empty' => true,
            ],
        ],
    ];

    $analytics['itm_' . $itmIx++] = '</td>';

    $lineTotal = $itemTotal + round($itemTotal * $item->vat_percent / 100, 2);
    $itemsGrandTotal += $lineTotal;

    $analytics['itm_' . $itmIx++] = '<td class="right-align td-invoices-item-line-total">' .
        sprintf('<span>%s</span>', $this->Number->precision($lineTotal, 2)) .
        '</td>';

    $analytics['itm_' . $itmIx++] = sprintf('<td class="center-align">%s</td>', $this->Html->link(
        $this->Html->image('/lil_invoices/img/remove.gif', ['alt' => __d('lil_invoices', 'Remove Item')]),
        [
            'plugin' => 'LilInvoices',
            'controller' => 'invoices-items',
            'action' => 'delete',
            $item->id,
        ],
        [
            'escape' => false,
            'class' => 'invoices-item-remove',
        ]
    ));

    $analytics['itm_' . $itmIx++] = '</tr>';
}
    $analytics['itm_' . $itmIx++] = '</tbody>';

    // table FOOTER with grand total and add new row link
    $analytics['itm_' . $itmIx++] = '<tfoot><tr>';
    $analytics['itm_' . $itmIx++] = sprintf(
        '<th colspan="3">%s</th>',
        $this->Html->link(
            __d('lil_invoices', 'Add new Item'),
            'javascript:void(0);',
            ['id' => 'add-invoice-item-row']
        )
    );
    $analytics['itm_' . $itmIx++] = sprintf('<th colspan="3" class="right-align">%1$s:</th>', __d('lil_invoices', 'Grand Total'));
    $analytics['itm_' . $itmIx++] = sprintf('<th class="right-align" id="invoice-items-total">%s</th>', $this->Number->precision($itemsTotal, 2));
    $analytics['itm_' . $itmIx++] = '<th class="right-align">&nbsp;</th>';
    $analytics['itm_' . $itmIx++] = sprintf('<th class="right-align" id="invoice-items-grand-total">%s</th>', $this->Number->precision($itemsGrandTotal, 2));

    $analytics['itm_' . $itmIx++] = '<th class="left-align"></th>';
    $analytics['itm_' . $itmIx++] = '</tr></tfoot>';
    $analytics['itm_' . $itmIx++] = '</table></div>';
