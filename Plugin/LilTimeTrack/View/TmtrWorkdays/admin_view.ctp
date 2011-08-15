<?php
	$this->set('main_menu', array(
		/*'edit' => array(
			'title' => __d('lil_time_track', 'Edit workday', true),
			'visible' => true,
			'url'   => array(
				'admin'      => true,
				'plugin'     => 'lil_time_track',
				'controller' => 'tmtr_workdays',
				'action'     => 'edit',
				$data['TmtrWorkday']['id']
			),
		),*/
		'event_start' => (!empty($data['TmtrWorkday']['duration']) || (sizeof($data['TmtrEvent'])>0) && 
			empty($data['TmtrEvent'][sizeof($data['TmtrEvent'])-1]['duration'])) ? null : array(
				'title' => __d('lil_time_track', 'Start Event', true),
				'visible' => true,
				'url'   => array(
					'admin'      => true,
					'plugin'     => 'lil_time_track',
					'controller' => 'tmtr_events',
					'action'     => 'start',
					$data['TmtrWorkday']['id']
				),
				'params' => array(
					'onclick' => sprintf('popup("%s", $(this).attr("href"), 390); return false;', __d('lil_time_track', 'Start Event'))
				)
			),
		'event_end' => (sizeof($data['TmtrEvent']) == 0) || 
			!empty($data['TmtrEvent'][sizeof($data['TmtrEvent'])-1]['duration']) ? null : array(
				'title' => __d('lil_time_track', 'End Event', true),
				'visible' => true,
				'url'   => array(
					'admin'      => true,
					'plugin'     => 'lil_time_track',
					'controller' => 'tmtr_events',
					'action'     => 'end',
					$data['TmtrEvent'][sizeof($data['TmtrEvent'])-1]['id']
				),
				'params' => array(
					'onclick' => sprintf('popup("%s", $(this).attr("href"), 300); return false;', __d('lil_time_track', 'End Event'))
				)
			),
		'delete' => array(
			'title' => __d('lil_time_track', 'Delete last registration', true),
			'visible' => true,
			'url'   => array(
				'admin'      => true,
				'plugin'     => 'lil_time_track',
				'controller' => 'tmtr_workdays',
				'action'     => 'delete_last',
				$data['TmtrWorkday']['id']
			),
			'params' => array(
				'confirm' => __d('lil_time_track', 'Are you sure you want to delete last registration?')
			)
		),
	));
	
?>
<div id="lil-tmtr-view">
<?php
	$date = strftime('%Y-%m-%d', strtotime($data['TmtrWorkday']['started']));
	$date_prev = strftime('%Y-%m-%d', strtotime($date) - 24*60*60);
	$date_next = strftime('%Y-%m-%d', strtotime($date) + 24*60*60);
	
	$this->set('head_for_layout', false);
	$date_link = sprintf('%2$s %1$s %3$s',
		$this->Html->link($this->LilDate->niceShortDate(
				$date,
				null,
				$this->LilDate->isToday($date) // use words only for today's date
			),
			array(
				'plugin'     => 'lil_time_track',
				'controller' => 'tmtr_workdays',
				'admin'      => true,
				'action'     => 'select',
				'?' => array('filter' => array('date' => $date))
			),
			array('id' => 'lil-tmtr-date-main')
		),
		
		$this->Html->link(__d('lil_time_track', '<< prev'), 
			array(
				'plugin'     => 'lil_time_track',
				'controller' => 'lil_time_track',
				'admin'      => true,
				'action'     => 'select',
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
				'controller' => 'lil_time_track',
				'admin'      => true,
				'action'     => 'select',
				'?' => array('filter' => array('date' => $date_next))
			),
			array(
				'id' => 'lil-tmtr-date-next',
				'onclick' => sprintf('gotoDate("%s"); return false;', $date_next)
			)
		)
	);
	
	printf('<h1>%1$s: %2$s</h1>',
		__d('lil_time_track', 'Workday'),
		$date_link
	);
	
	printf('<input type="hidden" value="%s", id="lil-tmtr-date-main-input" />', $date);
	
	printf('<div><span>%1$s</span>%2$s</div>',
		$this->LilDate->format($data['TmtrWorkday']['started'], '%H:%M'),
		__d('lil_time_track', 'Workday started')
	);
	
	$lunchtime_left = Configure::read('LilTimeTrack.maxLunchtime');
	foreach ($data['TmtrEvent'] as $ev) {
		if ($ev['kind'] == 'private') {
			printf('<div><span>%1$s</span>%2$s</div>',
				$this->LilDate->format($ev['started'], '%H:%M'),
				__d('lil_time_track', 'Private exit started')
			);
			if (!empty($ev['duration'])) {
				printf('<div><span>%1$s</span>%2$s</div>',
					$this->LilDate->format(strtotime($ev['started']) + $ev['duration'], '%H:%M'),
					sprintf(__d('lil_time_track', 'Private exit ended (duration %s)'),
						$this->LilDate->toHoursAndMinutes($ev['duration'])
					)
				);
			}
		} else if ($ev['kind'] == 'lunch') {
			printf('<div><span>%1$s</span>%2$s</div>',
				$this->LilDate->format($ev['started'], '%H:%M'),
				__d('lil_time_track', 'Lunch break started')
			);
			if (!empty($ev['duration'])) {
				printf('<div><span>%1$s</span>%2$s</div>',
					$this->LilDate->format(strtotime($ev['started']) + $ev['duration'], '%H:%M'),
					
					$ev['duration'] > $lunchtime_left ?
						sprintf(__d('lil_time_track', 'Lunch break ended (%1$s lunch break + %2$s private exit)'),
							$this->LilDate->toHoursAndMinutes($lunchtime_left),
							$this->LilDate->toHoursAndMinutes($ev['duration']-$lunchtime_left)
						)
				 	:
						sprintf(__d('lil_time_track', 'Lunch break ended (duration %s)'),
							$this->LilDate->toHoursAndMinutes($ev['duration'])
						)
				);
				
				$lunchtime_left -= $ev['duration'];
				if ($lunchtime_left < 0) $lunchtime_left = 0;
			}
		} else {
			printf('<div><span>%1$s</span>%2$s</div>',
				$this->LilDate->format($ev['started'], '%H:%M'),
				__d('lil_time_track', 'Work started')
			);
			if (!empty($ev['duration'])) {
				printf('<div><span>%1$s</span>%2$s</div>',
					$this->LilDate->format(strtotime($ev['started']) + $ev['duration'], '%H:%M'),
					sprintf(__d('lil_time_track', 'Work ended (duration %s)'),
						$this->LilDate->toHoursAndMinutes($ev['duration'])
					)
				);
			}
		}
		

	}
	
	if (!empty($data['TmtrWorkday']['duration'])) {
		printf('<div><span>%1$s</span>%2$s</div>',
			$this->LilDate->format(strtotime($data['TmtrWorkday']['started']) + $data['TmtrWorkday']['duration'], '%H:%M'),
			__d('lil_time_track', 'Workday ended')
		);
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
	
	// redate
	$("#lil-tasks-redate-date").datepicker({
		dateFormat: 'yy-mm-dd',
		onSelect: function(dateString, inst) {
			redate($("#lil-tasks-redate-id").val(), dateString);
		}
	});
});

function gotoDate(dateText) {
	//$.get(tmtrUrl + dateText, function(data) {
	//	$('#lil-tasks-index').replaceWith(data);
	//});
	document.location.href = tmtrUrl + dateText;
}
</script>
</div>