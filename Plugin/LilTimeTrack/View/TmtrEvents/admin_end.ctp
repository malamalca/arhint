<?php
	$this->set('title_for_layout', false);
?>

<div class="form">
<?php
	echo $this->LilForm->create('TmtrEvent');
	echo $this->LilForm->input('id', array('type' => 'hidden'));
	echo $this->LilForm->input('workday_id', array('type' => 'hidden'));
	echo $this->LilForm->input('started', array('type' => 'hidden'));
	
	printf('<fieldset><legend>%s</legend>', __d('lil_time_track', 'Event End'));
	
	print ($this->LilForm->input('end_type', array(
		'type'    => 'radio',
		'hiddenField'  => false,
		'options' => array('auto' => __d('lil_time_track', 'Auto (current time)')),
		'default' => 'auto'
	)));
	
	print ($this->LilForm->input('end_type', array(
		'type'    => 'radio',
		'hiddenField'  => false,
		'options' => array('duration' => __d('lil_time_track', 'Specify duration') . ' : '),
		'after'   => $this->LilForm->input('duration', array(
			'type'  => 'duration',
			'div'   => false,
			'label' => false
		))
	)));
	
	print ($this->LilForm->input('end_type', array(
		'type'    => 'radio',
		'hiddenField'  => false,
		'options' => array('time' => __d('lil_time_track', 'Specify time') . ' : '),
		'after'   => $this->LilForm->input('end_time', array(
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