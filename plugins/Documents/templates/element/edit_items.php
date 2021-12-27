<?php
$itmIx = 0;
$analytics['itm_' . $itmIx++] =
    '<div class="index2">' .
    '<table cellspacing="0" cellpadding="0" id="document-items-table" class="index-static">' .
    '<thead><tr>' .
    sprintf('<th class="actions">%s</th>', $this->Html->image('/documents/img/link.gif')) .
    sprintf('<th class="left-align">%s</th>', __d('documents', 'Description')) .
    sprintf('<th class="right-align">%s</th>', __d('documents', 'Quantity')) .
    sprintf('<th class="left-align">%s</th>', __d('documents', 'Unit')) .
    sprintf('<th class="right-align">%s</th>', __d('documents', 'Price per Unit')) .
    sprintf('<th class="right-align nowrap">%s</th>', __d('documents', 'Discount [%]')) .
    sprintf('<th class="right-align">%s</th>', __d('documents', 'Unit Total')) .
    sprintf('<th class="right-align nowrap">%s</th>', __d('documents', 'Tax [%]')) .
    sprintf('<th class="right-align">%s</th>', __d('documents', 'Total with Tax')) .
    '<th class="center-align">&nbsp;</th>' .
    '</tr></thead>';

$analytics['itm_' . $itmIx++] = '<tbody>';

$itemsGrandTotal = 0;
$itemsTotal = 0;

// create empty item if one does not exist
$documents_items = $document->documents_items;
if (empty($documents_items)) {
    $documents_items[] = (object)[
        'id' => null, 'item_id' => null, 'vat_id' => null, 'vat_percent' => 0, 'vat_title' => '',
        'price' => null, 'discount' => null, 'qty' => null,
    ];
}

$vatOptions = [];
foreach ($vatLevels as $vat) {
    $vatOptions[$vat->id] = $vat->descript;
}

foreach ($documents_items as $k => $item) {
    $analytics['itm_' . $itmIx++] = '<tr>';

    $analytics['itm_' . $itmIx++] = '<td class="center actions">';
    $analytics['itm_' . $itmIx++] = $this->Html->image('/documents/img/link.gif', [
        'style' => $item->item_id ? '' : 'display: none;',
        'class' => 'image-item-check',
    ]);
    $analytics['itm_' . $itmIx++] = '</td>';

    $analytics['itm_' . $itmIx++] = '<td class="td-documents-item-descript">';
    $analytics['itm_' . $itmIx++] = [
        'method' => 'control',
        'parameters' => [
            'field' => 'documents_items.' . $k . '.item_id',
            ['type' => 'hidden', 'class' => 'documents-item-item_id'],
        ],
    ];
    $analytics['itm_' . $itmIx++] = [
        'method' => 'control',
        'parameters' => [
            'field' => 'documents_items.' . $k . '.document_id',
            ['type' => 'hidden', 'class' => 'documents-item-document_id'],
        ],
    ];
    $analytics['itm_' . $itmIx++] = [
        'method' => 'control',
        'parameters' => [
            'field' => 'documents_items.' . $k . '.descript', [
                'type' => 'text',
                'label' => false,
                'size' => 30,
                'class' => 'documents-item-descript',
                'autocomplete' => 'off',
                'error' => __d('documents', 'Blank'),
            ],
        ],
    ];
    $analytics['itm_' . $itmIx++] = '</td>';

    $analytics['itm_' . $itmIx++] = '<td class="td-documents-item-qty right-align">';
    $analytics['itm_' . $itmIx++] = [
        'method' => 'number',
        'parameters' => [
            'field' => 'documents_items.' . $k . '.qty', [
                'type' => 'number',
                'step' => '0.01',
                'label' => false,
                'size' => 5,
                'class' => 'documents-item-qty',
                'error' => __d('documents', 'Blank'),
                'id' => 'document-documents-items-' . $k . '-qty',
            ],
        ],
    ];
    $analytics['itm_' . $itmIx++] = '</td>';

    $analytics['itm_' . $itmIx++] = '<td class="td-documents-item-unit right-align">';
    $analytics['itm_' . $itmIx++] = [
        'method' => 'text',
        'parameters' => [
            'field' => 'documents_items.' . $k . '.unit', [
                'type' => 'text',
                'label' => false,
                'size' => 5,
                'class' => 'documents-item-unit',
                'error' => __d('documents', 'Blank'),
                'id' => 'document-documents-items-' . $k . '-unit',
            ],
        ],
    ];
    $analytics['itm_' . $itmIx++] = '</td>';

    $analytics['itm_' . $itmIx++] = '<td class="td-documents-item-price right-align">';
    $analytics['itm_' . $itmIx++] = [
        'method' => 'number',
        'parameters' => [
            'field' => 'documents_items.' . $k . '.price', [
                'type' => 'number',
                'step' => 0.01,
                'label' => false,
                'size' => 12,
                'class' => 'documents-item-price',
                'error' => __d('documents', 'Blank'),
                'id' => 'document-documents-items-' . $k . '-price',
            ],
        ],
    ];
    $analytics['itm_' . $itmIx++] = '</td>';

    $analytics['itm_' . $itmIx++] = '<td class="td-documents-item-discount right-align">';
    $analytics['itm_' . $itmIx++] = [
        'method' => 'number',
        'parameters' => [
            'field' => 'documents_items.' . $k . '.discount', [
                'type' => 'number',
                'step' => 0.1,
                'label' => false,
                'size' => 5,
                'class' => 'documents-item-discount',
                'error' => __d('documents', 'Blank'),
                'id' => 'document-documents-items-' . $k . '-discount',
            ],
        ],
    ];
    $analytics['itm_' . $itmIx++] = '</td>';

    $itemTotal = round(round($item->price * (100 - $item->discount) / 100, 4) * $item->qty, 2);
    $itemsTotal += $itemTotal;

    $analytics['itm_' . $itmIx++] = '<td class="td-documents-item-total right-align">';
    $analytics['itm_' . $itmIx++] = [
        'method' => 'control',
        'parameters' => [
            'field' => 'documents_items.' . $k . '.id',
            ['type' => 'hidden', 'class' => 'documents-item-id'],
        ],
    ];
    $analytics['itm_' . $itmIx++] = sprintf('<span>%s</span>', $this->Number->precision((float)$itemTotal, 2));
    $analytics['itm_' . $itmIx++] = '</td>';

    $analytics['itm_' . $itmIx++] = '<td class="td-documents-item-vat right-align nowrap">';
    $analytics['itm_' . $itmIx++] = [
        'method' => 'control',
        'parameters' => [
            'field' => 'documents_items.' . $k . '.vat_title',
            [
                'type' => 'hidden',
                'value' => $item->vat_title,
                'class' => 'documents-item-vat_title',
            ],
        ],
    ];
    $analytics['itm_' . $itmIx++] = [
        'method' => 'control',
        'parameters' => [
            'field' => 'documents_items.' . $k . '.vat_percent', [
                'type' => 'hidden',
                'value' => $item->vat_percent,
                'class' => 'documents-item-vat_percent',
            ],
        ],
    ];
    $analytics['itm_' . $itmIx++] = [
        'method' => 'select',
        'parameters' => [
            'field' => 'documents_items.' . $k . '.vat_id',
            $vatOptions,
            [
                'type' => 'select',
                'label' => false,
                'value' => $item->vat_id,
                'class' => 'documents-item-vat_id browser-default',
                'id' => 'document-documents-items-' . $k . '-vat-id',
                'empty' => true,
            ],
        ],
    ];

    $analytics['itm_' . $itmIx++] = '</td>';

    $lineTotal = $itemTotal + round($itemTotal * $item->vat_percent / 100, 2);
    $itemsGrandTotal += $lineTotal;

    $analytics['itm_' . $itmIx++] = '<td class="right-align td-documents-item-line-total">' .
        sprintf('<span>%s</span>', $this->Number->precision((float)$lineTotal, 2)) .
        '</td>';

    $analytics['itm_' . $itmIx++] = sprintf('<td class="center-align">%s</td>', $this->Html->link(
        $this->Html->image('/documents/img/remove.gif', ['alt' => __d('documents', 'Remove Item')]),
        [
            'plugin' => 'Documents',
            'controller' => 'documents-items',
            'action' => 'delete',
            $item->id,
        ],
        [
            'escape' => false,
            'class' => 'documents-item-remove',
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
            __d('documents', 'Add new Item'),
            'javascript:void(0);',
            ['id' => 'add-document-item-row']
        )
    );
    $analytics['itm_' . $itmIx++] = sprintf('<th colspan="3" class="right-align">%1$s:</th>', __d('documents', 'Grand Total'));
    $analytics['itm_' . $itmIx++] = sprintf('<th class="right-align" id="document-items-total">%s</th>', $this->Number->precision((float)$itemsTotal, 2));
    $analytics['itm_' . $itmIx++] = '<th class="right-align">&nbsp;</th>';
    $analytics['itm_' . $itmIx++] = sprintf('<th class="right-align" id="document-items-grand-total">%s</th>', $this->Number->precision((float)$itemsGrandTotal, 2));

    $analytics['itm_' . $itmIx++] = '<th class="left-align"></th>';
    $analytics['itm_' . $itmIx++] = '</tr></tfoot>';
    $analytics['itm_' . $itmIx++] = '</table></div>';
