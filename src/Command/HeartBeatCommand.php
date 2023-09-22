<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Mailer\Mailer;

class HeartBeatCommand extends Command
{
    /**
     * Hourly heartbeat function
     *
     * @param \Cake\Console\Arguments $args Console arguments
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $users = $this->fetchTable('Users')->find()
            ->select()
            ->where(['active' => true, 'email_hourly' => true])
            ->all();

        foreach ($users as $user) {
            $emailPanels = [
                'title' => __('Intranet Notification'),
                'panels' => [],
            ];

            $event = new Event('App.HeartBeat.hourlyEmail', $this, ['panels' => $emailPanels, 'user' => $user]);
            EventManager::instance()->dispatch($event);

            if (!empty($event->getResult()['panels'])) {
                $emailPanels = $event->getResult()['panels'];
            }

            if (count($emailPanels['panels']) > 0) {
                $email = new Mailer('default');
                $email
                    ->setEmailFormat('html')
                    ->setTo($user->email)
                    ->setSubject(__('Intranet Report'))
                    ->setViewVars(['user' => $user, 'panels' => $emailPanels])
                    ->viewBuilder()
                        ->setTemplate('hourly')
                        ->addHelpers(['Lil.Lil']);

                $email->deliver();
            }
        }

        return 1;
    }
}
