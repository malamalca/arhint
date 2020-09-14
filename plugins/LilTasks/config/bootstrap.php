<?php
	use Cake\Event\EventManager;
	use LilTasks\Event\LilTasksEvents;
	
	$LilTasksEvents = new LilTasksEvents();
	EventManager::instance()->on($LilTasksEvents);