<?php
	$css = dirname(dirname(dirname(dirname(__FILE__)))) . DS . 'webroot' . DS . 'css' . DS . 'lil_invoices_pdf.css';
	echo '<style type="text/css">' . PHP_EOL;
	echo file_get_contents($css);
	echo '</style>' . PHP_EOL;
	
	// generate client HTML
	$client_address  = '<div id="invoice-client"><table>';
	$client_address .= sprintf('<tr><td>%1$s</td></tr>', $this->Html->clean($data['Client']['title']));

	if (!empty($data['Client']['PrimaryAddress'])) {
		$client_address .= sprintf('<tr><td>%1$s</td></tr>', $this->Html->clean($data['Client']['PrimaryAddress']['street']));
		$client_address .= '<tr><td>&nbsp;</td></tr>';
		$client_address .= sprintf('<tr><td>%1$s</td></tr>', $this->Html->clean(implode(
			' ', 
			array(
				$data['Client']['PrimaryAddress']['zip'],
				$data['Client']['PrimaryAddress']['city']
			)
		)));
		if (!empty($data['Client']['PrimaryAddress']['country'])) {
			$client_address .= sprintf('<tr><td>%1$s</td></tr>', $this->Html->clean($data['Client']['PrimaryAddress']['country']));
		}
	}
	$client_address .= '</table></div>';
	
	$client_tax = '<div id="invoice-client-taxno">';
	if (!empty($data['Client']['tax_no'])) {
		$client_tax .= sprintf('<span class="label">%1$s:</span> %2$s',
			($data['Client']['tax_status']) ? __d('lil_invoices', 'TAX payee no.') : __d('lil_invoices', 'TAX no.'),
			$this->Html->clean($data['Client']['tax_no'])
		);
	}
	$client_tax .= '</div>';
	
	
	$margins = $this->getMargins();
	$this->writeHTMLCell(83, 30, $margins['left'], 40, $client_address, 0);
	$this->writeHTMLCell(83, 10, $margins['left'], 76, $client_tax, 0);
	
	// folding line
	$this->Line(0, 100, 10, 100);
	
	$this->SetY(100);
	
	echo $content_for_layout;