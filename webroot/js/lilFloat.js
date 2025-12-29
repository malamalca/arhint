/*
 *
 * Copyright (c) 2006/2007 Sam Collett (http://www.texotela.co.uk)
 * Licensed under the MIT License:
 * http://www.opensource.org/licenses/mit-license.php
 *
 * Version 1.0
 * Demo: http://www.texotela.co.uk/code/jquery/numeric/
 *
 * $LastChangedDate$
 * $Rev$
 */

var lilFloatSetup = {
	decimalSeparator   : ".",
	thousandsSeparator : "",
};

/*
 * Allows only valid characters to be entered into input boxes.
 * Note: does not validate that the final text is a valid number
 * (that could be done by another script, or server-side)
 *
 * @name     numeric
 * @param    decimal      Decimal separator (e.g. '.' or ',' - default is '.')
 * @param    callback     A function that runs if the number is not valid (fires onblur)
 * @author   Sam Collett (http://www.texotela.co.uk)
 * @example  $(".numeric").numeric();
 * @example  $(".numeric").numeric(",");
 * @example  $(".numeric").numeric(null, callback);
 *
 */
function LilFloatFormat(val, pPlaces, pDecimal)
{
	var decimal = pDecimal || lilFloatSetup.decimalSeparator;
	var places = 2; if (typeof pPlaces == 'number') places = pPlaces;

	if (typeof val == "string") {
		var x = LilFloatStringToFloat(val, decimal);
	} else {
		var x = val;
	}

	x = parseFloat(x).toFixed(places).split('.');

	wholesPart = x[0];
	decimalPart = x.length > 1 ? decimal + x[1] : '';

	if (ts = lilFloatSetup.thousandsSeparator) {
		var rgx = /(\d+)(\d{3})/;
		while (rgx.test(wholesPart)) {
			wholesPart = wholesPart.replace(rgx, "$1" + ts + "$2");
		}
	}

	return wholesPart + decimalPart;
}

function LilFloatStringToFloat(val, pDecimal) {
	var decimal = pDecimal || lilFloatSetup.decimalSeparator;
	var re1 = new RegExp("[^\\-0-9\\" + decimal + "]", "g");
	var re2 = new RegExp("[\\" + decimal + "]", "g");
	var str = val + "";
	str = str.replace(re1, '').replace(re2, '.');
	if (str === '') return 0;
	return parseFloat(str);
}

function LilFloatIsValidFloat(val, pDecimal) {
	if ((typeof val == 'number') && isFinite(val)) return true;
	var rx_string = '^[-+]?(0|([1-9]([0-9]*';

	if (ts = lilFloatSetup.thousandsSeparator) {
		rx_string = rx_string + '|([0-9]{0,2}(\'' + ts + '[0-9]{3})*)';
	}
	rx_string = rx_string + ')))';

	if (pDecimal !== false) {
		var decimals = '1,';
		if (typeof pDecimal == "number") decimals = decimals + pDecimal;
		rx_string = rx_string + "(\\" + lilFloatSetup.decimalSeparator + "{1}[0-9]{" + decimals + "})?";
	}
	rx_string = rx_string + '$';

	var re1 = new RegExp(rx_string, "i");
	return re1.test(val);
}

jQuery.fn.LilFloatVal = function()
{
	var decimal = jQuery(this).data('LilFloatDecimalSeparator') || lilFloatSetup.decimalSeparator;
	var places = jQuery(this).data('LilFloatPlaces') || 2;

	var val;
	if (jQuery(this).prop("type") == "number") {
		val = jQuery(this).val();
	} else {
		val = LilFloatStringToFloat(jQuery(this).val(), decimal) + "";
	}

	return Math.round(val * Math.pow(10, places)) /  Math.pow(10, places);
}
jQuery.fn.LilFloatFormat = function()
{
	var decimal = jQuery(this).data('LilFloatDecimalSeparator') || lilFloatSetup.decimalSeparator;
	var places = jQuery(this).data('LilFloatPlaces') || 2;
	return LilFloatFormat(jQuery(this).val(), places, decimal);
}

jQuery.fn.LilFloat = function(options)
{
	var default_options = {
		places   : 2,
		decimal  : lilFloatSetup.decimalSeparator,
		empty    : false,
		callback : function() {}
	};
    options = jQuery().extend(default_options, options);

	jQuery(this).data('LilFloatDecimalSeparator', options.decimal);
	jQuery(this).data('LilFloatPlaces', options.places);

	this.focus(
		function(e) {
			// clear thousands and monetary signs
			jQuery(this).addClass('input-left');
			jQuery(this).removeClass('input-right');

			// replace all characters but - sign, numbers and decimal separator
			if ((jQuery(this).val() === '')) return true;
			var val = LilFloatStringToFloat(jQuery(this).val(), options.decimal) + '';
			jQuery(this).val(val.replace(/\./g, options.decimal));
		}
	)
	.blur(
		function(e)
		{
			var val = jQuery(this).val();
			if(val !== "")
			{
			     if (!options.places) {
					var re = new RegExp("^[-+]?(0|([1-9]([0-9]*)))$", 'g');
				} else {
					var re = new RegExp('^[-+]?(0|([1-9]([0-9]*)))(\\' + lilFloatSetup.decimalSeparator + '{1}[0-9]{1,})?$', 'g');
				}
				if(!re.test(val))
				{
					// not valid
					options.callback.apply(this);
					jQuery(this).parent('div').addClass('error');
				} else {
					// valid
					jQuery(this).parent('div').removeClass('error');
					jQuery(this).val(LilFloatFormat(val, options.places, options.decimal));
				}
			} else {
				if (!options.empty) jQuery(this).val(LilFloatFormat(0, options.places, options.decimal));
			}

			jQuery(this).addClass('input-right');
			jQuery(this).removeClass('input-left');
		}
	)
	.keypress(
		function(e)
		{
			var key = e.charCode ? e.charCode : e.keyCode ? e.keyCode : 0;

			// allow enter/return key (only when in an input box)
			if(key == 13 && this.nodeName.toLowerCase() == "input")
			{
				return true;
			}
			else if(key == 13)
			{
				return false;
			}
			var allow = false;
			// allow Ctrl+A
			if((e.ctrlKey && key == 97 /* firefox */) || (e.ctrlKey && key == 65) /* opera */) return true;
			// allow Ctrl+X (cut)
			if((e.ctrlKey && key == 120 /* firefox */) || (e.ctrlKey && key == 88) /* opera */) return true;
			// allow Ctrl+C (copy)
			if((e.ctrlKey && key == 99 /* firefox */) || (e.ctrlKey && key == 67) /* opera */) return true;
			// allow Ctrl+Z (undo)
			if((e.ctrlKey && key == 122 /* firefox */) || (e.ctrlKey && key == 90) /* opera */) return true;
			// allow or deny Ctrl+V (paste), Shift+Ins
			if((e.ctrlKey && key == 118 /* firefox */) || (e.ctrlKey && key == 86) /* opera */
			|| (e.shiftKey && key == 45)) return true;

			// if a number was not pressed
			if(key < 48 || key > 57)
			{
				/* '-' only allowed at start */
				if(key == 45 && ((typeof this.selectionStart == "undefined") || (this.selectionStart == 0)) && (this.value.indexOf('-') == -1)) {
					return true;
				}
				/* only one decimal separator allowed */
				if(key == options.decimal.charCodeAt(0) && this.value.indexOf(options.decimal) != -1)
				{
					allow = false;
				}
				// check for other keys that have special purposes
				if(
					key != 8 /* backspace */ &&
					key != 9 /* tab */ &&
					key != 13 /* enter */ &&
					key != 35 /* end */ &&
					key != 36 /* home */ &&
					key != 37 /* left */ &&
					key != 39 /* right */ //&&
					//key != 46 /* del */
				)
				{
					allow = false;
				}
				else
				{
					// for detecting special keys (listed above)
					// IE does not support 'charCode' and ignores them in keypress anyway
					if(typeof e.charCode != "undefined")
					{
						// special keys have 'keyCode' and 'which' the same (e.g. backspace)
						if(e.keyCode == e.which && e.which != 0)
						{
							allow = true;
						}
						// or keyCode != 0 and 'charCode'/'which' = 0
						else if(e.keyCode != 0 && e.charCode == 0 && e.which == 0)
						{
							allow = true;
						}
					}
				}
				// if key pressed is the decimal and it is not already in the field
				if(key == options.decimal.charCodeAt(0) && this.value.indexOf(options.decimal) == -1)
				{
					allow = true;
				}
			}
			else
			{
				allow = true;
			}
			return allow;
		}
	);
	return this;
}
