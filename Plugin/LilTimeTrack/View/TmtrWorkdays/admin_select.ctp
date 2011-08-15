<?php
	$this->set('main_menu', array(
		'add' => array(
			'title' => __d('lil_time_track', 'Add', true),
			'visible' => true,
			'url'   => array(
				'admin'      => true,
				'plugin'     => 'lil_time_track',
				'controller' => 'tmtr_workdays',
				'action'     => 'add',
			),
			'params' => array(
				'onclick' => sprintf(
					'popup("%s", $(this).attr("href"), 350); return false;',
					__d('lil_time_track', 'Add Workday')
				)
			)
		)
	));
?>
<div id="lil-tmtr-select">
<?php
	$this->set('head_for_layout', false);
	$date_link = sprintf('%2$s %1$s %3$s',
		$this->Html->link($this->LilDate->niceShortDate(
				$date,
				null,
				$this->LilDate->isToday($date) // use words only for today's date
			),
			array(
				'plugin'     => 'lil_time_track',
				'controller' => 'tasks',
				'admin'      => true,
				'action'     => 'index',
				'?' => array('filter' => array('date' => $date))
			),
			array('id' => 'lil-tmtr-date-main')
		),
		
		$this->Html->link(__d('lil_time_track', '<< prev'), 
			array(
				'plugin'     => 'lil_time_track',
				'controller' => 'tasks',
				'admin'      => true,
				'action'     => 'index',
				'?' => array('filter' => array('date' => $date_prev))
			),
			array(
				'id' => 'lil-tmtr-date-prev',
				'onclick' => sprintf('gotoDate("%s"); return false;', $date_prev)				
			)
		),
		$this->Html->link(__d('lil_time_track', 'next >>'), 
			array(
				'plugin'     => 'lil_time_track',
				'controller' => 'tasks',
				'admin'      => true,
				'action'     => 'index',
				'?' => array('filter' => array('date' => $date_next))
			),
			array(
				'id' => 'lil-tmtr-date-next',
				'onclick' => sprintf('gotoDate("%s"); return false;', $date_next)
			)
		)
	);
	
	printf('<h1>%1$s: %2$s</h1>',
		__d('lil_time_track', 'Delovni dan'),
		$date_link
	);
	
	printf('<input type="hidden" value="%s", id="lil-tmtr-date-main-input" />', $date);
	
	if (empty($data)) {
		printf(
			'<p>%1$s</p>',
			__d('lil_time_track', 'Hey, this workday has not been started.', true)
		);
	} else {
		printf(
			'<p>%1$s</p>',
			__d('lil_time_track', 'There are multiple workdays for this date. Please select one.', true)
		);
		foreach ($data as $wd) {
			printf('<div>%1$s</div>',
				$this->Html->link($this->LilDate->format($wd['TmtrWorkday']['started'], '%H:%M'), array(
					'action' => 'view',
					$wd['TmtrWorkday']['id']
				))
			);
		}
	}
	
	printf($this->element('js' . DS . 'popup_dialog'));
?>
<script type="text/javascript">
var tmtrUrl = "<?php echo $this->Html->url(array(
	'plugin'     => 'lil_time_track',
	'controller' => 'tmtr_workdays',
	'admin'      => true,
	'action'     => 'select',
	'?'          => array('filter' => array('date' => ''))
)); ?>";

$(document).ready(function() {
	
	// dates picker
	$("#lil-tmtr-date-main-input").datepicker({
		dateFormat: 'yy-mm-dd',
		showButtonPanel: true,
		onSelect: function(dateString, inst) {
			gotoDate(dateString);
		}
	});
	$("#lil-tmtr-date-main").click(function() {
		$("#lil-tmtr-date-main-input").datepicker('show');
		return false;
	});
});

function gotoDate(dateText) {
	//$.get(tasksUrl + dateText, function(data) {
	//	$('#lil-tasks-index').replaceWith(data);
	//});
	document.location.href = tmtrUrl + dateText;
}
</script>
</div>