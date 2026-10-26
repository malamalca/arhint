<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Mailer\Mailer;
use Throwable;

/**
 * Send a test message through the default e-mail transport and layout.
 */
class SendTestEmailCommand extends Command
{
    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'send_test_email';
    }

    /**
     * @inheritDoc
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return parent::buildOptionParser($parser)
            ->setDescription(__('Send a test e-mail to check the e-mail transport and the HTML layout.'))
            ->addArgument('recipient', [
                'help' => __('E-mail address the test message is sent to.'),
                'required' => true,
            ])
            ->addOption('subject', [
                'short' => 's',
                'help' => __('Subject of the message.'),
                'default' => __('Test e-mail'),
            ])
            ->addOption('format', [
                'short' => 'f',
                'help' => __('Format of the message.'),
                'choices' => ['html', 'text', 'both'],
                'default' => 'html',
            ]);
    }

    /**
     * Send the message.
     *
     * @param \Cake\Console\Arguments $args Console arguments
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $recipient = trim((string)$args->getArgument('recipient'));
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $io->error(__('"{0}" is not a valid e-mail address.', $recipient));

            return static::CODE_ERROR;
        }

        $subject = (string)$args->getOption('subject');
        $content = implode("\n", [
            __('This is a test message from {0}.', (string)Configure::read('App.title')),
            __('If you can read it, e-mail delivery and the e-mail layout work.'),
            __('Sent at {0}.', date('Y-m-d H:i:s')),
        ]);

        try {
            $email = new Mailer('default');
            $email
                ->setEmailFormat((string)$args->getOption('format'))
                ->setFrom([Configure::read('App.fromEmail.from') => Configure::read('App.fromEmail.name')])
                ->setTo($recipient)
                ->setSubject($subject)
                ->setViewVars(['emailTitle' => $subject])
                ->viewBuilder()
                    ->setTemplate('default');

            $email->deliver($content);
        } catch (Throwable $e) {
            $io->error(__('The test e-mail could not be sent: {0}', $e->getMessage()));

            return static::CODE_ERROR;
        }

        $io->success(__('The test e-mail has been sent to {0}.', $recipient));

        return static::CODE_SUCCESS;
    }
}
