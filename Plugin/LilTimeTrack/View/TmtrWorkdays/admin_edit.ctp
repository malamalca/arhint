<?php
	$this->set('title_for_layout',
		sprintf('%1$s', $this->Form->value('TmtrWorkday.id') ? __d('lil_time_track', 'Edit Workday') : __d('lil_time_track', 'Add Workday'))
	);
	$this->set('main_menu', array(
		'delete' => array(
			'title' => __d('lil_time_track', 'Delete', true),
			'visible' => true,
			'url'   => array(
				'admin'      => true,
				'plugin'     => 'lil_time_track',
				'controller' => 'tmtr_workdays',
				'action'     => 'delete',
				$this->Form->value('TmtrWorkday.id')
			),
			'params' => array(
				'confirm' => __d('lil_time_track', 'Are you sure you want to delete this workday?')
			)
		)
	));
?>

<div class="form">
<?php
	echo $this->LilForm->create('TmtrWorkday');
	echo $this->LilForm->input('id', array('type' => 'hidden'));
	echo $this->LilForm->input('referer', array('type' => 'hidden'));
	
	if ($this->Lil->currentUser->role('admin')) {
		echo $this->LilForm->input('user_id', array(
			'label' => __d('lil_time_track', 'User', true) . ':',
			'default' => $this->Lil->currentUser->get('id')
		));
	} else {
		echo $this->LilForm->input('user_id', array(
			'type'    => 'hidden',
			'default' => $this->Lil->currentUser->get('id')
		));
	}
	
	echo $this->LilForm->input('started', array(
		'label' => __d('lil_time_track', 'Started', true) . ':',
		'type'  => 'datetime'
	));
	echo $this->LilForm->input('duration', array(
		'label' => __d('lil_time_track', 'Duration', true) . ':',
		'type'  => 'duration',
		'empty' => true
	));
	
	echo $this->LilForm->submit(__d('lil_time_track', 'Save', true));
	echo $this->LilForm->end();
?>
</div>