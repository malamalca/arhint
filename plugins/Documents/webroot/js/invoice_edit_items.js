jQuery.fn.DocumentItemEditor = function (pOptions) {
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
            $('input.documents-item-vat_percent', row).val(options.vats[id].percent);
            $('input.documents-item-vat_title', row).val(options.vats[id].descript);
        } else {
            $('input.documents-item-vat_percent', row).val(0);
            $('input.documents-item-vat_title', row).val('');
        }

        $this.recalculateRow(row);

        $('input.documents-item-descript', row).focus();

        return false;
    }

    this.onInputsChange = function () {
        var row = $(this).closest('tr');
        $this.recalculateRow(row);
    }

    this.recalculateRow = function (row) {
        var price = $('input.documents-item-price', row).val();
        var discount = $('input.documents-item-discount', row).val();
        var qty = $('input.documents-item-qty', row).val();
        var tax = parseFloat($('input.documents-item-vat_percent', row).val());
        if (!tax) {
            tax = 0;
        }

        var itemWithDiscount = Math.round(price * (100 - discount)) / 100;
        var itemTotal = Math.round(itemWithDiscount * qty * 100) / 100;
        var taxTotal = Math.round(itemTotal * tax) / 100;

        $('td.td-documents-item-total', row).children('span').html(LilFloatFormat(itemTotal, 2));
        $('td.td-documents-item-line-total', row).children('span').html(LilFloatFormat(itemTotal + taxTotal, 2));

        $this.calculateTotalSum();
    }

    this.calculateTotalSum = function () {
        var totalSum = 0;
        var totalLineSum = 0;

        $('#document-items-table > tbody:last > tr').each(function () {
            var price = $('input.documents-item-price', this).val();
            var discount = $('input.documents-item-discount', this).val();
            var qty = $('input.documents-item-qty', this).val();
            var tax = parseFloat($('input.documents-item-vat_percent', this).val());
            if (!tax) {
                tax = 0;
            }

            var itemWithDiscount = Math.round(price * (100 - discount)) / 100;
            var itemTotal = Math.round(itemWithDiscount * qty * 100) / 100;
            var taxTotal = itemTotal + Math.round(itemTotal * tax) / 100;

            totalSum += itemTotal;
            totalLineSum += taxTotal;
        });
        $('#document-items-grand-total').html(LilFloatFormat(totalLineSum, 2));
        $('#document-items-total').html(LilFloatFormat(totalSum, 2));
    }

    this.addRow = function () {
        var rowClone = $('tbody:last > tr:first', $this).clone();
        $this.clearRow(rowClone);

        var i = 0;
        while ($('input#document-documents-items-' + i + '-id').size() > 0) {
            i++;
        }
        $this.renumberRow(rowClone, 0, i);

        $('input.documents-item-qty', rowClone).each(function () {
            $(this).blur($this.onInputsChange); });
        $('input.documents-item-price', rowClone).each(function () {
            $(this).blur($this.onInputsChange); });
        $('input.documents-item-discount', rowClone).each(function () {
            $(this).blur($this.onInputsChange); });


        //$('input.documents-item-descript', rowClone).autocomplete($this.DocumentsItemAutocomplete);

        $('select.documents-item-vat_id', rowClone).change($this.selectTax);
        $('a.documents-item-remove', rowClone).click($this.onRemoveButtonClick);
        $('tbody:last', $this).append(rowClone);

        var elem = $('input.documents-item-descript', rowClone).get(0);
        M.AutocompleteAjax.init(elem, $this.DocumentsItemAutocomplete);


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
        $('input.documents-item-id', row).val('');
        $('input.documents-item-vat_id', row).val('');
        $('input.documents-item-vat_percent', row).val('');
        $('input.documents-item-vat_title', row).val('');
        $('input.documents-item-item_id', row).val('');

        // visible fields
        $('input.documents-item-descript', row).val('');
        $('input.documents-item-qty', row).val('');
        $('input.documents-item-unit', row).val('');
        $('input.documents-item-price', row).val('');
        $('input.documents-item-discount', row).val('');

        // hide link image
        $('img.image-item-check', row).hide();

        $('td.td-documents-item-total > span', row).html('-');
        $('td.td-documents-item-line-total > span', row).html('');

        // remove action from delete row button
        $('a.documents-item-remove', row).attr('href', '#');

        $('.error-message', row).remove();
        $('.error', row).removeClass('error');
        $('.form-error', row).removeClass('form-error');
    }

    this.renumberRow = function (row, from, to) {
        $('input#document-documents-items-' + from + '-id', row).prop('name', 'documents_items[' + to + '][id]').prop('id', 'document-documents-items-' + to + '-id');
        $('input#document-documents-items-' + from + '-item-id', row).prop('name', 'documents_items[' + to + '][item_id]').prop('id', 'document-documents-items-' + to + '-item-id');
        $('input#document-documents-items-' + from + '-document-id', row).prop('name', 'documents_items[' + to + '][document_id]').prop('id', 'document-documents-items-' + to + '-document-id');

        $('select#document-documents-items-' + from + '-vat-id', row).prop('name', 'documents_items[' + to + '][vat_id]').prop('id', 'document-documents-items-' + to + '-vat-id');
        $('input#document-documents-items-' + from + '-vat-percent', row).prop('name', 'documents_items[' + to + '][vat_percent]').prop('id', 'document-documents-items-' + to + '-vat-percent');
        $('input#document-documents-items-' + from + '-vat-title', row).prop('name', 'documents_items[' + to + '][vat_title]').prop('id', 'document-documents-items-' + to + '-vat-title');

        $('input#document-documents-items-' + from + '-descript', row).prop('name', 'documents_items[' + to + '][descript]').prop('id', 'document-documents-items-' + to + '-descript');
        $('input#document-documents-items-' + from + '-qty', row).prop('name', 'documents_items[' + to + '][qty]').prop('id', 'document-documents-items-' + to + '-qty');
        $('input#document-documents-items-' + from + '-unit', row).prop('name', 'documents_items[' + to + '][unit]').prop('id', 'document-documents-items-' + to + '-unit');
        $('input#document-documents-items-' + from + '-price', row).prop('name', 'documents_items[' + to + '][price]').prop('id', 'document-documents-items-' + to + '-price');
        $('input#document-documents-items-' + from + '-discount', row).prop('name', 'documents_items[' + to + '][discount]').prop('id', 'document-documents-items-' + to + '-discount');
    }

    // initialization
    options = jQuery().extend(true, {}, default_options, pOptions);

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // AUTOCOMPLETE FUNCTIONALITY FOR ITEM DESCRIPTION
    this.DocumentsItemAutocomplete = {
        source: options.itemsAutocompleteUrl,
        onSearch: function () {
            var row = $(this).closest('tr');
            $('.documents-item-item_id', row).val('');
            $('.image-item-check', row).hide();
        },
        onSelect: function (item) {
            var row = $(this).closest('tr');
            $('.td-documents-item-descript > input:first', row).val(item.id);
            $('.image-item-check', row).show();

            $('.documents-item-qty', row).val(parseFloat(item.qty));
            $('.documents-item-unit', row).val(item.unit);
            $('.documents-item-price', row).val(parseFloat(item.price));
            $('.documents-item-discount', row).val(parseFloat(item.discount));

            $('.documents-item-vat_id', row).val(item.vat.id);
            $('.documents-item-vat_title', row).val(item.vat.descript);
            $('.documents-item-vat_percent', row).val(parseFloat(item.vat.percent));

            $this.recalculateRow(row);
        }
    };


    var elem = $('input.documents-item-descript', this).get(0);
    M.AutocompleteAjax.init(elem, $this.DocumentsItemAutocomplete);

    $('input.documents-item-qty', this).blur($this.onInputsChange);
    $('input.documents-item-price', this).blur($this.onInputsChange);
    $('input.documents-item-discount', this).blur($this.onInputsChange);
    $('a#add-document-item-row', this).click($this.addRow);
    $('a.documents-item-remove', this).click($this.onRemoveButtonClick);

    $('select.documents-item-vat_id').change($this.selectTax);
}
