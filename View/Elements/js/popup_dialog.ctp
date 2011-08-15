<div id="dialog-form"></div>
<script type="text/javascript">
	// constants for scripts
	var popupTitle = null;
	var popupUrl = null;
	var popupDialog = null;
		
	function popup(title, url, h, w) {
		popupTitle = title;
		popupUrl = url;
		$("#dialog-form").dialog({
			title: popupTitle,
			autoOpen: true,
			height: h ? h : 500,
			width: w ? w : 400,
			modal: true,
			open: function() {
				$(this).html('');
				$(this).load(popupUrl, function() {
					popupDialog = $(this);
					$('form', popupDialog).submit(popupFormSubmit);
				});
			},
			close: function () {
				popupTitle = null;
				popupUrl = null;
				popupDialog = null;
			}
		});
	}
	
	popupFormSubmit = function() {
		$.post(
			popupUrl,
			$('form', popupDialog).serialize(),
			function(data) {
				$(popupDialog).html(data);
				$('form', popupDialog).submit(popupFormSubmit);
			}
		);
		return false;
	} 
</script>