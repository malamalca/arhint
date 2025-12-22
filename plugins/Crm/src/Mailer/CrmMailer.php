<?php
declare(strict_types=1);

namespace Crm\Mailer;

use App\Model\Entity\User;
use Cake\Collection\Collection;
use Cake\Core\Configure;
use Cake\Mailer\Mailer;
use Crm\Model\Entity\Adrema;
use Crm\Model\Entity\AdremasContact;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * @psalm-suppress UnusedClass
 */
class CrmMailer extends Mailer
{
    /**
     * Send adrema
     *
     * @param \Crm\Model\Entity\Contact $address Contact entity
     * @param \Crm\Model\Entity\Adrema $adrema Adrema entity
     * @return void
     */
    public function sloMnenja(User $sender, AdremasContact $address, Adrema $adrema): void
    {
        $this
            ->setFrom($sender->email)
            ->setTo(Configure::read('debug') ? 'miha.nahtigal@arhim.si' : $address->contacts_email->email)
            ->setSubject('Vloga za mnenje za gradnjo')
            ->setViewVars(['user' => $sender, 'address' => $address, 'data' => $adrema->user_data])
            ->setEmailFormat('both')
            ->viewBuilder()
                ->setTemplate('Crm.slo_mnenja')
                ->addHelper('Html');

        // excel attachment
        $attachment = (new Collection($adrema->form_attachments))->firstMatch(['id' => $adrema->user_data['xls']]);
        $spreadsheet = IOFactory::load($attachment->getFilePath());

        //change it
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('C32', $address->contact->title);
        $sheet->setCellValue('C33', (string)$address->contacts_address);
        $sheet->setCellValue('C34', (string)($address->user_data['opis'] ?? ''));

        $sheet->setCellValue('C38', (string)($address->user_data['stPogojev'] ?? ''));
        $sheet->setCellValue('C39', (string)($address->user_data['datumPogojev'] ?? ''));

        //write it again to Filesystem with the same name (=replace)
        //$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Tcpdf');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        ob_start();
        $writer->save('php://output');
        $excelOutput = ob_get_clean();

        $atts = [];
        $atts[$attachment->filename] = ['data' => $excelOutput];

        // other attachemts
        foreach ((array)$address->attachments as $attachment) {
            $atts[$attachment->filename] = $attachment->getFilePath();
        }
        foreach ($adrema->attachments as $attachment) {
            $atts[$attachment->filename] = $attachment->getFilePath();
        }

        $this->setAttachments($atts);
    }
}
