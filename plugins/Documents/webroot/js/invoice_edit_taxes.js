jQuery.fn.DocumentTaxEditor = function (options) {
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

            $('input.documents-tax-vat_id', row).val(id);
            $('input.documents-tax-vat_percent', row).val(options.vats[id].percent);
            $('input.documents-tax-vat_title', row).val(options.vats[id].descript);

            $('td.td-documents-tax-vat_percent > span', row).html(LilFloatFormat(parseFloat(options.vats[id].percent), 1));
            $('span.documents-tax-display-vat', row).html(options.vats[id].descript);

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
        base = parseFloat($('input.documents-tax-base', row).val());
        percent = parseFloat($('input.documents-tax-vat_percent', row).val());

        if (isNaN(base)) {
            base = 0;
        }
        if (isNaN(percent)) {
            base = 0;
        }

        var tax = Math.round(base * percent) / 100;

        $('td.td-documents-tax', row).children('span').html(LilFloatFormat(tax, 2));
        $('td.td-documents-tax-total', row).children('span').html(LilFloatFormat(base + tax, 2));

        $this.calculateTotalTaxSum();
    }

    this.calculateTotalTaxSum = function () {
        var totalBase = 0;
        var totalTax = 0;

        $('tbody:last > tr', $this).each(function () {
            base = parseFloat($('input.documents-tax-base', this).val());
            percent = parseFloat($('input.documents-tax-vat_percent', this).val());

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
        $('th#document-tax-base-total').html(LilFloatFormat(totalBase, 2));
        $('th#document-tax-tax-total').html(LilFloatFormat(totalTax, 2));
        $('th#document-tax-grand-total').html(LilFloatFormat(totalBase + totalTax, 2));
    }

    this.addDocumentTaxRow = function () {
        var rowClone = $('tbody:last > tr:first', $this).clone();
        $this.clearDocumentTaxRow(rowClone);

        var i = 0;
        while ($('input#document-documents-taxes-' + i + '-id').size() > 0) {
            i++;
        }
        $this.renumberDocumentTaxRow(rowClone, 0, i);

        $('input.documents-tax-base', rowClone).each(function () {
            $(this).blur($this.onTaxChange); });
        $('select.documents-tax-vat_id', rowClone).change($this.selectTax);
        //$('button.documents-tax-vat-button', rowClone).click($this.onTaxButtonClick);
        $('a.documents-tax-remove', rowClone).click($this.onRemoveButtonClick);
        $('tbody:last', $this).append(rowClone);

        return false;
    }

    this.onRemoveButtonClick = function () {
        var row = $(this).closest('tr');

        if ($('tbody:last > tr', $this).size() > 1) {
            $(row).remove(); // we have to remove row so we know how many rows are actually visible
        } else {
            $this.clearDocumentTaxRow(row);
        }

        $this.calculateTotalTaxSum();

        return false;
    }

    this.clearDocumentTaxRow = function (row) {
        $('input.documents-tax-id', row).val('');
        $('input.documents-tax-vat_id', row).val('');
        $('input.documents-tax-base', row).val('');
        $('input.documents-tax-vat_percent', row).val('');
        $('input.documents-tax-vat_title', row).val('');
        $('select.documents-tax-vat_id', row).val('');

        // hide link image
        $('img.image-tax-check', row).hide();

        $('td.td-documents-tax-descript > span', row).html('-');
        $('td.td-documents-tax-vat_percent > span', row).html('');
        $('td.td-documents-tax > span', row).html('');
        $('td.td-documents-tax-total > span', row).html('');

        // remove action from delete row button
        $('a.documents-tax-remove', row).attr('href', '#');

        $('.error-message', row).remove();
        $('.error', row).removeClass('error');
        $('.form-error', row).removeClass('form-error');
    }

    this.renumberDocumentTaxRow = function (row, from, to) {
        $('input#document-documents-taxes-' + from + '-id', row).prop('name', 'documents_taxes[' + to + '][id]').prop('id', 'document-documents-taxes-' + to + '-id');
        $('select#document-documents-taxes-' + from + '-vat-id', row).prop('name', 'documents_taxes[' + to + '][vat_id]').prop('id', 'document-documents-taxes-' + to + '-vat-id');
        $('input#document-documents-taxes-' + from + '-vat-percent', row).prop('name', 'documents_taxes[' + to + '][vat_percent]').prop('id', 'document-documents-taxes-' + to + '-vat-percent');
        $('input#document-documents-taxes-' + from + '-vat-title', row).prop('name', 'documents_taxes[' + to + '][vat_title]').prop('id', 'document-documents-taxes-' + to + '-vat-title');
        $('input#document-documents-taxes-' + from + '-base', row).prop('name', 'documents_taxes[' + to + '][base]').prop('id', 'document-documents-taxes-' + to + '-base');
    }

    // initialization
    options = jQuery().extend(true, {}, default_options, options);

    $('input.documents-tax-base', this).blur($this.onTaxChange);
    $('a#add-document-tax-row', this).click($this.addDocumentTaxRow);

    $('button.documents-tax-vat-button', this).click($this.onTaxButtonClick);
    $('a.documents-tax-remove', this).click($this.onRemoveButtonClick);

    $('select.documents-tax-vat_id').change($this.selectTax);
}
