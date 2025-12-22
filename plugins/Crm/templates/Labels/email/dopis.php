<?php
use App\Mailer\ArhintMailer;

foreach ($addresses as $address) {
    if (!empty($address->contacts_email->email)) {
        $mailer = new ArhintMailer($this->getCurrentUser());
        $mailer
            ->setFrom($this->getCurrentUser()->email)
            ->setTo($address->contacts_email->email)
            ->setSubject($adrema->user_data['subject'] ?? __d('crm', 'Adrema: {0}', $adrema->title))
            ->setViewVars([
                'user' => $this->getCurrentUser(),
                'address' => $address,
                'adrema' => $adrema,
                'data' => $adrema->user_data,
            ])
            ->setEmailFormat('both')
            ->viewBuilder()
                ->setTemplate('Crm.dopis')
                ->addHelper('Html');

        $result = $mailer->deliver();

        if ($result) {
            printf('<p>' . __d('crm', 'Email successfully sent to "{0}"', $address->contacts_email->email) . '</p>');
        }
    }
}
