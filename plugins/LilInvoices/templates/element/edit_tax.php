<?php
$taxIx = 0;
$analytics['tax_' . $taxIx++] =
    '<div class="index2">' .
    '<table cellspacing="0" cellpadding="0" id="invoice-tax-table" class="index-static">' .
    '<thead><tr>' .
    sprintf('<th class="actions center-align">%s</th>', $this->Html->image('/lil_invoices/img/link.gif')) .
    sprintf('<th class="left-align">%s</th>', __d('lil_invoices', 'VAT Description')) .
    sprintf('<th class="right-align">%s</th>', __d('lil_invoices', 'VAT [%]')) .
    sprintf('<th class="right-align">%s</th>', __d('lil_invoices', 'Base')) .
    sprintf('<th class="right-align">%s</th>', __d('lil_invoices', 'Tax')) .
    sprintf('<th class="right-align">%s</th>', __d('lil_invoices', 'Total with Tax')) .
    '<th class="center-align">&nbsp;</th>' .
    '</tr></thead>';

$analytics['tax_' . $taxIx++] = '<tbody>';

$taxGrandTotal = 0;
$taxTotal = 0;
$taxBaseTotal = 0;

$invoices_taxes = $invoice->invoices_taxes;

if (empty($invoices_taxes)) {
    $invoices_taxes[] = (object)['id' => null, 'vat_id' => null,
    'vat_percent' => 0, 'vat_title' => '', 'base' => 0];
}

foreach ($invoices_taxes as $k => $invoices_tax) {
    $tax = round($invoices_tax->base * $invoices_tax->vat_percent / 100, 2);

    $analytics['tax_' . $taxIx++] = '<tr>';

    $analytics['tax_' . $taxIx++] = '<td class="center-align actions">';
    $analytics['tax_' . $taxIx++] = $this->Html->image('/lil_invoices/img/link.gif', [
        'style' => !empty($invoices_tax->vat_id) ? '' : 'display: none;',
        'class' => 'image-tax-check',
    ]);
    $analytics['tax_' . $taxIx++] = [
        'class' => $this->Form,
        'method' => 'control',
        'parameters' => [
            'field' => sprintf('invoices_taxes.%d.id', $k),
            ['type' => 'hidden', 'class' => 'invoices-tax-id'],
        ],
    ];
    $analytics['tax_' . $taxIx++] = '</td>';

    $analytics['tax_' . $taxIx++] = '<td class="td-invoices-tax-descript">';
    $analytics['tax_' . $taxIx++] = [
        'class' => $this->Form,
        'method' => 'control',
        'parameters' => [
            'field' => sprintf('invoices_taxes.%d.vat_title', $k),
            ['type' => 'hidden', 'class' => 'invoices-tax-vat_title'],
        ],
    ];
    $analytics['tax_' . $taxIx++] = [
        'method' => 'select',
        'parameters' => [
            sprintf('invoices_taxes.%d.vat_id', $k),
            $vatLevels,
            [
                'type' => 'select',
                'label' => false,
                'value' => $invoices_tax->vat_id,
                'options' => $vatLevels,
                'class' => 'invoices-tax-vat_id browser-default',
                'id' => 'invoice-invoices-taxes-' . $k . '-vat-id',
            ],
        ],
    ];
    $analytics['tax_' . $taxIx++] = '</td>';

    $analytics['tax_' . $taxIx++] = '<td class="td-invoices-tax-vat_percent right-align">';
    $analytics['tax_' . $taxIx++] = sprintf('<span>%s</span>', $this->Number->precision($invoices_tax->vat_percent, 1));
    $analytics['tax_' . $taxIx++] = [
        'class' => $this->Form,
        'method' => 'control',
        'parameters' => [
            'field' => sprintf('invoices_taxes.%d.vat_percent', $k),
            [
                'type' => 'hidden',
                'class' => 'invoices-tax-vat_percent',
                'value' => $invoices_tax->vat_percent,
            ],
        ],
    ];
    $analytics['tax_' . $taxIx++] = '</td>';

    $analytics['tax_' . $taxIx++] = '<td class="td-invoices-tax-base right-align">';
    $analytics['tax_' . $taxIx++] = [
        'class' => $this->Form,
        'method' => 'number',
        'parameters' => [
            'field' => sprintf('invoices_taxes.%d.base', $k),
            [
                'type' => 'number',
                'step' => '0.01',
                'label' => false,
                'class' => 'invoices-tax-base right-align',
                'error' => __d('lil_invoices', 'Blank'),
                'id' => 'invoice-invoices-taxes-' . $k . '-base',
            ],
        ],
    ];
    $analytics['tax_' . $taxIx++] = '</td>';

    $analytics['tax_' . $taxIx++] = '<td class="td-invoices-tax right-align">';
    $analytics['tax_' . $taxIx++] = sprintf('<span>%s</span>', $this->Number->currency($tax));
    $analytics['tax_' . $taxIx++] = '</td>';

    $taxTotal += $tax;
    $taxBaseTotal += round($invoices_tax->base, 2);
    $line_total = $tax + round($invoices_tax->base, 2);
    $taxGrandTotal += $line_total;

    $analytics['tax_' . $taxIx++] = '<td class="td-invoices-tax-total right-align">' .
        sprintf('<span>%s</span>', $this->Number->currency($invoices_tax->base + $tax)) .
        '</td>';

    $analytics['tax_' . $taxIx++] = sprintf('<td class="center-align">%s</td>', $this->Html->link(
        $this->Html->image('/lil_invoices/img/remove.gif', ['alt' => __d('lil_invoices', 'Remove Item')]),
        [
            'plugin' => 'LilInvoices',
            'controller' => 'InvoicesTax',
            'action' => 'delete',
            $invoices_tax->id,
        ],
        ['escape' => false, 'class' => 'invoices-tax-remove']
    ));

    $analytics['tax_' . $taxIx++] = '</tr>';
}
    $analytics['tax_' . $taxIx++] = '</tbody>';

    // table FOOTER with grand total and add new row link
    $analytics['tax_' . $taxIx++] = '<tfoot><tr>';
    $analytics['tax_' . $taxIx++] = sprintf(
        '<th colspan="2">%s</th>',
        $this->Html->link(
            __d('lil_invoices', 'Add new Tax'),
            'javascript:void(0);',
            ['id' => 'add-invoice-tax-row']
        )
    );
    $analytics['tax_' . $taxIx++] = sprintf('<th class="right-align">%1$s:</th>', __d('lil_invoices', 'Grand Total'));
    $analytics['tax_' . $taxIx++] = sprintf('<th class="right-align" id="invoice-tax-base-total">%s</th>', $this->Number->currency($taxBaseTotal));
    $analytics['tax_' . $taxIx++] = sprintf('<th class="right-align" id="invoice-tax-tax-total">%s</th>', $this->Number->currency($taxTotal));
    $analytics['tax_' . $taxIx++] = sprintf('<th class="right-align" id="invoice-tax-grand-total">%s</th>', $this->Number->currency($taxGrandTotal));

    $analytics['tax_' . $taxIx++] = '<th class="left-align"></th>';
    $analytics['tax_' . $taxIx++] = '</tr></tfoot>';
    $analytics['tax_' . $taxIx++] = '</table></div>';
