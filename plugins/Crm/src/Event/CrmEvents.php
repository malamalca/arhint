<?php
declare(strict_types=1);

namespace Crm\Event;

use ArrayObject;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Crm\Lib\CrmSidebar;
use Exception;
use Lil\Lib\LilForm;

class CrmEvents implements EventListenerInterface
{
    /**
     * Return implemented events.
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [
            'App.HeartBeat.hourly' => 'hourly',
            'View.beforeRender' => 'addScripts',
            'Lil.Sidebar.beforeRender' => 'modifySidebar',
            'Lil.Form.Documents.Documents.email' => 'addAutocompleteToEmail',
        ];
    }

    /**
     * Hourly
     *
     * @param \Cake\Event\Event $event Event object.
     * @return void
     */
    public function hourly(Event $event): void
    {
        /** @var \App\Model\Entity\User $user */
        $user = $event->getData('user');

        $imap = $user->getProperty('imap');
        if ($imap && $imap->url && $imap->username && $imap->password) {
            $mbox = imap_open($imap->url, $imap->username, $imap->password);
            if ($mbox) {
                $MC = imap_check($mbox);
                if (!$MC) {
                    throw new Exception('Failure Checking Imap');
                }

                $showMessages = 100;
                $totalMessages = $MC->Nmsgs;

                $firstMessageIndex = $totalMessages - $showMessages + 1;
                if ($firstMessageIndex < 1) {
                    $firstMessageIndex = 1;
                }

                $emails = (array)imap_fetch_overview($mbox, $firstMessageIndex . ':' . $totalMessages, 0);

                /** @var \Crm\Model\Table\ContactsLogsTable $ContactsLogsTable */
                $ContactsLogsTable = TableRegistry::getTableLocator()->get('Crm.ContactsLogs');
                $ContactsEmailsTable = TableRegistry::getTableLocator()->get('Crm.ContactsEmails');

                $contactsLogArray = [];
                foreach ($emails as $overview) {
                    preg_match_all(
                        '/(?|(?|"([^"]+)"|([^<@]+)) ?<(.+?)>|()(.+?))(?:$|, ?)/',
                        $overview->from,
                        $emailsDecoded,
                        PREG_SET_ORDER
                    );

                    $contactsEmails = $ContactsEmailsTable->find()
                        ->select('contact_id')
                        ->where(['email' => $emailsDecoded[0][2]])
                        ->all();

                    $fromEmail = empty($emailsDecoded[0][1]) ?
                        $emailsDecoded[0][2] :
                        (h(iconv_mime_decode($emailsDecoded[0][1])) . '<' . $emailsDecoded[0][2] . '>');

                    foreach ($contactsEmails as $contactEmail) {
                        $contactsLog = $ContactsLogsTable->newEmptyEntity();
                        $contactsLog->contact_id = $contactEmail->contact_id;
                        $contactsLog->kind = 'E';
                        $contactsLog->email_uid = $overview->uid;
                        $contactsLog->descript =
                            'From: ' . $fromEmail . PHP_EOL .
                            'Subject: ' . iconv_mime_decode($overview->subject) . PHP_EOL .
                            PHP_EOL .
                            sprintf(
                                'https://webmail.arhim.si/?_task=mail&_uid=%s&_mbox=INBOX&_action=show',
                                $overview->uid
                            );

                        $contactsLogArray[] = $contactsLog;
                    }
                }

                try {
                    $ContactsLogsTable->saveMany($contactsLogArray);
                } catch (Exception $e) {
                }

                imap_close($mbox);
            }
        }
    }

    /**
     * Add autocomplete func to email field
     *
     * @param \Cake\Event\Event $event Event.
     * @param \Lil\Lib\LilForm $form Form array
     * @return void
     */
    public function addAutocompleteToEmail(Event $event, LilForm $form): void
    {
        $link = Router::url([
            'plugin' => 'Crm',
            'controller' => 'Contacts',
            'action' => 'autocomplete-email',
        ], true);

        $form->form['post'] .= '<script type="text/javascript">' . PHP_EOL .
            sprintf('M.Autocomplete.init($("#to").get(0), {' .
                'onSearch: (text, autocomplete) => {$.get("%s" + "?term=" + text)' .
                '.done(function(data) {autocomplete.setMenuItems(data);})}});', $link) . PHP_EOL .
            '</script>' . PHP_EOL;
    }

    /**
     * Add css script to main layout.
     *
     * @param \Cake\Event\Event $event Event.
     * @return void
     */
    public function addScripts(Event $event): void
    {
        /** @var \App\View\AppView $view */
        $view = $event->getSubject();
        $view->append('script');
        echo $view->Html->css('Crm.crm');
        $view->end();

        if ($view->getRequest()->getParam('plugin') == 'Crm') {
            $view->set('admin_title', __d('crm', 'Costumers'));
        }
    }

    /**
     * Modify Lil sidebar.
     *
     * @param \Cake\Event\Event $event Event.
     * @param \ArrayObject $sidebar Sidebar.
     * @return void
     */
    public function modifySidebar(Event $event, ArrayObject $sidebar): void
    {
        if (Configure::read('Crm.showSidebar')) {
            CrmSidebar::setAdminSidebar($event, $sidebar);
        }
    }
}
