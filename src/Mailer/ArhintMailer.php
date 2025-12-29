<?php
declare(strict_types=1);

namespace App\Mailer;

use App\Model\Entity\User;
use Cake\Mailer\Mailer;
use Cake\ORM\TableRegistry;
use InvalidArgumentException;

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
    public function __construct(array|string|null $config = null)
    {
        parent::__construct($config);

        if (isset($config['user']) && $config['user'] instanceof User) {
            $this->currentUser = $config['user'];
        } else {
            throw new InvalidArgumentException('User entity must be provided in Mailer config under "user" key.');
        }

        $this->setTransport('default');
    }

    /**
     * Render content and send email using configured transport.
     *
     * @param string $content Content.
     * @return array
     * @phpstan-return array{headers: string, message: string, ...}
     */
    public function deliver(?string $content = null): array
    {
        // setup email template
        $DocumentsTemplatesTable = TableRegistry::getTableLocator()->get('Documents.DocumentsTemplates');
        $layout = $DocumentsTemplatesTable->find()
            ->select()
            ->where(['owner_id' => $this->currentUser->get('company_id'), 'kind' => 'email', 'main' => 1])
            ->first();

        // user defined template exists
        if ($layout) {
            $this->setEmailFormat('html');
            $uniqueFile = TMP . uniqid() . '.php';
            file_put_contents($uniqueFile, $layout->body);

            if (!empty($content)) {
                // this is a functionality of TmpView
                // to mimic $content as ViewBlock
                $this->viewBuilder()
                    ->setVar('_contentBlock', $content);
            }

            $this->viewBuilder()
                ->setClassName('Tmp')
                ->setLayout(basename($uniqueFile, '.php'));
        }

        $companyLogoFilePath = dirname(APP) . DS . 'uploads' . DS . 'Contacts' . DS;
        $companyLogoFilePath .= $this->currentUser->get('company_id') . '.png';
        if (file_exists($companyLogoFilePath)) {
            $this->addAttachments([
                'logo.png' => [
                    'file' => $companyLogoFilePath,
                    'mimetype' => 'image/png',
                    'contentId' => 'embedded-logo',
                ],
            ]);
        }

        $result = parent::deliver((string)$content);

        // save email message to Sent IMAP folder
        $imap = $this->currentUser->getProperty('imap');
        if ($imap && $imap->url && $imap->username && $imap->password) {
            $mbox = imap_open($imap->url, $imap->username, $imap->password);
            if ($mbox) {
                imap_append(
                    $mbox,
                    $imap->url . ($imap->folder ?? 'Sent'),
                    $result['headers'] . "\r\n\r\n" . $result['message'],
                );
                imap_close($mbox);
            }
        }

        return $result;
    }
}
