<?php
use App\Mailer\ArhintMailer;
use Cake\Collection\Collection;
use Cake\Core\Configure;
use PhpOffice\PhpSpreadsheet\IOFactory;

$xlsFileName = $adrema->user_data['xls'] ?? null;

if (!$xlsFileName) {
    throw new Exception('Datoteka XLS ni naložena');
}

$attachment = (new Collection($adrema->form_attachments))->firstMatch(['id' => $adrema->user_data['xls']]);

if (!$attachment) {
    throw new Exception('Priponka v bazi ne obstaja');
}

$xlsFile = $attachment->getFilePath();
if (!file_exists($xlsFile)) {
    throw new Exception('Datoteka XLS ne obstaja na disku');
}

$spreadsheet = IOFactory::load($xlsFile);

if (!$spreadsheet) {
    throw new Exception('Napaka pri nalaganju XLS');
}

foreach ($addresses as $address) {
    if (!empty($address->contacts_email->email)) {
        $mailer = new ArhintMailer($this->getCurrentUser());
        $mailer
            ->setFrom($this->getCurrentUser()->email)
            ->setTo(Configure::read('debug') ? 'miha.nahtigal@arhim.si' : $address->contacts_email->email)
            ->setSubject('Vloga za projektne pogoje za gradnjo')
            ->setViewVars(['user' => $this->getCurrentUser(), 'address' => $address, 'data' => $adrema->user_data])
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
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        ob_start();
        $writer->save('php://output');
        $excelOutput = ob_get_clean();

        $atts[$attachment->filename] = ['data' => $excelOutput];

        // other attachemts
        foreach ($adrema->attachments as $attachment) {
            $atts[$attachment->filename] = $attachment->getFilePath();
        }

        $mailer->setAttachments($atts);

        $result = $mailer->deliver();

        if ($result) {
            printf('<p>' . __d('crm', 'Email successfully sent to "{0}"', $address->contacts_email->email) . '</p>');
        }
    }
}
