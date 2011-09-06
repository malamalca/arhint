$(document).ready(function() {
	// WATCH FUNCTIONALITY FOR ADVANCE
	$('#TravelOrderAdvance').LilFloat({empty: true});
	
	// WATCH FUNCTIONALITY FOR NUMERIC
	$('.travel-orders-item-workdays').each(function() { $(this).LilFloat({places:0, empty:true}); $(this).blur(onTravelOrdersItemsNumbersChange); });
	$('.travel-orders-item-workday_price').each(function() { $(this).LilFloat({empty:true}); $(this).blur(onTravelOrdersItemsNumbersChange); });
	$('.travel-orders-item-km').each(function() { $(this).LilFloat({empty:true}); $(this).blur(onTravelOrdersItemsNumbersChange); });
	$('.travel-orders-item-km_price').each(function() { $(this).LilFloat({empty:true}); $(this).blur(onTravelOrdersItemsNumbersChange); });
	$('.travel-orders-expense-price').each(function() { $(this).LilFloat({empty:true}); $(this).blur(onTravelOrdersExpensesNumbersChange); });
	
	// DATE PICKER
	$('.travel-orders-item-dat_travel').datepicker({dateFormat: "dd.mm."});
	$('.travel-orders-expense-dat_expense').datepicker({dateFormat: "dd.mm."});
	
	// AJAX CHANGE OF COUNTER
	$('#TravelOrderCounterId').change(function() {
		$('#TravelOrderCounterIdLoader').parent().show();
		$.ajax({
			url: counterUrl.replace(/\%d/, $(this).val()),
			dataType: 'json',
			success:  function(data) {
				$('#TravelOrderNo').val(data.no);
				$('#TravelOrderCounter').val(data.InvoicesCounter.counter);
				
				// change attributes of 'no'
				if (!isUserAdmin) {
					if (data.InvoicesCounter.mask.trim() === '') {
						$('#TravelOrderNo').attr('disabled', '');
					} else {
						$('#TravelOrderNo').attr('disabled', 'disabled');
					}
				}
			},
			complete: function(data, status) {
				if (status != 'success') alert('Call Failed');
				$('#TravelOrderCounterIdLoader').parent().hide();
			}
		});
	});
});

var onTravelOrdersItemsNumbersChange = function() {
	var row = $(this).closest('tr');
	calculateTravelOrdersItemsRow($(row));
};

function calculateTravelOrdersItemsRow(row) {
	var workdays = $('input.travel-orders-item-workdays', row).LilFloatVal();
	var workdayPrice = $('input.travel-orders-item-workday_price', row).LilFloatVal();
	
	var km = $('input.travel-orders-item-km', row).LilFloatVal();
	var kmPrice = $('input.travel-orders-item-km_price', row).LilFloatVal();
	
	var workdayTotal = Math.round(workdays * workdayPrice * 100) / 100;
	var kmTotal = Math.round(km * kmPrice * 100) / 100;
	
	
	$('td.td-travel-orders-item-workday-total', row).html(LilFloatFormat(workdayTotal, 2));
	$('td.td-travel-orders-item-km-total', row).html(LilFloatFormat(kmTotal, 2));
	
	calculateTravelOrdersItemsTotalSum();
}

function calculateTravelOrdersItemsTotalSum() {
	var totalWorkdaySum = 0;
	var totalKmSum = 0;
	var workdays = 0;
	var workdayPrice = 0;
	var km = 0;
	var kmPrice = 0;
	
	$('#travel-orders-analytics-table > tbody:last > tr').each(function(){
		workdays = $('input.travel-orders-item-workdays', this).LilFloatVal();
		workdayPrice = $('input.travel-orders-item-workday_price', this).LilFloatVal();
	
		km = $('input.travel-orders-item-km', this).LilFloatVal();
		kmPrice = $('input.travel-orders-item-km_price', this).LilFloatVal();
		
		totalWorkdaySum += Math.round(workdays * workdayPrice * 100) / 100;
		totalKmSum += Math.round(km * kmPrice * 100) / 100;
	});
	$('#travel-orders-analytics-workdays-total').html(LilFloatFormat(totalWorkdaySum, 2));
	$('#travel-orders-analytics-kms-total').html(LilFloatFormat(totalKmSum, 2));
}
		
function addTravelOrdersItemsRow() {
	// must destroy all datepickers, because we should not copy datepickers dom
	$('.travel-orders-item-dat_travel').each(function() {$(this).datepicker('destroy')});
	
	var rowClone = $('#travel-orders-analytics-table > tbody:last > tr:first').clone();

	clearTravelOrdersItemsRow(rowClone);
	
	var i = 0;
	while ($('#TravelOrdersItem' + i + 'Id').size() > 0) i++;
	renumberTravelOrdersItemsRow(rowClone, 0, i);
	
	$('.travel-orders-item-workdays', rowClone).each(function() { $(this).LilFloat({places:0, empty:true}); $(this).blur(onTravelOrdersItemsNumbersChange); });
	$('.travel-orders-item-workday_price', rowClone).each(function() { $(this).LilFloat({empty:true}); $(this).blur(onTravelOrdersItemsNumbersChange); });
	$('.travel-orders-item-km', rowClone).each(function() { $(this).LilFloat({empty:true}); $(this).blur(onTravelOrdersItemsNumbersChange); });
	$('.travel-orders-item-km_price', rowClone).each(function() { $(this).LilFloat({empty:true}); $(this).blur(onTravelOrdersItemsNumbersChange); });
	
	$('#travel-orders-analytics-table > tbody:last').append(rowClone);
	
	// reenable all detapickers
	$('.travel-orders-item-dat_travel').each(function() {$(this).datepicker({dateFormat: "dd.mm."})});
}

function removeTravelOrdersItemsRow(btn) {
	var row = $(btn).closest('tr');
	var id = $('.travel-orders-item-id > input:first', row);
	
	if ($(id).val() !== '') {
		// add this item do deleted items list
		var del_list = $('<input/>')
			.attr({
				name: 'data[TravelOrder][items_to_delete][]'
			})
			.val($(id).val());
		$(del_list).insertAfter($('#TravelOrderId'));
	}

	if ($('#travel-orders-analytics-table > tbody:last > tr').size() > 1) {
		$(row).remove(); // we have to remove row so we know how many rows are actually visible
	} else {
		clearTravelOrdersItemsRow(row);
	}
	
	calculateTravelOrdersItemsTotalSum();
	return false;
}

function clearTravelOrdersItemsRow(row) {
	$('.travel-orders-item-dat_travel', row).val('');
	$('.travel-orders-item-origin', row).val('');
	$('.travel-orders-item-destination', row).val('');
	
	$('.travel-orders-item-departure', row).val('');
	$('.travel-orders-item-arrival', row).val('');
	
	$('.travel-orders-item-workdays', row).val('');
	$('.travel-orders-item-workday_price', row).val('');
	$('.travel-orders-item-km', row).val('');
	$('.travel-orders-item-km_price', row).val('');
	
	$('.travel-orders-item-id > input:first', row).val('');
	
	// this is a td cell with duration
	$('.td-travel-orders-item-duration', row).html('');
	$('.td-travel-orders-item-workday-total', row).html('');
	$('.td-travel-orders-item-km-total', row).html('');
	
	// remove action from button
	$('.travel-orders-item-remove', row).attr('href', '#').click(function(){ return false; });
	
	$('.error-message', row).remove();
	$('.error', row).removeClass('error');
	$('.form-error', row).removeClass('form-error');
}

function renumberTravelOrdersItemsRow(row, from, to) {
	$('#TravelOrdersItem' + from + 'Id', row).attr('name', 'data[TravelOrdersItem][' + to + '][id]').attr('id', 'TravelOrdersItem' + to + 'Id');
	$('#TravelOrdersItem' + from + 'OrderId', row).attr('name', 'data[TravelOrdersItem][' + to + '][order_id]').attr('id', 'TravelOrdersItem' + to + 'OrderId');
	
	$('#TravelOrdersItem' + from + 'DatTravel', row).attr('name', 'data[TravelOrdersItem][' + to + '][dat_travel]').attr('id', 'TravelOrdersItem' + to + 'DatTravel');
	$('#TravelOrdersItem' + from + 'Origin', row).attr('name', 'data[TravelOrdersItem][' + to + '][origin]').attr('id', 'TravelOrdersItem' + to + 'Origin');
	$('#TravelOrdersItem' + from + 'Destination', row).attr('name', 'data[TravelOrdersItem][' + to + '][destination]').attr('id', 'TravelOrdersItem' + to + 'Destination');
	
	$('#TravelOrdersItem' + from + 'DepartureHour', row).attr('name', 'data[TravelOrdersItem][' + to + '][departure][hour]').attr('id', 'TravelOrdersItem' + to + 'DepartureHour');
	$('#TravelOrdersItem' + from + 'DepartureMin', row).attr('name', 'data[TravelOrdersItem][' + to + '][departure][min]').attr('id', 'TravelOrdersItem' + to + 'DepartureMin');
	$('#TravelOrdersItem' + from + 'ArrivalHour', row).attr('name', 'data[TravelOrdersItem][' + to + '][arrival][hour]').attr('id', 'TravelOrdersItem' + to + 'ArrivalHour');
	$('#TravelOrdersItem' + from + 'ArrivalMin', row).attr('name', 'data[TravelOrdersItem][' + to + '][arrival][min]').attr('id', 'TravelOrdersItem' + to + 'ArrivalMin');
	
	$('#TravelOrdersItem' + from + 'Workdays', row).attr('name', 'data[TravelOrdersItem][' + to + '][workdays]').attr('id', 'TravelOrdersItem' + to + 'Workdays');
	$('#TravelOrdersItem' + from + 'WorkdayPrice', row).attr('name', 'data[TravelOrdersItem][' + to + '][workday_price]').attr('id', 'TravelOrdersItem' + to + 'WorkdayPrice');
	$('#TravelOrdersItem' + from + 'Km', row).attr('name', 'data[TravelOrdersItem][' + to + '][km]').attr('id', 'TravelOrdersItem' + to + 'Km');
	$('#TravelOrdersItem' + from + 'KmPrice', row).attr('name', 'data[TravelOrdersItem][' + to + '][km_price]').attr('id', 'TravelOrdersItem' + to + 'KmPrice');
}

/**************************************************************************************************/

var onTravelOrdersExpensesNumbersChange = function() {
	var row = $(this).closest('tr');
	calculateTravelOrdersExpensesRow(row);
};

function calculateTravelOrdersExpensesRow(row) {
	calculateTravelOrdersExpensesTotalSum();
}

function calculateTravelOrdersExpensesTotalSum() {
	var price = 0;
	var totalExpenses = 0;
	
	$('#travel-orders-expenses-table > tbody:last > tr').each(function(){
		price = $('input.travel-orders-expense-price', this).LilFloatVal();
		totalExpenses += Math.round(price * 100) / 100;
	});
	$('#travel-orders-expenses-total').html(LilFloatFormat(totalExpenses, 2));
}
		
function addTravelOrdersExpensesRow() {
	// must destroy all datepickers, because we should not copy datepickers dom
	$('.travel-orders-expense-dat_expense').each(function() {$(this).datepicker('destroy')});
	
	var rowClone = $('#travel-orders-expenses-table > tbody:last > tr:first').clone();

	clearTravelOrdersExpensesRow(rowClone);
	
	var i = 0;
	while ($('#TravelOrdersExpense' + i + 'Id').size() > 0) i++;
	renumberTravelOrdersExpensesRow(rowClone, 0, i);
	
	$('.travel-orders-expense-price', rowClone).each(function() { $(this).LilFloat({empty:true}); $(this).blur(onTravelOrdersExpensesNumbersChange); });
	
	$('#travel-orders-expenses-table > tbody:last').append(rowClone);
	
	// reenable all detapickers
	$('.travel-orders-expense-dat_expense').each(function() {$(this).datepicker({dateFormat: "dd.mm."})});
}

function removeTravelOrdersExpensesRow(btn) {
	var row = $(btn).closest('tr');
	var id = $('.travel-orders-expense-id > input:first', row);
	
	if ($(id).val() !== '') {
		// add this item do deleted items list
		var del_list = $('<input/>')
			.attr({
				name: 'data[TravelOrder][expenses_to_delete][]'
			})
			.val($(id).val());
		$(del_list).insertAfter($('#TravelOrderId'));
	}

	if ($('#travel-orders-expenses-table > tbody:last > tr').size() > 1) {
		$(row).remove(); // we have to remove row so we know how many rows are actually visible
	} else {
		clearTravelOrdersExpensesRow(row);
	}
	
	calculateTravelOrdersExpensesTotalSum();
	return false;
}

function clearTravelOrdersExpensesRow(row) {
	$('.travel-orders-expense-dat_expense', row).val('');
	$('.travel-orders-expense-descript', row).val('');
	$('.travel-orders-expense-price', row).val('');
	
	$('.travel-orders-expense-id > input:first', row).val('');
	
	// remove action from button
	$('.travel-orders-expense-remove', row).attr('href', '#').click(function(){ return false; });
	
	$('.error-message', row).remove();
	$('.error', row).removeClass('error');
	$('.form-error', row).removeClass('form-error');
}

function renumberTravelOrdersExpensesRow(row, from, to) {
	$('#TravelOrdersExpense' + from + 'Id', row).attr('name', 'data[TravelOrdersExpense][' + to + '][id]').attr('id', 'TravelOrdersExpense' + to + 'Id');
	$('#TravelOrdersExpense' + from + 'OrderId', row).attr('name', 'data[TravelOrdersExpense][' + to + '][order_id]').attr('id', 'TravelOrdersExpense' + to + 'OrderId');
	
	$('#TravelOrdersExpense' + from + 'DatExpense', row).attr('name', 'data[TravelOrdersExpense][' + to + '][dat_expense]').attr('id', 'TravelOrdersExpense' + to + 'DatExpense');
	$('#TravelOrdersExpense' + from + 'Descript', row).attr('name', 'data[TravelOrdersExpense][' + to + '][descript]').attr('id', 'TravelOrdersExpense' + to + 'Descript');
	$('#TravelOrdersExpense' + from + 'Price', row).attr('name', 'data[TravelOrdersExpense][' + to + '][price]').attr('id', 'TravelOrdersExpense' + to + 'Price');
}