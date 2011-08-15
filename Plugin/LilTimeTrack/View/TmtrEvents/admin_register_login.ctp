<div id="lil_tmtr_registration_login">
<?php
	printf('<h1>%s</h1>', __d('lil_time_track', 'Welcome to TimeTrack'));
	printf('<p>%s</p>', __d('lil_time_track', 'Please enter your PIN number to proceed.'));
	
	echo $this->LilForm->create(false);
	echo $this->LilForm->input('uid', array(
		'label' => __d('lil_time_track', 'User\'s ID') . ':',
		'autocomplete' => 'off',
		'label' => false
	));
	echo $this->LilForm->submit(__d('lil_time_track', 'Login'));
	echo $this->LilForm->end();
	
	print ('<div class="btn_panel">');
	printf('<input type="button" value="1" class="btn_pin" />');
	printf('<input type="button" value="2" class="btn_pin" />');
	printf('<input type="button" value="3" class="btn_pin" />');
	
	printf('<input type="button" value="4" class="btn_pin" />');
	printf('<input type="button" value="5" class="btn_pin" />');
	printf('<input type="button" value="6" class="btn_pin" />');


	printf('<input type="button" value="7" class="btn_pin" />');
	printf('<input type="button" value="8" class="btn_pin" />');
	printf('<input type="button" value="9" class="btn_pin" />');
	
	printf('<input type="button" value="0" class="btn_pin" />');

	print ('</div>');
	
	echo '</div>';
?>
<script type="text/javascript">
	$(document).ready(function() {
		$(".btn_pin").click(function() {
			$("#uid").val($("#uid").val() + $(this).val());
		});
	});

</script>