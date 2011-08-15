<?php
App::import('vendor', 'TCPDF', array('file' => 'tcpdf/tcpdf.php'));
//App::uses('TCPDF', 'Vendor/tcpdf');

// Extend the TCPDF class to create custom Header and Footer
class TcPdfInvoice extends TCPDF {
	var $invoice = null;
	var $user = null;
	//Page header
	function Header() {
		// Logo
		/*$image_file = K_PATH_IMAGES.'logo_example.jpg';
		$this->Image($image_file, 10, 10, 15, '', 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);*/
		// Set font
		//$this->SetFont('dejavusans', 'B', 10);
		// Title
		/*$this->Cell(0, 10, 'ARHIM, arhitektura, projektiranje, notranja oprema d.o.o.', 0, 1, 'L', 0, '', 0, false, 'M', 'M');
		
		$this->SetFont('dejavusans', '', 10);
		$this->Cell(0, 12, 'Slakova ulica 36', 0, 1, 'L', 0, '', 0, false, 'M', 'M');
		$this->Cell(0, 15, '8210 Trebnje', 0, 1, 'L', 0, '', 0, false, 'M', 'M');
		$this->Cell(0, 12, 'Davčna št.: 55736645 ', 0, 0, 'L', 0, '', 0, false, 'M', 'M');
		$this->Cell(0, 12, 'Raiffeisen bank, TRR št. SI56 2420 3901 0691 883', 0, 0, 'R', 0, '', 0, false, 'M', 'M');
		
		$this->Line(0, 28, 300, 28);
		$this->Ln(10);*/
		if (!empty($this->invoice['Counter']['header'])) {
			$this->Image(APP . 'uploads' . DS . $this->invoice['Counter']['header'], 15, 0, 190);
		}
	}

	// Page footer
	function Footer() {
		// Position at 15 mm from bottom
		$this->SetY(-20);
		// Set font
		//$this->SetFont('helvetica', 'I', 8);
		// Page number
		//$this->Cell(0, 10, 'stran '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
		if (!empty($this->invoice['Counter']['footer'])) {
			$this->Image(APP . 'uploads' . DS . $this->invoice['Counter']['footer'], 15, $this->y, 190);
		}
	}
}

class PdfInvoice {

	function PdfInvoice($data, $invoice, $user) {
		// create new PDF document
		$pdf = new TcPdfInvoice(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->invoice = $invoice;
		$pdf->user = $user;
		
		// set document information2
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('LilIntranet');
		$pdf->SetTitle('DOKUMENT');
		$pdf->SetSubject('Dokument');
		$pdf->SetKeywords('dokument, racun, invoice');
		
		// set default header data
		$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
		
		// set header and footer fonts
		$pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
		
		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		
		// By default TCPDF enables font subsetting to reduce the size of embedded Unicode TTF fonts, this process, that is very slow and requires a lot of memory
		$pdf->setFontSubsetting(false);
		
		//set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT + 3, PDF_MARGIN_TOP + 13, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		$pdf->setHeaderMargin(7);
		
		//set auto page breaks
		$pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
		
		//set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		
		//set some language-dependent strings
		$l['a_meta_charset'] = 'UTF-8';
		$l['a_meta_dir'] = 'ltr';
		$l['a_meta_language'] = 'sl';
		$pdf->setLanguageArray($l);
		
		// ---------------------------------------------------------
		
		// set font
		$pdf->SetFont('dejavusans', '', 10);

		// add a page
		$pdf->AddPage();
		
		// folding line
		$pdf->line(0, 100, 10, 100);
		
		App::import('vendor', 'SimpleHtmlDom');
		$html = str_get_html($data);
		$css = $html->find('style', 0)->outertext;
		
		$pdf->SetCellPadding(2);

		if ($client = $html->find('div[id=invoice-client]', 0)->outertext) {
			$pdf->writeHTMLCell(83, 30, 20, 42, $css . PHP_EOL . $client, 1);
		}
		
		if ($taxno = $html->find('div[id=invoice-client-taxno]', 0)->outertext) {
			$pdf->writeHTMLCell(83, 10, 20, 76, $css . PHP_EOL . $taxno);
		}
		
		if ($body = $html->find('div[id=invoice-body]', 0)->outertext) {
			$pdf->writeHTMLCell(180, 10, 15, 80, $css . PHP_EOL . $body );
		}
		
		// ---------------------------------------------------------
		
		//Close and output PDF document
		$pdf->Output($invoice['Invoice']['no'] . '.pdf', 'I');
	}
}
