<?php
	$css = dirname(dirname(dirname(dirname(__FILE__)))) . DS . 'webroot' . DS . 'css' . DS . 'lil_invoices_pdf.css';
	if (file_exists($css)) {
		echo '<style type="text/css">' . PHP_EOL;
		echo file_get_contents($css);
		echo '</style>' . PHP_EOL;
	}
	echo $content_for_layout;