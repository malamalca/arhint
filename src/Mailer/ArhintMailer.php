<?php
declare(strict_types=1);

namespace App\Mailer;

use App\Model\Entity\User;
use App\View\AppView;
use Cake\Mailer\Mailer;
use Cake\ORM\TableRegistry;

/**
 * @psalm-suppress UnusedClass
 */
class ArhintMailer extends Mailer
{
    protected User $currentUser;

    /**
     * Constructor
     *
     * @param array<string, mixed>|string|null $config Array of configs, or string to load configs from app.php
     */
    public function __construct(array|string|null $config = null, User $currentUser)
    {
        parent::__construct($config);
        $this->currentUser = $currentUser;
    }

    /**
     * Render content and send email using configured transport.
     *
     * @param string $contents Content.
     * @return array
     * @psalm-return array{headers: string, message: string}
     */
    public function deliver(string $contents = ''): array
    {
        // setup email template
        $DocumentsTemplatesTable = TableRegistry::getTableLocator()->get('Documents.DocumentsTemplates');
        $template = $DocumentsTemplatesTable->find()
            ->select()
            ->where(['owner_id' => $this->currentUser->get('company_id'), 'kind' => 'email', 'main' => 1])
            ->first();

        if ($template) {
            $this->setEmailFormat('html');
            $uniqueFile = TMP . uniqid() . '.php';
            file_put_contents($uniqueFile, $template->body);

            ob_start();
            include($uniqueFile);
            $contents = ob_get_contents();
            ob_end_clean();

            unlink($uniqueFile);
        }

        $result = parent::deliver($contents);

        // save email message to Sent IMAP folder
        $imap = $this->currentUser->getProperty('imap');
        if ($imap && $imap->url && $imap->username && $imap->password) {
            $mbox = imap_open($imap->url, $imap->username, $imap->password);
            if ($mbox) {
                imap_append(
                    $mbox,
                    $imap->url . ($imap->folder ?? 'Sent'),
                    $result['headers'] . "\r\n\r\n" . $result['message']
                );
                imap_close($mbox);
            }
        }

        return $result;
    }
}
