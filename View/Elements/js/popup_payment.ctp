<div id="dialog-form"></div>
<script type="text/javascript">
	// constants for scripts
	var popupPaymentTitle = null;
	var popupPaymentUrl = null;
	var popupPaymentDialog = null;
		
	function popupPayment(title, url) {
		popupPaymentTitle = title;
		popupPaymentUrl = url;
		$("#dialog-form").dialog({
			title: popupPaymentTitle,
			autoOpen: true,
			height: 470,
			width: 400,
			modal: true,
			open: function() {
				$(this).html('');
				$(this).load(popupPaymentUrl, function() {
					popupPaymentDialog = $(this);
					$('form', popupPaymentDialog).submit(paymentFormSubmit);
				});
			}
		});
	}
	
	paymentFormSubmit = function() {
		$.post(
			popupPaymentUrl,
			$('form', popupPaymentDialog).serialize(),
			function(data) {
				$(popupPaymentDialog).html(data);
				$('form', popupPayment).submit(paymentFormSubmit);
			}
		);
		return false;
	} 
</script>