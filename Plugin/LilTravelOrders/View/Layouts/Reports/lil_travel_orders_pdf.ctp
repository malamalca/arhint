<?php
	$css = dirname(dirname(dirname(dirname(__FILE__)))) . DS . 'webroot' . DS . 'css' . DS . 'lil_travel_orders_pdf.css';
	echo '<style type="text/css">' . PHP_EOL;
	echo file_get_contents($css);
	echo '</style>' . PHP_EOL;
	
	echo $content_for_layout;
	
	$this->Rect(155, 100, 40, 15);
	$this->Text(155, 113, __d('lil_travel_orders', 'Taskee signature'));
	
	$this->Rect(155, 205, 40, 15);
	$this->Text(155, 218, __d('lil_travel_orders', 'TO Tasker\'s signature'));
	
	$this->Rect(155, 227, 40, 15);
	$this->Text(155, 240, __d('lil_travel_orders', 'TO Payer\'s signature'));
	
	$this->Rect(155, 250, 40, 15);
	$this->Text(155, 263, __d('lil_travel_orders', 'TO Receiver\'s signature'));
	
	// reset position
	$this->SetXY(0, 20);
		
	