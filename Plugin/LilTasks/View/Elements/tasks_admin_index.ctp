<div id="lil-tasks-index">
<?php
	$this->set('head_for_layout', false);
	
	if ($date == '7d') {
		$date_link = $this->Html->link(__d('lil_tasks', 'Next 7 days'),
			array(
				'plugin'     => 'lil_tasks',
				'controller' => 'tasks',
				'admin'      => true,
				'action'     => 'index',
				'?' => array('filter' => array('date' => $date))
			),
			array('id' => 'lil-tasks-date-main')
		);
	} else if ($date == '30d') {
		$date_link = $this->Html->link(__d('lil_tasks', 'Next 30 days'),
			array(
				'plugin'     => 'lil_tasks',
				'controller' => 'tasks',
				'admin'      => true,
				'action'     => 'index',
				'?' => array('filter' => array('date' => $date))
			),
			array('id' => 'lil-tasks-date-main')
		);
	} else {
		$date_link = sprintf('%2$s %1$s %3$s',
			$this->Html->link($this->LilDate->niceShortDate(
					$date,
					null,
					$this->LilDate->isToday($date) // use words only for today's date
				),
				array(
					'plugin'     => 'lil_tasks',
					'controller' => 'tasks',
					'admin'      => true,
					'action'     => 'index',
					'?' => array('filter' => array('date' => $date))
				),
				array('id' => 'lil-tasks-date-main')
			),
			
			$this->Html->link(__d('lil_tasks', '<< prev'), 
				array(
					'plugin'     => 'lil_tasks',
					'controller' => 'tasks',
					'admin'      => true,
					'action'     => 'index',
					'?' => array('filter' => array('date' => $date_prev))
				),
				array(
					'id' => 'lil-tasks-date-prev',
					'onclick' => sprintf('gotoDate("%s"); return false;', $date_prev)				
				)
			),
			$this->Html->link(__d('lil_tasks', 'next >>'), 
				array(
					'plugin'     => 'lil_tasks',
					'controller' => 'tasks',
					'admin'      => true,
					'action'     => 'index',
					'?' => array('filter' => array('date' => $date_next))
				),
				array(
					'id' => 'lil-tasks-date-next',
					'onclick' => sprintf('gotoDate("%s"); return false;', $date_next)
				)
			)
		);
	}
	
	printf('<h1>%1$s: %2$s</h1>',
		__d('lil_tasks', 'To-do list for'),
		$date_link
	);
	
	printf('<input type="hidden" value="%s", id="lil-tasks-date-main-input" />', $date);
	
	if (empty($tasks)) {
		printf(
			'<p>%1$s</p>',
			__d('lil_tasks', 'Yeah! Nothing to do.', true)
		);
	} else {
		printf('<fieldset>');
		foreach ($tasks as $tsk) {
			echo $this->LilForm->input(
				'lil_task_'. $tsk['Task']['id'],
				array(
					'type'  => 'checkbox',
					'value' => $tsk['Task']['id'],
					'checked' => empty($tsk['Task']['completed']) ? '' : 'checked',
					
					'label' => array(
						'text' => $tsk['Task']['title'],
						'class' => empty($tsk['Task']['completed']) ? '' : 'striked',
					),
					'div'   => array('id' => 'lil-tasks-div-'.$tsk['Task']['id']),
					'after' => 
					sprintf('<div>%1$s<span class="lil-tasks-control">%2$s | %3$s</span></div>',
						
						($this->LilDate->isToday($date) && $this->LilDate->isToday($tsk['Task']['deadline'])) ? '' :
							sprintf('<span class="lil-tasks-due%3$s">(%1$s %2$s)</span>',
								__d('lil_tasks', 'due'),
								$this->LilDate->niceShortDate($tsk['Task']['deadline']),
								$this->LilDate->isPast($tsk['Task']['deadline']) ? ' lil-tasks-overdue' : ''
							),
						
						
						$this->Html->link(__d('lil_tasks', 'details'),
							array(
								'plugin'     => 'lil_tasks',
								'admin'      => true,
								'controller' => 'tasks',
								'action'     => 'edit',
								$tsk['Task']['id']
							),
							array(
								'class' => 'lil-tasks-details-link',
								'onclick' => sprintf('popup("%s", $(this).attr("href"), 580); return false;', __d('lil_tasks', 'Edit Task'))
							)
						),
						$this->Html->link(__d('lil_tasks', 'redate'), '#',
							array(
								'class' => 'lil-tasks-redate-link',
								'onclick' => sprintf('redateClick("%s"); return false', $tsk['Task']['id'])
							)
						)
					)
				)
			);
		}
		printf('</fieldset>');
	}
	
	printf('<input type="hidden" value="%s", id="lil-tasks-redate-date" />', $date);
	print ('<input type="hidden" value="", id="lil-tasks-redate-id" />'); // temporary storage for ids
	printf($this->element('js' . DS . 'popup_dialog'));
?>
<script type="text/javascript">
var tasksUrl = "<?php echo $this->Html->url(array(
	'plugin'     => 'lil_tasks',
	'controller' => 'tasks',
	'admin'      => true,
	'action'     => 'index',
	'?'          => array('filter' => array('date' => ''))
)); ?>";
var toggleTaskUrl = "<?php echo $this->Html->url(array(
	'plugin'     => 'lil_tasks',
	'controller' => 'tasks',
	'admin'      => true,
	'action'     => 'toggle',
	'id'         => '[[id]]',
	'?'          => array('filter' => array('date' => $date))
)); ?>";
var redateTaskUrl = "<?php echo $this->Html->url(array(
	'plugin'     => 'lil_tasks',
	'controller' => 'tasks',
	'admin'      => true,
	'action'     => 'redate',
	'id'         => '[[id]]',
	'date'       => '[[date]]',
	'?'          => array('filter' => array('date' => $date))
)); ?>";

$(document).ready(function() {
	// 2011-05-10: added path; cookie gets duplicated on different paths so we must include it
	document.cookie = 'CakeCookie[lil_tasks_index]=<?php echo $date; ?>; path=<?php echo Router::url('/'); ?>';
	
	// hover on widget shows control links
	$('div#lil-tasks-index .ui-checkbox').hover(
		function() { $('span.lil-tasks-control', this).show();},
		function() { $('span.lil-tasks-control', this).hide();}
	);
	
	// toggle task completition
	$('div#lil-tasks-index .ui-checkbox input').click(function() {
		if (timeout = $(this).data('timeout')) {
			window.clearTimeout(timeout);
			$(this).data('timeout', null);
		} else {
			$(this).data('timeout', window.setTimeout('toggleTask("'+$(this).val()+'")', 500));
		}
		if ($(this).next('label').hasClass('striked')) {
			$(this).next('label').removeClass('striked');
		} else {
			$(this).next('label').addClass('striked');
		}
	});
	
	// dates picker
	$("#lil-tasks-date-main-input").datepicker({
		dateFormat: 'yy-mm-dd',
		showButtonPanel: true,
		onSelect: function(dateString, inst) {
			gotoDate(dateString);
		},
		beforeShow: function( input ) {
			setTimeout(function() {
				var buttonPane = $(input).datepicker( "widget" ).find(".ui-datepicker-buttonpane");
				$('button', buttonPane).remove();
				var btn = $('<button class="ui-datepicker-current ui-state-default ui-priority-secondary ui-corner-all" type="button">Next 7 days</button>');
				$(btn).unbind("click").bind("click", function () {
					gotoDate("7d");
					$("#lil-tasks-date-main-input").datepicker("hide");
				});
				btn.appendTo(buttonPane);
				
				var btn2 = $('<button class="ui-datepicker-current ui-state-default ui-priority-secondary ui-corner-all" type="button">Next 30 days</button>');
				$(btn2).unbind("click").bind("click", function () {
					gotoDate("30d");
					$("#lil-tasks-date-main-input").datepicker("hide");
				});
				btn2.appendTo(buttonPane);
			}, 1 );
		}
	});
	$("#lil-tasks-date-main").click(function() {
		$("#lil-tasks-date-main-input").datepicker('show');
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
	$.get(tasksUrl + dateText, function(data) {
		$('#lil-tasks-index').replaceWith(data);
	});
}
function redateClick(id) {
	$("#lil-tasks-redate-id").val(id);
	$("#lil-tasks-redate-date").datepicker('show');
	return false;
}
function redate(id, dateText) {
	$.get(redateTaskUrl.replace(/\[\[id\]\]/, id).replace(/\[\[date\]\]/, dateText), function(data) {
		$('#lil-tasks-index').replaceWith(data);
	});
}
function toggleTask(id) {
	$.get(toggleTaskUrl.replace(/\[\[id\]\]/, id), function(data) {
		$('#lil-tasks-index').replaceWith(data);
	});
}
</script>
</div>