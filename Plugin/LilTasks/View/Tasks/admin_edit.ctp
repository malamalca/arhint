<?php
	$this->set('title_for_layout',
		sprintf('%1$s', $this->Form->value('Task.id') ? __d('lil_tasks', 'Edit Task') : __d('lil_tasks', 'Add Task'))
	);
	
	// offer option to delete task when editing existing one
	if ($this->Form->value('Task.id')) {
		$this->set('main_menu', array(
			'add' => array(
				'title' => __d('lil_tasks', 'Delete', true),
				'visible' => true,
				'url'   => array(
					'admin'      => true,
					'plugin'     => 'lil_tasks',
					'controller' => 'tasks',
					'action'     => 'delete',
					$this->Form->value('Task.id')
				),
				'params' => array(
					'confirm' => __d('lil_tasks', 'Are you sure you want to delete this task?')
				)
			)
		));
	}
?>

<div class="form">
<?php
	echo $this->LilForm->create('Task');
	echo $this->LilForm->input('id', array('type' => 'hidden'));
	echo $this->LilForm->input('referer', array('type' => 'hidden'));
	
	echo $this->LilForm->input('project_id', array(
		'label' => __d('lil_tasks', 'Project', true) . ':',
		'empty' => true,
		'default' => $this->Lil->currentArea->get('id')
	));
	echo $this->LilForm->input('user_id', array(
		'label' => __d('lil_tasks', 'User', true) . ':',
		'default' => $this->Lil->currentUser->get('id')
	));
	
	echo $this->LilForm->input('title', array(
		'label' => __d('lil_tasks', 'Title', true) . ':',
	));
	
	echo $this->LilForm->input('descript', array(
		'label' => __d('lil_tasks', 'Description', true) . ':',
		'type'  => 'textarea',
	));
	
	echo $this->LilForm->input('deadline', array(
		'label' => __d('lil_tasks', 'Deadline', true) . ':',
		'type'  => 'date'
	));
	echo $this->LilForm->input('completed', array(
		'label' => __d('lil_tasks', 'Completed', true) . ':',
		'type'  => 'date',
	));
	
	echo $this->LilForm->submit(__d('lil_tasks', 'Save', true));
	echo $this->LilForm->end();
?>
</div>