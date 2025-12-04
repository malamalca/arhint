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
            $mailer = new ArhintMailer($this->getCurrentUser());
            $mailer
                ->setFrom($this->getCurrentUser()->email)
                ->setTo($address->contacts_email->email)
                //->setTo('miha.nahtigal@arhim.si')
                ->setSubject('Vloga za mnenje za gradnjo')
                ->setViewVars(['user' => $this->getCurrentUser(), 'address' => $address, 'data' => $this->getRequest()->getData()])
                ->setEmailFormat('both')
                ->viewBuilder()
                    ->setTemplate('Crm.slo_mnenja')
                    ->addHelper('Html');

            $atts = [];

            $data = json_decode($address->descript, true);

            // excel attachment
            //change it
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('C32', $address->contact->title);
            $sheet->setCellValue('C33', (string)$address->contacts_address);
            $sheet->setCellValue('C34', (string)($data['opis'] ?? ''));

            $sheet->setCellValue('C38', (string)($data['stPogojev'] ?? ''));
            $sheet->setCellValue('C39', (string)($data['datumPogojev'] ?? ''));

            //write it again to Filesystem with the same name (=replace)
            //$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Tcpdf');
            $writer = IOFactory::createWriter($spreadsheet, "Xlsx");
            ob_start();
            $writer->save('php://output');
            $excelOutput = ob_get_clean();

            $atts[$xlsFile->getClientFilename()] = ['data' => $excelOutput];

            // other attachemts
            foreach ((array)$address->attachments as $attachment) {
                $atts[$attachment->filename] = $attachment->getFilePath();
            }
            foreach ($attachments as $attachment) {
                $atts[$attachment->filename] = $attachment->getFilePath();
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
