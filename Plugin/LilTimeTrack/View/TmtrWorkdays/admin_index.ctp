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
				'onclick' => sprintf('popup("%s", $(this).attr("href"), 580); return false;', __d('lil_time_track', 'Add Workday'))
			)
		)
	));

?>
<div id="lil-tmtr-index">
<?php
	if (!empty($filter['month'])) {
		list($year, $month) = explode('-', $filter['month']);
		$span_caption = $months[(int)$month];
	} else {
		$span_caption = __d('lil_time_track', 'costum span');
	}
	$span_link = $this->Html->link($span_caption, '#');
	
	$start_link = $this->Html->link($this->LilDate->format($filter['start']), array('action' => 'filter'), array('id' => 'lil-tmtr-link-date-start'));
	$end_link = $this->Html->link($this->LilDate->format($filter['end']), array('action' => 'filter'), array('id' => 'lil-tmtr-link-date-end'));
	
	$title = sprintf('<h1>%1$s</h1>',
		sprintf(
			__d('lil_time_track', 'Work days for %1$s, from %2$s to %3$s'),
			$span_link, $start_link, $end_link
		)
	);
	print ($title);
	$this->set('head_for_layout', false);
	$this->set('title_for_layout', strip_tags($title));
	
	printf('<input type="hidden" value="%s", id="lil-tmtr-input-date-start" />', $filter['start']);
	printf('<input type="hidden" value="%s", id="lil-tmtr-input-date-end" />', $filter['end']);
	
	print ('<table class="index-static"><thead><tr class="ui-toolbar ui-widget-header ui-corner-top">');
	printf('<th class="center">%s</th>', __d('lil_time_track', 'Day'));
	printf('<th class="center">%s</th>', __d('lil_time_track', 'Expected'));
	printf('<th class="center">%s</th>', __d('lil_time_track', 'Started'));
	printf('<th class="right">%s</th>', __d('lil_time_track', 'Lunch'));
	printf('<th class="right">%s</th>', __d('lil_time_track', 'Private Exit'));
	printf('<th class="right">%s</th>', __d('lil_time_track', 'Work Duration'));
	printf('<th class="right">%s</th>', __d('lil_time_track', 'Total Work Duration'));
	//printf('<th>%s</th>', __d('lil_time_track', 'Total Duration'));
	print ('</tr></thead><tbody>');
	
	$start_cnt = strtotime($filter['start']);
	$end_cnt = strtotime($filter['end']);
	
	$w = 0;
	$total = 0;
	$expected = 0;
	$private = 0;
	$lunch = 0;
	
	for ($i = $start_cnt; $i <= $end_cnt; $i += 24*60*60) {
	
		// calculate amount of expected hours
		$daily_expectation = 0;
		$dow = $this->LilDate->format($i, '%w');
		if ($dow > 0 && $dow < 6) {
			$daily_expectation = 60*60*8; // eight hours
		}
		$expected += $daily_expectation;
		
		// daily amounts
		$daily_lunch = 0;
		$daily_private = 0;
		$daily_total = 0;
		
		// multiple workdays?
		$workdays = array();
		$lunch_left = Configure::read('LilTimeTrack.maxLunchtime');
		
		while (
			!empty($data[$w]['TmtrWorkday']['started']) && 
			$this->LilDate->isSameDay($i, $data[$w]['TmtrWorkday']['started'])
		) {
			$wd = $data[$w];
			
			
			// calculate everything
			$wd_lunch = 0; $wd_transfer = 0; $wd_private = 0;
			foreach ($wd['TmtrEvent'] as $ev_k => $ev) {
				if ($ev['kind'] == 'lunch') {
					if ($lunch_left < $ev['duration']) {
						$wd_transfer += $ev['duration'] - $lunch_left;
						$wd['TmtrEvent'][$ev_k]['duration'] = $lunch_left;
						$wd['TmtrEvent'][$ev_k]['transfer'] = $wd_transfer;
						$lunch_left = 0;
					} else {
						$lunch_left -= $ev['duration'];
						$wd['TmtrEvent'][$ev_k]['transfer'] = 0;
					}
					$wd_lunch += $wd['TmtrEvent'][$ev_k]['duration'];
				} else if ($ev['kind'] == 'private') {
					$wd_private += $ev['duration'];
				}
			}
			
			$wd['lunch_transfer'] = $wd_transfer;
			$wd['lunch_duration'] = $wd_lunch;
			$wd['private_duration'] = $wd_private;
			
			$daily_lunch += $wd_lunch;
			$daily_total += ($wd['TmtrWorkday']['duration'] - $wd['lunch_transfer'] - $wd['private_duration']);
			
			$lunch += $wd_lunch;
			$private += $wd_transfer;
			$private += $wd_private;
			
			$workdays[$wd['TmtrWorkday']['id']] = $wd;
			$w++;
		}
		$total += $daily_total;
		
		// display
		if (empty($workdays)) {
			print ('<tr>');
			printf('<td>%1$s</td>' . PHP_EOL,
				$this->Html->link($this->LilDate->format($i),
					array(
						'action' => 'select',
						'?' => array('filter' => array('date' => $this->LilDate->toSql($i, false)))
					)
				)
			);
			printf('<td class="center">%1$s</td>' . PHP_EOL,
				$this->LilDate->toHoursAndMinutes($daily_expectation)
			);
			print ('<td colspan="5">&nbsp;</td>');
		} else {
			$wd_pos = 0;
			foreach ($workdays as $wd_id => $wd) {
				printf('<tr%s>', ($wd_pos == (sizeof($workdays)-1) ? ' class="tmtr-wd-row"' : ''));
				
				if ($wd_pos == 0) {
					// if there is only one workday in a date, goto view action
					// if there are multiple, offer action to select workday in a day
					printf('<td rowspan="%2$s">%1$s</td>' . PHP_EOL,
						$this->Html->link($this->LilDate->format($i),
							sizeof($workdays) == 1
							?
							array(
								'action' => 'view',
								$wd['TmtrWorkday']['id']
							)
							:
							array(
								'action' => 'select',
								'?' => array('filter' => array('date' => $this->LilDate->toSql($i, false)))
							)
						),
						sizeof($workdays)
					);
					
					// display expected for this workday
					printf('<td class="center" rowspan="%2$s">%1$s</td>' . PHP_EOL,
						$this->LilDate->toHoursAndMinutes($daily_expectation),
						sizeof($workdays)
					);
				}
				
				// display started time
				printf('<td class="center">%s</td>' . PHP_EOL,
					$this->LilDate->format($wd['TmtrWorkday']['started'], '%H:%M')
				);
				
				// display lunch
				printf('<td class="right">%s</td>' . PHP_EOL,
					$this->LilDate->toHoursAndMinutes($wd['lunch_duration'])
				);
				
				// display private exits
				printf('<td class="right">%s</td>' . PHP_EOL,
					$this->LilDate->toHoursAndMinutes($wd['private_duration'] + $wd['lunch_transfer'])
				);
				
				// display work duraion
				printf('<td class="right">%s</td>' . PHP_EOL,
					$this->LilDate->toHoursAndMinutes(
						$wd['TmtrWorkday']['duration']
						- $wd['private_duration'] 
						- $wd['lunch_transfer'] 
						- $wd['lunch_duration']
					)
				);
				
				// display total work duraion
				if ($wd_pos == 0) {
					// if there is only one workday in a date, goto view action
					// if there are multiple, offer action to select workday in a day
					printf('<td rowspan="%2$s" class="right">%1$s</td>' . PHP_EOL,
						$this->LilDate->toHoursAndMinutes($daily_total),
						sizeof($workdays)
					);
				}
				
				print ('</tr>');
				$wd_pos++;
			}
		}
	}
	print ('</tbody>');
	
	print ('<tfoot>');
	print ('<tr class="ui-toolbar ui-widget-header ui-corner-bl ui-corner-br">');
	printf('<th>%1$s:</th>', __d('lil_time_track', 'Expected'));
	printf('<th class="center">%1$s</th>', $this->LilDate->toHoursAndMinutes($expected));
	printf('<th colspan="4" class="right">%s:</th>', __d('lil_time_track', 'Total'));
	printf('<th class="right">%1$s</th>', $this->LilDate->toHoursAndMinutes($total));
	print ('</tr>');
	
	printf('<tr class="ui-toolbar ui-widget-header ui-corner-bl ui-corner-br"><th colspan="6" class="right">%1$s:</th><th class="right">%2$s</th></tr>',
		__d('lil_time_track', 'SALDO'),
		$this->LilDate->toHoursAndMinutes($total - $expected)
	);
	print ('</tfoot></table>');
	
?>
</div>
<script type="text/javascript">
	var tmtrUrlStartEnd = "<?php echo Router::url(array(
		'plugin'     => 'lil_time_track',
		'controller' => 'tmtr_workdays',
		'admin'      => true,
		'action'     => 'index',
		'?'          => array('filter' => array('start' => '[[start]]', 'end' => '[[end]]'))
	)); ?>";
	var tmtrUrlMonth = "<?php echo Router::url(array(
		'plugin'     => 'lil_time_track',
		'controller' => 'tmtr_workdays',
		'admin'      => true,
		'action'     => 'index',
		'?'          => array('filter' => array('month' => '[[month]]'))
	), array('escape' => false)); ?>";
	
	function filterByDate(dateText, startOrEnd) {
		var rx_start = new RegExp("(\\%5B){2}start(\\%5D){2}", "i");
		var rx_end = new RegExp("(\\%5B){2}end(\\%5D){2}", "i");
		//$.get(tmtrUrlStartEnd.replace(rx, dateText), function(data) {
		//	$('#lil-tmtr-index').replaceWith(data);
		//});
		if (startOrEnd == 'start') {
			rpl_start = dateText;
			rpl_end = $('#lil-tmtr-input-date-end').val();
		} else {
			rpl_start = $('#lil-tmtr-input-date-start').val();
			rpl_end = dateText;
		}
		document.location.href = tmtrUrlStartEnd.replace(rx_start, rpl_start).replace(rx_end, rpl_end);
	}

	$(document).ready(function() {
		// move input to link
		var pos_end = $("#lil-tmtr-link-date-end").position();
		var pos_start = $("#lil-tmtr-link-date-start").position();
		
		// dates picker
		$("#lil-tmtr-input-date-start").datepicker({
			dateFormat: 'yy-mm-dd',
			onSelect: function(dateString, inst) {
				filterByDate(dateString, 'start');
			},
			beforeShow: function(input, inst) {
				inst.dpDiv.css({'marginLeft': pos_start.left - 20, 'marginTop': '-15px'});
			}
		});
		$("#lil-tmtr-link-date-start").click(function() {
			$("#lil-tmtr-input-date-start").datepicker('show');
			return false;
		});
		
		$("#lil-tmtr-input-date-end").datepicker({
			dateFormat: 'yy-mm-dd',
			onSelect: function(dateString, inst) {
				filterByDate(dateString, 'end');
			},
			beforeShow: function(input, inst) {
				inst.dpDiv.css({'marginLeft': pos_end.left - 20, 'marginTop': '-15px'});
			}
		});
		$("#lil-tmtr-link-date-end").click(function() {
			$("#lil-tmtr-input-date-end").datepicker('show');
			return false;
		});
	});
</script>