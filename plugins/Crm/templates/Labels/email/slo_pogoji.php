<?php
    use Cake\Core\Plugin;
    use App\Mailer\ArhintMailer;
    use PhpOffice\PhpSpreadsheet\IOFactory;

    $xlsFile = $this->request->getData('xls');
    if ($xlsFile->getError() !== UPLOAD_ERR_OK) {
        throw new \Exception('Napaka pri prenosu datoteke');
    }

    $spreadsheet = IOFactory::load($xlsFile->getStream()->getMetadata('uri'));

    if (!$spreadsheet) {
        throw new \Exception('Napaka pri nalaganju XLS');
    }

    foreach ($addresses as $address) {
        if (!empty($address->contacts_email->email)) {
            $mailer = new ArhintMailer(null, $this->getCurrentUser());
            $mailer
                ->setFrom($this->getCurrentUser()->email)
                ->setTo($address->contacts_email->email)
                //->setTo('miha.nahtigal@arhim.si')
                ->setSubject('Vloga za projektne pogoje za gradnjo')
                ->setViewVars(['user' => $this->getCurrentUser(), 'address' => $address, 'data' => $this->getRequest()->getData()])
                ->setEmailFormat('both')
                ->viewBuilder()
                    ->setTemplate('Crm.slo_pogoji')
                    ->addHelper('Html');

            $atts = [];

            // excel attachment
            //change it
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('C32', $address->contact->title);
            $sheet->setCellValue('C33', (string)$address->contacts_address);

            //write it again to Filesystem with the same name (=replace)
            //$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Tcpdf');
            $writer = IOFactory::createWriter($spreadsheet, "Xlsx");
            ob_start();
            $writer->save('php://output');
            $excelOutput = ob_get_clean();

            $atts[$xlsFile->getClientFilename()] = ['data' => $excelOutput];

            // other attachemts
            foreach ($this->request->getData('attachment') as $attachment) {
                if ($attachment->getError() === UPLOAD_ERR_OK) {
                    $atts[$attachment->getClientFilename()] = $attachment->getStream()->getMetadata('uri');
                }
            }

            $mailer->setAttachments($atts);

            $result = $mailer->deliver();

            if ($result) {
?>
            <p><?= __d('crm', 'Email successfully sent to "{0}"', $address->contacts_email->email) ?>
<?php
            }
        }
    }
