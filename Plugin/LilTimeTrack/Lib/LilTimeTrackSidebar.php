<?php
	class LilTimeTrackSidebar {
		static function generate($request) {
			$tasks['title'] = __d('lil_time_track', 'Time Track');
			$tasks['visible'] = true;
			$tasks['active'] = in_array($request->params['plugin'], array('lil_time_track'));
			$tasks['url'] = array(
				'admin'      => true,
				'plugin'     => 'lil_time_track',
				'controller' => 'tmtr_workdays',
				'action'     => 'index',
			);
			
			$selected_date = null;
			if (!empty($request->query['filter']['date'])) {
				$selected_date = $request->query['filter']['date'];
			}
			
			App::uses('LilDateEngine', 'Lil.Lib');
			$LilDate = LilDateEngine::getInstance();
			
			$tasks['items'] = array(
				'lil_time_track' => array(
					'visible' => true,
					'title' => __d('lil_time_track', 'Index by day'),
					'url'   => array(
						'plugin'     => 'lil_time_track',
						'controller' => 'tmtr_workdays',
						'admin'      => true,
						'action'     => 'index',
						
					),
					'params' => array(),
					'active' => in_array($request->params['controller'], array('tmtr_workdays')) &&
						$request->params['action'] == 'index',
					'expand' => false,
					'submenu' => false,
				)
			);
			
			return $tasks;
		}
	}