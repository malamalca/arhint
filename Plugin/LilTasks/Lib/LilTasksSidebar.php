<?php
	class LilTasksSidebar {
		static function generate($request) {
			$tasks['title'] = __d('lil_tasks', 'Tasks');
			$tasks['visible'] = true;
			$tasks['active'] = in_array($request->params['controller'], array('tasks'));
			$tasks['url'] = array(
				'admin'      => true,
				'plugin'     => 'lil_tasks',
				'controller' => 'tasks',
				'action'     => 'index',
			);
			
			$selected_date = null;
			if (!empty($request->query['filter']['date'])) {
				$selected_date = $request->query['filter']['date'];
			}
			
			App::uses('LilDateEngine', 'Lil.Lib');
			$LilDate = LilDateEngine::getInstance();
			
			$tasks['items'] = array(
				'lil_tasks' => array(
					'visible' => true,
					'title' => __d('lil_tasks', 'Tasks'),
					'url'   => array(
						'plugin'     => 'lil_tasks',
						'controller' => 'tasks',
						'action'     => 'index',
						'admin'      => true,
					),
					'params' => array(),
					'active' => in_array($request->params['controller'], array('tasks')) && (
						empty($selected_date) || !(
							$LilDate->isToday($selected_date) || 
							$LilDate->isTomorrow($selected_date) ||
							in_array($selected_date, array('7d', '30d')
						)
					)),
					'expand' => true,
					'submenu' => array(
						'tasks_today' => array(
							'visible' => true,
							'title'   => __d('lil_tasks', 'Today'),
							'url'   => array(
								'plugin'     => 'lil_tasks',
								'controller' => 'tasks',
								'admin'      => true,
								'action'     => 'index',
								'?'          => array('filter' => array('date' => strftime('%Y-%m-%d')))
							),
							'active' => $LilDate->isToday($selected_date)
						),
						'tasks_tomorrow' => array(
							'visible' => true,
							'title'   => __d('lil_tasks', 'Tomorrow'),
							'url'   => array(
								'plugin'     => 'lil_tasks',
								'controller' => 'tasks',
								'admin'      => true,
								'action'     => 'index',
								'?'          => array('filter' => array('date' => strftime('%Y-%m-%d', time()+24*60*60)))
							),
							'active' => $LilDate->isTomorrow($selected_date)
						),
						'tasks_7d' => array(
							'visible' => true,
							'title'   => __d('lil_tasks', 'Next 7 days'),
							'url'   => array(
								'plugin'     => 'lil_tasks',
								'controller' => 'tasks',
								'admin'      => true,
								'action'     => 'index',
								'?'          => array('filter' => array('date' => '7d'))
							),
							'active' => $selected_date == '7d'
						),
						'tasks_30d' => array(
							'visible' => true,
							'title'   => __d('lil_tasks', 'Next 30 days'),
							'url'   => array(
								'plugin'     => 'lil_tasks',
								'controller' => 'tasks',
								'admin'      => true,
								'action'     => 'index',
								'?'          => array('filter' => array('date' => '30d'))
							),
							'active' => $selected_date == '30d'
						),
					)
				),
			);
			
			return $tasks;
		}
	}