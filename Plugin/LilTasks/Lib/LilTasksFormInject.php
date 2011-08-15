<?php
	class LilTasksFormInject {
		static function generate($view, $entity = 'Invoice') {
		$User = ClassRegistry::init('Lil.User');
		$Area = ClassRegistry::init('Lil.Area');
		$users = $User->find('list');
		$projects = $Area->findForUser(null, 'list');
	
		$task = array(
			'fs_tasks_start' => '<fieldset>',
			'fs_tasks_legend' => sprintf('<legend>%s</legend>', 
				$view->Lil->input('Task.exists', array(
					'checked' => (bool)$view->Form->value('Task.exists') || (bool)$view->Form->value('Task.id'),
					'id'      => 'task-toggle',
					'label'   => false,
					'div'     => false
				)) . ' ' .
				sprintf('<label for="task-toggle">%s</label>', __d('lil_tasks', 'Task'))
			),
			'fs_tasks_div_start'  => '<div id="task">',
			'task_id' => array(
				'class'      => $view->Lil,
				'method'     => 'input',
				'parameters' => array(
					'field' => 'Task.id',
					'options' => array(
						'type' => 'hidden'
					)
				)
			),
			'task_foreign_id' => array(
				'class'      => $view->Lil,
				'method'     => 'input',
				'parameters' => array(
					'field' => 'Task.foreign_id',
					'options' => array(
						'type' => 'hidden'
					)
				)
			),
			'task_model' => array(
				'class'      => $view->Lil,
				'method'     => 'input',
				'parameters' => array(
					'field' => 'Task.model',
					'options' => array(
						'type' => 'hidden',
						'default' => $entity
					)
				)
			),
			'task_title' => array(
				'class'      => $view->Lil,
				'method'     => 'input',
				'parameters' => array(
					'field' => 'Task.title',
					'options' => array(
						'label' => __d('lil_tasks', 'Title') . ':',
					)
				)
			),
			'task_descript' => array(
				'class'      => $view->Lil,
				'method'     => 'input',
				'parameters' => array(
					'field' => 'Task.descript',
					'options' => array(
						'type'  => 'textarea',
						'label' => __d('lil_tasks', 'Descript') . ':',
					)
				)
			),
			'task_deadline' => array(
				'class'      => $view->Lil,
				'method'     => 'input',
				'parameters' => array(
					'field' => 'Task.deadline',
					'options' => array(
						'type' => 'date',
						'label' => __d('lil_tasks', 'Deadline') . ':',
						'default' => ''
					)
				)
			),
			'task_user_id' => 
				($view->Lil->currentUser->role('admin') && (sizeof($users) > 1)) ?
					array(
						'class'      => $view->Lil,
						'method'     => 'input',
						'parameters' => array(
							'field' => 'Task.user_id',
							'options' => array(
								'type'    => 'select',
								'options' => $users,
								'label'   => __d('lil_tasks', 'User') . ':',
								'default' => $this->currentUser->get('id')
							)
						)
					)
				:
					array(
						'class'      => $view->Lil,
						'method'     => 'input',
						'parameters' => array(
							'field' => 'Task.user_id',
							'options' => array(
								'type'    => 'hidden',
								'default' => $view->Lil->currentUser->get('id')
							)
						)
					)
				,
			'fs_tasks_div_end'  => '</div>',
			'fs_tasks_end' => '</fieldset>'
		);
		
		App::uses('Sanitize', 'Utility');
		$toggle_confirm = Sanitize::escape(__d('lil_tasks', 'Are you sure you want to clear task data?', true));
		$d = <<<EOT
			$('#task-toggle').click(function() {
				var doToggle = true;
				if (!$(this).attr('checked') && (
					($('#TaskTitle').val().trim() !== '') || 
					($('#TaskDeadline').val().trim() !== '') || 
					($('#TaskDescript').val().trim() !== '')
				)) doToggle = confirm('{$toggle_confirm}');
				if (doToggle) {
					$('#task').toggle($(this).attr('checked'));
					if (!$(this).attr('checked') && !$('#TaskId').val()) {
						$('#TaskTitle').val('');
						$('#TaskDescript').val('');
						$('#TaskDeadline').val('');
					}
				} else {
					$(this).attr('checked', 'checked');
				}
			});
EOT;
		$task['javascript'][] = $d;
		
		// default hide task
		$task['javascript'][] = '$("#task-toggle").attr("checked") ? $("#task").show() : $("#task").hide();';
			
			return $task;
		}
	}