<?php
	$this->set('title_for_layout', __d('lil_time_track', 'Time Track Registration'));
	
	print ('<div id="tmtr_register">');
	
	print ('<div class="tmtr_button_line">');
	printf('<div class="tmtr_button tmtr_btn_disabled"><span>%1$s</span></div>',
		__d('lil_time_track', 'Workday Start')
	);
	
	printf('<div class="tmtr_button">%1$s</div>',
		$this->Html->link('<span>' . __d('lil_time_track', 'Workday End') . '</span>',
			array(
				'controller' => 'tmtr_workdays',
				'action'     => 'stop',
				$workday_id
			), array('id' => 'tmtr-btn-workday-end', 'escape' => false)
		)
	);
	print ('<div style="clear: both" />');
	print ('</div>');
	
	print ('<div class="tmtr_button_line">');
	printf('<div class="tmtr_button">%1$s</div>',
		$this->Html->link('<span>' . __d('lil_time_track', 'Lunchbreak Start') . '</span>',
			array(
				'controller' => 'tmtr_events',
				'action'     => 'register_start',
				'workday'    => $workday_id,
				'kind'       => 'lunch'
			), array('id' => 'tmtr-btn-lunch-start', 'escape' => false)
		)
	);
	
	printf('<div class="tmtr_button tmtr_btn_disabled"><span>%1$s</span></div>',
		__d('lil_time_track', 'Lunchbreak End')
	);
	print ('<div style="clear: both" />');
	print ('</div>');
	
	print ('<div class="tmtr_button_line">');
	printf('<div class="tmtr_button">%1$s</div>',
		$this->Html->link('<span>' . __d('lil_time_track', 'Private Exit Start') . '</span>',
			array(
				'controller' => 'tmtr_events',
				'action'     => 'register_start',
				'workday'    => $workday_id,
				'kind'       => 'private'
			), array('id' => 'tmtr-btn-private-start', 'escape' => false)
		)
	);
	printf('<div class="tmtr_button tmtr_btn_disabled"><span>%1$s</span></div>',
		__d('lil_time_track', 'Private Exit End')
	);
	print ('<div style="clear: both" />');
	print ('</div>');
	
	print ('</div>');
?>
<script type="text/javascript">
	var timeoutCntr = null;
	
	$(document).ready(function() {
		resetCounter();
	});
	
	function resetCounter() {
		if (timeoutCntr) clearTimeout(timeoutCntr);
		timeoutCntr = setTimeout(doTimeout, 5000);
	}
	
	function doTimeout() {
		//window.location.reload();
	}
</script>