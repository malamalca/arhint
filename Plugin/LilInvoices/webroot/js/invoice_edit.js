var invoicesItemAutocomplete = {};
var invoicesItemKeyup = function() {};
var invoicesItemLinkClick = function() {};

var popupContacts = null;
var popupClientUrl = null;

$(document).ready(function() {
	// AUTOCOMPLETE FUNCTIONALITY FOR ITEM DESCRIPTION
	invoicesItemAutocomplete = {
		source: itemAutocompleteUrl, 
		search: function() {
			var row = $(this).closest('tr');
			$('.invoices-item-item_id', row).val('');
			$('.image-item-check', row).hide();
		},
		select: function(event, ui) {
			if (ui.item) {
				var row = $(this).closest('tr');
				$('.td-invoices-item-descript > input:first', row).val(ui.item.id);
				$('.image-item-check', row).show();
				
				$('.invoices-item-qty', row).val(LilFloatFormat(parseFloat(ui.item.qty), 2));
				$('.invoices-item-unit', row).val(ui.item.unit);
				$('.invoices-item-price', row).val(LilFloatFormat(parseFloat(ui.item.price), 2));
				$('.invoices-item-tax', row).val(LilFloatFormat(parseFloat(ui.item.tax), 1));
				
				calculateRow(row);
			}
		}
	};
	
	applyDates = function(dateText, inst) {
		if ($('#InvoiceDatService').val() == "") $('#InvoiceDatService').val(dateText);
		if ($('#InvoiceDatExpire').val() == "") $('#InvoiceDatExpire').val(dateText);
	}
	
	invoicesItemKeyup = function() {
		if ($(this).val() === "") {
			var row = $(this).closest('tr');
			$('.td-invoices-item-descript > input:first', row).val('');
			$('.image-item-check', row).hide();
		}
	};
	
	invoicesItemLinkClick = function() {
		if (confirm(toggleUnlinkItemConfirmation)) {
			var row = $(this).closest('tr');
			$('.td-invoices-item-descript > input:first', row).val('');
			
			$('.image-item-check', row).hide();
		}
	}
	$('.invoices-item-descript').autocomplete(invoicesItemAutocomplete).keyup(invoicesItemKeyup);
	$('.image-item-check').each(function() { $(this).click(invoicesItemLinkClick); });
	
	// WATCH FUNCTIONALITY FOR QTY AND PRICE FIELDS
	$('.invoices-item-price').each(function() { $(this).blur(onNumbersChange); } );
	$('.invoices-item-qty').each(function() { $(this).blur(onNumbersChange);  } );
	$('.invoices-item-tax').each(function() { $(this).blur(onNumbersChange);  } );
	
	contactsFormSubmit = function(event) {
		event.preventDefault();
		$.post(
			popupClientUrl,
			$('form', popupContacts).serialize(),
			function(data) {
				$(popupContacts).html(data);
				$('form', popupContacts).submit(contactsFormSubmit);
			}
		);
		return false;
	} 
});

function openAddContactForm(kind) {
	$("#dialog-form").dialog({
		title: (kind == "T") ? popupPersonTitle : popupCompanyTitle,
		autoOpen: true,
		height: 550,
		width: 440,
		modal: true,
		open: function(event, ui) {
			popupClientUrl = (kind == "T") ? popupPersonUrl : popupCompanyUrl;
			$(this).html('');
			$(this).load(popupClientUrl, function() {
				popupContacts = $(this);
				$('form', popupContacts).submit(contactsFormSubmit);
			});
		}
	});
};

var onNumbersChange = function() {
	var row = $(this).closest('tr');
	calculateRow(row);
};

function calculateRow(row) {
	var price = $('input.invoices-item-price', row);
	var itemPrice = $(price).LilFloatVal();
	
	var qty = $('input.invoices-item-qty', row);
	var itemQty = $(qty).LilFloatVal();
	
	var tax = $('input.invoices-item-tax', row);
	var itemTax = $(tax).LilFloatVal();
	
	var itemTotal = Math.round(itemPrice * itemQty * 100) / 100;
	var taxTotal = Math.round(itemPrice * itemQty * itemTax) / 100;
 	
	$('td.invoices-item-total', row).children('span').html(LilFloatFormat(itemTotal, 2));
	$('td.invoices-line-total', row).children('span').html(LilFloatFormat(itemTotal + taxTotal, 2));
	
	calculateTotalSum();
}

function calculateTotalSum() {
	var totalSum = 0;
	var totalLineSum = 0;
	$('#invoice-analytics-table > tbody:last > tr').each(function(){
		price = $('input.invoices-item-price', this).LilFloatVal();
		itemPrice = $('input.invoices-item-qty', this).LilFloatVal();
		itemTax = $('input.invoices-item-tax', this).LilFloatVal();
		
		totalSum += Math.round(price * itemPrice * 100) / 100;
		totalLineSum += (Math.round(price * itemPrice * 100) / 100 + Math.round(price * itemPrice * itemTax) / 100);
	});
	$('#invoice-analytics-grand-total').html(LilFloatFormat(totalLineSum, 2));
	$('#invoice-analytics-items-total').html(LilFloatFormat(totalSum, 2));
}
		
function addInvoiceAnalyticsRow() {
	var rowClone = $('#invoice-analytics-table > tbody:last > tr:first').clone();

	clearInvoiceAnalyticsRow(rowClone);
	
	var i = 0;
	while ($('#InvoicesItem' + i + 'Id').size() > 0) i++;
	renumberInvoiceAnalyticsRow(rowClone, 0, i);
	
	$('.invoices-item-price', rowClone).each(function() { $(this).LilFloat({'empty': true}); $(this).blur(onNumbersChange); });
	$('.invoices-item-qty', rowClone).each(function() { $(this).LilFloat({'empty': true, 'places': 1}); $(this).blur(onNumbersChange); });
	$('.invoices-item-tax', rowClone).each(function() { $(this).LilFloat({'empty': true, 'places': 1}); $(this).blur(onNumbersChange); });
	
	$('.invoices-item-descript', rowClone).each(function() { $(this).autocomplete(invoicesItemAutocomplete).keyup(invoicesItemKeyup); });
	$('.image-item-check', rowClone).each(function() { $(this).click(invoicesItemLinkClick); });
	
	$('#invoice-analytics-table > tbody:last').append(rowClone);
}

function removeInvoiceAnalyticsRow(btn) {
	var row = $(btn).closest('tr');
	var id = $('.invoices-item-total > input:first', row);
	
	if ($(id).val() !== '') {
		// add this item do deleted items list
		var del_list = $('<input/>')
			.attr({
				name: 'data[Invoice][items_to_delete][]'
			})
			.val($(id).val());
		$(del_list).insertAfter('#InvoiceKind');
	}

	if ($('#invoice-analytics-table > tbody:last > tr').size() > 1) {
		$(row).remove(); // we have to remove row so we know how many rows are actually visible
	} else {
		clearInvoiceAnalyticsRow(row);
	}
	
	calculateTotalSum();
	return false;
}

function clearInvoiceAnalyticsRow(row) {
	$('.invoices-item-descript', row).val('');
	$('.invoices-item-qty', row).val('');
	
	$('.invoices-item-unit', row).val('');
	$('.invoices-item-price', row).val('');
	$('.invoices-item-tax', row).val('');
	
	// hidden item id
	$('.td-invoices-item-descript > input:first', row).val('');
	
	// hide link image
	$('.image-item-check', row).hide();
	
	// do nothing with Invoice.id field
	// but empty InvoiceItem.id field
	$('.invoices-item-total > input:first', row).val('');
	
	// this is a td cell with price*amount
	$('.invoices-item-total > span', row).html('');
	$('.invoices-line-total > span', row).html('');
	
	// remove action from button
	$('.invoices-item-remove', row).attr('href', '#').click(function(){return false;});
	
	$('.error-message', row).remove();
	$('.error', row).removeClass('error');
	$('.form-error', row).removeClass('form-error');
}

function renumberInvoiceAnalyticsRow(row, from, to) {
	$('#InvoicesItem' + from + 'Id', row).attr('name', 'data[InvoicesItem][' + to + '][id]').attr('id', 'InvoicesItem' + to + 'Id');
	$('#InvoicesItem' + from + 'ItemId', row).attr('name', 'data[InvoicesItem][' + to + '][item_id]').attr('id', 'InvoicesItem' + to + 'ItemId');
	$('#InvoicesItem' + from + 'InvoiceId', row).attr('name', 'data[InvoicesItem][' + to + '][invoice_id]').attr('id', 'InvoicesItem' + to + 'InvoiceId');
	$('#InvoicesItem' + from + 'Descript', row).attr('name', 'data[InvoicesItem][' + to + '][descript]').attr('id', 'InvoicesItem' + to + 'Descript');
	$('#InvoicesItem' + from + 'Qty', row).attr('name', 'data[InvoicesItem][' + to + '][qty]').attr('id', 'InvoicesItem' + to + 'Qty');
	$('#InvoicesItem' + from + 'Unit', row).attr('name', 'data[InvoicesItem][' + to + '][unit]').attr('id', 'InvoicesItem' + to + 'Unit');
	$('#InvoicesItem' + from + 'Price', row).attr('name', 'data[InvoicesItem][' + to + '][price]').attr('id', 'InvoicesItem' + to + 'Price');
	$('#InvoicesItem' + from + 'Tax', row).attr('name', 'data[InvoicesItem][' + to + '][tax]').attr('id', 'InvoicesItem' + to + 'Tax');
}