<?php
declare(strict_types=1);

namespace App\Mailer;

use App\Model\Entity\User;
use Cake\Mailer\Mailer;

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
     * @param string $content Content.
     * @return array
     * @psalm-return array{headers: string, message: string}
     */
    public function deliver(string $content = ''): array
    {
        $result = parent::deliver($content);

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
