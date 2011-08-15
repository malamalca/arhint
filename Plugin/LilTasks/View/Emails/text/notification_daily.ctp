<?php
	print (__d('lil_tasks', 'Hello') . PHP_EOL . PHP_EOL);
	
	App::uses('LilDateEngine', 'Lil.Lib');
	$LilDate = LilDateEngine::getInstance();
	
	$todays = false; $overdues = false;
	
	$request = new CakeRequest();
	Router::setRequestInfo(
		$request->addParams(array(
			'plugin' => 'lil_tasks', 'controller' => 'lil_tasks', 'action' => 'index',
			'url' => array('url' => 'admin/lil_tasks/index')
		))->addPaths(array(
			'base' => Configure::read('LilTasks.baseUrl'),
			'webroot' => Configure::read('LilTasks.webroot'),
		))
	);
		
	foreach ($tasks as $task) {
		if (empty($task['Task']['completed'])) {
			if (!$todays && $LilDate->isToday($task['Task']['deadline'])) {
				print (__d('lil_tasks', 'Today\'s tasks:') . PHP_EOL);
				$todays = true;
			}
			if (!$overdues && !$LilDate->isToday($task['Task']['deadline'])) {
				print (PHP_EOL . PHP_EOL . __d('lil_tasks', 'Overdue tasks:') . PHP_EOL);
				$overdues = true;
			}
			printf(' - %1$s (%2$s) | %3$s' . PHP_EOL,
				$task['Task']['title'],
				$task['Task']['deadline'],
				Configure::read('LilTasks.baseUrl') . Router::url(array(
					'base' => false,
					'plugin' => 'lil_tasks',
					'controller' => 'tasks',
					'admin' => true,
					'action' => 'edit',
					$task['Task']['id']
				))
			);
		}
	}
?>

Cheers, LilTasks