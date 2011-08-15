<?php
	$this->set('main_menu', array(
		'add' => array(
			'title' => __d('lil_tasks', 'Add', true),
			'visible' => true,
			'url'   => array(
				'admin'      => true,
				'plugin'     => 'lil_tasks',
				'controller' => 'tasks',
				'action'     => 'add',
			),
			'params' => array(
				'onclick' => sprintf('popup("%s", $(this).attr("href"), 580); return false;', __d('lil_expenses', 'Add Task'))
			)
		)
	));
	// contents moved to element because the same element is displayed on dashboard
	echo $this->element('tasks_admin_index');