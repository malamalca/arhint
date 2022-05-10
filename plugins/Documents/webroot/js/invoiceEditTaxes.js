jQuery.fn.InvoiceTaxEditor = function (options) {
    var default_options = {
        popupId:    'div.popup_vats',
        vats: {}
    };
    var $this = this;
    var clickedTaxButton = null;

    this.onTaxButtonClick = function (e) {
        clickedTaxButton = this;
        popupLinkClick(this, 'popup_vats');

        return false;
    }

    this.selectTax = function () {
        var id = $(this).val();
        if (typeof options.vats[id] != 'undefined') {
            var row = $(this).closest('tr');

            $('input.invoices-tax-vat_id', row).val(id);
            $('input.invoices-tax-vat_percent', row).val(options.vats[id].percent);
            $('input.invoices-tax-vat_title', row).val(options.vats[id].descript);

            $('td.td-invoices-tax-vat_percent > span', row).html(LilFloatFormat(parseFloat(options.vats[id].percent), 1));
            $('span.invoices-tax-display-vat', row).html(options.vats[id].descript);

            $('img.image-tax-check', row).show();

            $this.calculateTaxRow(row);
        }

        return false;
    }

    this.onTaxChange = function () {
        var row = $(this).closest('tr');
        $this.calculateTaxRow(row);
    }

    this.calculateTaxRow = function (row) {
        base = parseFloat($('input.invoices-tax-base', row).val());
        percent = parseFloat($('input.invoices-tax-vat_percent', row).val());

        if (isNaN(base)) {
            base = 0;
        }
        if (isNaN(percent)) {
            base = 0;
        }

        var tax = Math.round(base * percent) / 100;

        $('td.td-invoices-tax', row).children('span').html(LilFloatFormat(tax, 2));
        $('td.td-invoices-tax-total', row).children('span').html(LilFloatFormat(base + tax, 2));

        $this.calculateTotalTaxSum();
    }

    this.calculateTotalTaxSum = function () {
        var totalBase = 0;
        var totalTax = 0;

        $('tbody:last > tr', $this).each(function () {
            base = parseFloat($('input.invoices-tax-base', this).val());
            percent = parseFloat($('input.invoices-tax-vat_percent', this).val());

            if (isNaN(base)) {
                base = 0;
            }
            if (isNaN(percent)) {
                base = 0;
            }

            var tax = Math.round(base * percent) / 100;

            totalTax += tax;
            totalBase += base;
        });
        $('th#invoice-tax-base-total').html(LilFloatFormat(totalBase, 2));
        $('th#invoice-tax-tax-total').html(LilFloatFormat(totalTax, 2));
        $('th#invoice-tax-grand-total').html(LilFloatFormat(totalBase + totalTax, 2));
    }

    this.addInvoiceTaxRow = function () {
        var rowClone = $('tbody:last > tr:first', $this).clone();
        $this.clearInvoiceTaxRow(rowClone);

        var i = 0;
        while ($('input#invoice-invoices-taxes-' + i + '-id').length > 0) {
            i++;
        }
        $this.renumberInvoiceTaxRow(rowClone, 0, i);

        $('input.invoices-tax-base', rowClone).each(function () {
            $(this).blur($this.onTaxChange); });
        $('select.invoices-tax-vat_id', rowClone).change($this.selectTax);
        //$('button.invoices-tax-vat-button', rowClone).click($this.onTaxButtonClick);
        $('a.invoices-tax-remove', rowClone).click($this.onRemoveButtonClick);
        $('tbody:last', $this).append(rowClone);

        return false;
    }

    this.onRemoveButtonClick = function () {
        var row = $(this).closest('tr');

        if ($('tbody:last > tr', $this).length > 1) {
            $(row).remove(); // we have to remove row so we know how many rows are actually visible
        } else {
            $this.clearInvoiceTaxRow(row);
        }

        $this.calculateTotalTaxSum();

        return false;
    }

    this.clearInvoiceTaxRow = function (row) {
        $('input.invoices-tax-id', row).val('');
        $('input.invoices-tax-vat_id', row).val('');
        $('input.invoices-tax-base', row).val('');
        $('input.invoices-tax-vat_percent', row).val('');
        $('input.invoices-tax-vat_title', row).val('');
        $('select.invoices-tax-vat_id', row).val('');

        // hide link image
        $('img.image-tax-check', row).hide();

        $('td.td-invoices-tax-descript > span', row).html('-');
        $('td.td-invoices-tax-vat_percent > span', row).html('');
        $('td.td-invoices-tax > span', row).html('');
        $('td.td-invoices-tax-total > span', row).html('');

        // remove action from delete row button
        $('a.invoices-tax-remove', row).attr('href', '#');

        $('.error-message', row).remove();
        $('.error', row).removeClass('error');
        $('.form-error', row).removeClass('form-error');
    }

    this.renumberInvoiceTaxRow = function (row, from, to) {
        $('input#invoice-invoices-taxes-' + from + '-id', row).prop('name', 'invoices_taxes[' + to + '][id]').prop('id', 'invoice-invoices-taxes-' + to + '-id');
        $('select#invoice-invoices-taxes-' + from + '-vat-id', row).prop('name', 'invoices_taxes[' + to + '][vat_id]').prop('id', 'invoice-invoices-taxes-' + to + '-vat-id');
        $('input#invoice-invoices-taxes-' + from + '-vat-percent', row).prop('name', 'invoices_taxes[' + to + '][vat_percent]').prop('id', 'invoice-invoices-taxes-' + to + '-vat-percent');
        $('input#invoice-invoices-taxes-' + from + '-vat-title', row).prop('name', 'invoices_taxes[' + to + '][vat_title]').prop('id', 'invoice-invoices-taxes-' + to + '-vat-title');
        $('input#invoice-invoices-taxes-' + from + '-base', row).prop('name', 'invoices_taxes[' + to + '][base]').prop('id', 'invoice-invoices-taxes-' + to + '-base');
    }

    // initialization
    options = jQuery().extend(true, {}, default_options, options);

    $('input.invoices-tax-base', this).blur($this.onTaxChange);
    $('a#add-invoices-tax-row', this).click($this.addInvoiceTaxRow);

    $('button.invoices-tax-vat-button', this).click($this.onTaxButtonClick);
    $('a.invoices-tax-remove', this).click($this.onRemoveButtonClick);

    $('select.invoices-tax-vat_id').change($this.selectTax);
}
