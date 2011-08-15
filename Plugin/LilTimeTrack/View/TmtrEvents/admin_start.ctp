<?php
	$this->set('title_for_layout', false);
?>

<div class="form">
<?php
	echo $this->LilForm->create('TmtrEvent');
	echo $this->LilForm->input('id', array('type' => 'hidden'));
	echo $this->LilForm->input('workday_id', array('type' => 'hidden'));
	
	echo $this->LilForm->input('kind', array(
		'legend' => __d('lil_time_track', 'Register Event'),
		'type'  => 'radio',
		'options' => array(
			'end'     => __d('lil_time_track', 'Workday end'),
			'lunch'   => __d('lil_time_track', 'Start Lunch'),
			'private' => __d('lil_time_track', 'Private Exit'),
			'work'    => __d('lil_time_track', 'Other'),
		),
		'default' => 'end'
	));
	
	printf('<fieldset><legend>%s</legend>', __d('lil_time_track', 'Event Start'));
	
	print ($this->LilForm->input('start_type', array(
		'type'    => 'radio',
		'hiddenField'  => false,
		'options' => array('auto' => __d('lil_time_track', 'Auto (after last registration)')),
		'default' => 'auto'
	)));
	
	print ($this->LilForm->input('start_type', array(
		'type'    => 'radio',
		'hiddenField'  => false,
		'options' => array('manual' => __d('lil_time_track', 'Specify time') . ' : '),
		'after'   => $this->LilForm->input('started', array(
			'type'  => 'time',
			'div'   => false,
			'label' => false
		))
	)));
	print ('</fieldset>');
	
	echo $this->LilForm->submit(__d('lil_time_track', 'Save'));
	echo $this->LilForm->end();
?>
</div>