jQuery.fn.InvoiceItemEditor = function (pOptions) {
    var default_options = {
        popupId:    '#div-popup-vats',
        itemsAutocompleteUrl: '',
        vats: {}
    };
    var $this = this;
    var clickedTaxButton = null;
    var options = {};

    this.onTaxButtonClick = function () {
        clickedTaxButton = this;
        popupLinkClick(this, 'popup_vats');

        return false;
    }

    this.selectTax = function () {
        var id = $(this).val();
        var row = $(this).closest('tr');

        if (typeof options.vats[id] != 'undefined') {
            $('input.invoices-item-vat_percent', row).val(options.vats[id].percent);
            $('input.invoices-item-vat_title', row).val(options.vats[id].descript);
        } else {
            $('input.invoices-item-vat_percent', row).val(0);
            $('input.invoices-item-vat_title', row).val('');
        }

        $this.recalculateRow(row);

        return false;
    }

    this.onInputsChange = function () {
        var row = $(this).closest('tr');
        $this.recalculateRow(row);
    }

    this.recalculateRow = function (row) {
        var price = $('input.invoices-item-price', row).val();
        var discount = $('input.invoices-item-discount', row).val();
        var qty = $('input.invoices-item-qty', row).val();
        var tax = parseFloat($('input.invoices-item-vat_percent', row).val());
        if (!tax) {
            tax = 0;
        }

        var itemWithDiscount = Math.round(price * (100 - discount)) / 100;
        var itemTotal = Math.round(itemWithDiscount * qty * 100) / 100;
        var taxTotal = Math.round(itemTotal * tax) / 100;

        $('td.td-invoices-item-total', row).children('span').html(LilFloatFormat(itemTotal, 2));
        $('td.td-invoices-item-line-total', row).children('span').html(LilFloatFormat(itemTotal + taxTotal, 2));

        $this.calculateTotalSum();
    }

    this.calculateTotalSum = function () {
        var totalSum = 0;
        var totalLineSum = 0;

        $('#invoice-items-table > tbody:last > tr').each(function () {
            var price = $('input.invoices-item-price', this).val();
            var discount = $('input.invoices-item-discount', this).val();
            var qty = $('input.invoices-item-qty', this).val();
            var tax = parseFloat($('input.invoices-item-vat_percent', this).val());
            if (!tax) {
                tax = 0;
            }

            var itemWithDiscount = Math.round(price * (100 - discount)) / 100;
            var itemTotal = Math.round(itemWithDiscount * qty * 100) / 100;
            var taxTotal = itemTotal + Math.round(itemTotal * tax) / 100;

            totalSum += itemTotal;
            totalLineSum += taxTotal;
        });
        $('#invoice-items-grand-total').html(LilFloatFormat(totalLineSum, 2));
        $('#invoice-items-total').html(LilFloatFormat(totalSum, 2));
    }

    this.addRow = function () {
        var rowClone = $('tbody:last > tr:first', $this).clone();
        $this.clearRow(rowClone);

        var i = 0;
        while ($('input#invoice-invoices-items-' + i + '-id').size() > 0) {
            i++;
        }
        $this.renumberRow(rowClone, 0, i);

        $('input.invoices-item-qty', rowClone).each(function () {
            $(this).blur($this.onInputsChange); });
        $('input.invoices-item-price', rowClone).each(function () {
            $(this).blur($this.onInputsChange); });
        $('input.invoices-item-discount', rowClone).each(function () {
            $(this).blur($this.onInputsChange); });


        //$('input.invoices-item-descript', rowClone).autocomplete($this.invoicesItemAutocomplete);

        $('select.invoices-item-vat_id', rowClone).change($this.selectTax);
        $('a.invoices-item-remove', rowClone).click($this.onRemoveButtonClick);
        $('tbody:last', $this).append(rowClone);

        var elem = $('input.invoices-item-descript', rowClone).get(0);
        M.AutocompleteAjax.init(elem, $this.invoicesItemAutocomplete);


        return false;
    }

    this.onRemoveButtonClick = function () {
        var row = $(this).closest('tr');

        if ($('tbody:last > tr', $this).size() > 1) {
            $(row).remove(); // we have to remove row so we know how many rows are actually visible
        } else {
            $this.clearRow(row);
        }

        $this.calculateTotalSum();

        return false;
    }

    this.clearRow = function (row) {
        // hidden fields
        $('input.invoices-item-id', row).val('');
        $('input.invoices-item-vat_id', row).val('');
        $('input.invoices-item-vat_percent', row).val('');
        $('input.invoices-item-vat_title', row).val('');
        $('input.invoices-item-item_id', row).val('');

        // visible fields
        $('input.invoices-item-descript', row).val('');
        $('input.invoices-item-qty', row).val('');
        $('input.invoices-item-unit', row).val('');
        $('input.invoices-item-price', row).val('');
        $('input.invoices-item-discount', row).val('');

        // hide link image
        $('img.image-item-check', row).hide();

        $('td.td-invoices-item-total > span', row).html('-');
        $('td.td-invoices-item-line-total > span', row).html('');

        // remove action from delete row button
        $('a.invoices-item-remove', row).attr('href', '#');

        $('.error-message', row).remove();
        $('.error', row).removeClass('error');
        $('.form-error', row).removeClass('form-error');
    }

    this.renumberRow = function (row, from, to) {
        $('input#invoice-invoices-items-' + from + '-id', row).prop('name', 'invoices_items[' + to + '][id]').prop('id', 'invoice-invoices-items-' + to + '-id');
        $('input#invoice-invoices-items-' + from + '-item-id', row).prop('name', 'invoices_items[' + to + '][item_id]').prop('id', 'invoice-invoices-items-' + to + '-item-id');
        $('input#invoice-invoices-items-' + from + '-vat-id', row).prop('name', 'invoices_items[' + to + '][vat_id]').prop('id', 'invoice-invoices-items-' + to + '-vat-id');
        $('input#invoice-invoices-items-' + from + '-vat-percent', row).prop('name', 'invoices_items[' + to + '][vat_percent]').prop('id', 'invoice-invoices-items-' + to + '-vat-percent');
        $('input#invoice-invoices-items-' + from + '-vat-title', row).prop('name', 'invoices_items[' + to + '][vat_title]').prop('id', 'invoice-invoices-items-' + to + '-vat-title');

        $('input#invoice-invoices-items-' + from + '-descript', row).prop('name', 'invoices_items[' + to + '][descript]').prop('id', 'invoice-invoices-items-' + to + '-descript');
        $('input#invoice-invoices-items-' + from + '-qty', row).prop('name', 'invoices_items[' + to + '][qty]').prop('id', 'invoice-invoices-items-' + to + '-qty');
        $('input#invoice-invoices-items-' + from + '-unit', row).prop('name', 'invoices_items[' + to + '][unit]').prop('id', 'invoice-invoices-items-' + to + '-unit');
        $('input#invoice-invoices-items-' + from + '-price', row).prop('name', 'invoices_items[' + to + '][price]').prop('id', 'invoice-invoices-items-' + to + '-price');
        $('input#invoice-invoices-items-' + from + '-discount', row).prop('name', 'invoices_items[' + to + '][discount]').prop('id', 'invoice-invoices-items-' + to + '-discount');
    }

    // initialization
    options = jQuery().extend(true, {}, default_options, pOptions);

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // AUTOCOMPLETE FUNCTIONALITY FOR ITEM DESCRIPTION
    this.invoicesItemAutocomplete = {
        source: options.itemsAutocompleteUrl,
        onSearch: function () {
            var row = $(this).closest('tr');
            $('.invoices-item-item_id', row).val('');
            $('.image-item-check', row).hide();
        },
        onSelect: function (item) {
            var row = $(this).closest('tr');
            $('.td-invoices-item-descript > input:first', row).val(item.id);
            $('.image-item-check', row).show();

            $('.invoices-item-qty', row).val(parseFloat(item.qty));
            $('.invoices-item-unit', row).val(item.unit);
            $('.invoices-item-price', row).val(parseFloat(item.price));
            $('.invoices-item-discount', row).val(parseFloat(item.discount));

            $('.invoices-item-vat_id', row).val(item.vat.id);
            $('.invoices-item-vat_title', row).val(item.vat.descript);
            $('.invoices-item-vat_percent', row).val(parseFloat(item.vat.percent));

            $this.recalculateRow(row);
        }
    };


    var elem = $('input.invoices-item-descript', this).get(0);
    M.AutocompleteAjax.init(elem, $this.invoicesItemAutocomplete);
    //$('input.invoices-item-descript', this).autocomplete(this.invoicesItemAutocomplete);

    $('input.invoices-item-qty', this).blur($this.onInputsChange);
    $('input.invoices-item-price', this).blur($this.onInputsChange);
    $('input.invoices-item-discount', this).blur($this.onInputsChange);
    $('a#add-invoice-item-row', this).click($this.addRow);
    $('a.invoices-item-remove', this).click($this.onRemoveButtonClick);

    $('select.invoices-item-vat_id').change($this.selectTax);
}
