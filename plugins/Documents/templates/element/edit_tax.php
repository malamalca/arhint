<?php
$taxIx = 0;
$analytics['tax_' . $taxIx++] =
    '<div class="index2">' .
    '<table cellspacing="0" cellpadding="0" id="document-tax-table" class="index-static">' .
    '<thead><tr>' .
    sprintf('<th class="actions center-align">%s</th>', $this->Html->image('/documents/img/link.gif')) .
    sprintf('<th class="left-align">%s</th>', __d('documents', 'VAT Description')) .
    sprintf('<th class="right-align">%s</th>', __d('documents', 'VAT [%]')) .
    sprintf('<th class="right-align">%s</th>', __d('documents', 'Base')) .
    sprintf('<th class="right-align">%s</th>', __d('documents', 'Tax')) .
    sprintf('<th class="right-align">%s</th>', __d('documents', 'Total with Tax')) .
    '<th class="center-align">&nbsp;</th>' .
    '</tr></thead>';

$analytics['tax_' . $taxIx++] = '<tbody>';

$taxGrandTotal = 0;
$taxTotal = 0;
$taxBaseTotal = 0;

$documents_taxes = $document->documents_taxes;

if (empty($documents_taxes)) {
    $documents_taxes[] = (object)['id' => null, 'vat_id' => null,
    'vat_percent' => 0, 'vat_title' => '', 'base' => 0];
}

foreach ($documents_taxes as $k => $documents_tax) {
    $tax = round($documents_tax->base * $documents_tax->vat_percent / 100, 2);

    $analytics['tax_' . $taxIx++] = '<tr>';

    $analytics['tax_' . $taxIx++] = '<td class="center-align actions">';
    $analytics['tax_' . $taxIx++] = $this->Html->image('/documents/img/link.gif', [
        'style' => !empty($documents_tax->vat_id) ? '' : 'display: none;',
        'class' => 'image-tax-check',
    ]);
    $analytics['tax_' . $taxIx++] = [
        'class' => $this->Form,
        'method' => 'control',
        'parameters' => [
            'field' => sprintf('documents_taxes.%d.id', $k),
            ['type' => 'hidden', 'class' => 'documents-tax-id'],
        ],
    ];
    $analytics['tax_' . $taxIx++] = '</td>';

    $analytics['tax_' . $taxIx++] = '<td class="td-documents-tax-descript">';
    $analytics['tax_' . $taxIx++] = [
        'class' => $this->Form,
        'method' => 'control',
        'parameters' => [
            'field' => sprintf('documents_taxes.%d.vat_title', $k),
            ['type' => 'hidden', 'class' => 'documents-tax-vat_title'],
        ],
    ];
    $analytics['tax_' . $taxIx++] = [
        'method' => 'select',
        'parameters' => [
            sprintf('documents_taxes.%d.vat_id', $k),
            $vatLevels,
            [
                'type' => 'select',
                'label' => false,
                'value' => $documents_tax->vat_id,
                'options' => $vatLevels,
                'class' => 'documents-tax-vat_id browser-default',
                'id' => 'document-documents-taxes-' . $k . '-vat-id',
            ],
        ],
    ];
    $analytics['tax_' . $taxIx++] = '</td>';

    $analytics['tax_' . $taxIx++] = '<td class="td-documents-tax-vat_percent right-align">';
    $analytics['tax_' . $taxIx++] = sprintf('<span>%s</span>', $this->Number->precision((float)$documents_tax->vat_percent, 1));
    $analytics['tax_' . $taxIx++] = [
        'class' => $this->Form,
        'method' => 'control',
        'parameters' => [
            'field' => sprintf('documents_taxes.%d.vat_percent', $k),
            [
                'type' => 'hidden',
                'class' => 'documents-tax-vat_percent',
                'value' => $documents_tax->vat_percent,
            ],
        ],
    ];
    $analytics['tax_' . $taxIx++] = '</td>';

    $analytics['tax_' . $taxIx++] = '<td class="td-documents-tax-base right-align">';
    $analytics['tax_' . $taxIx++] = [
        'class' => $this->Form,
        'method' => 'number',
        'parameters' => [
            'field' => sprintf('documents_taxes.%d.base', $k),
            [
                'type' => 'number',
                'step' => '0.01',
                'label' => false,
                'class' => 'documents-tax-base right-align',
                'error' => __d('documents', 'Blank'),
                'id' => 'document-documents-taxes-' . $k . '-base',
            ],
        ],
    ];
    $analytics['tax_' . $taxIx++] = '</td>';

    $analytics['tax_' . $taxIx++] = '<td class="td-documents-tax right-align">';
    $analytics['tax_' . $taxIx++] = sprintf('<span>%s</span>', $this->Number->currency($tax));
    $analytics['tax_' . $taxIx++] = '</td>';

    $taxTotal += $tax;
    $taxBaseTotal += round($documents_tax->base, 2);
    $line_total = $tax + round($documents_tax->base, 2);
    $taxGrandTotal += $line_total;

    $analytics['tax_' . $taxIx++] = '<td class="td-documents-tax-total right-align">' .
        sprintf('<span>%s</span>', $this->Number->currency($documents_tax->base + $tax)) .
        '</td>';

    $analytics['tax_' . $taxIx++] = sprintf('<td class="center-align">%s</td>', $this->Html->link(
        $this->Html->image('/documents/img/remove.gif', ['alt' => __d('documents', 'Remove Item')]),
        [
            'plugin' => 'Documents',
            'controller' => 'DocumentsTax',
            'action' => 'delete',
            $documents_tax->id,
        ],
        ['escape' => false, 'class' => 'documents-tax-remove']
    ));

    $analytics['tax_' . $taxIx++] = '</tr>';
}
    $analytics['tax_' . $taxIx++] = '</tbody>';

    // table FOOTER with grand total and add new row link
    $analytics['tax_' . $taxIx++] = '<tfoot><tr>';
    $analytics['tax_' . $taxIx++] = sprintf(
        '<th colspan="2">%s</th>',
        $this->Html->link(
            __d('documents', 'Add new Tax'),
            'javascript:void(0);',
            ['id' => 'add-document-tax-row']
        )
    );
    $analytics['tax_' . $taxIx++] = sprintf('<th class="right-align">%1$s:</th>', __d('documents', 'Grand Total'));
    $analytics['tax_' . $taxIx++] = sprintf('<th class="right-align" id="document-tax-base-total">%s</th>', $this->Number->currency($taxBaseTotal));
    $analytics['tax_' . $taxIx++] = sprintf('<th class="right-align" id="document-tax-tax-total">%s</th>', $this->Number->currency($taxTotal));
    $analytics['tax_' . $taxIx++] = sprintf('<th class="right-align" id="document-tax-grand-total">%s</th>', $this->Number->currency($taxGrandTotal));

    $analytics['tax_' . $taxIx++] = '<th class="left-align"></th>';
    $analytics['tax_' . $taxIx++] = '</tr></tfoot>';
    $analytics['tax_' . $taxIx++] = '</table></div>';
