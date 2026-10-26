<?php
use Cake\Routing\Router;

$loginUrl = Router::url(['prefix' => false, 'plugin' => null, 'controller' => 'Users', 'action' => 'login'], true);
$passwordUrl = Router::url(
    ['prefix' => false, 'plugin' => null, 'controller' => 'Users', 'action' => 'changePassword', $user->reset_key],
    true,
);

echo __('Hello {0},', $user->name) . PHP_EOL;
echo PHP_EOL;
echo __('An account has been created for you.') . PHP_EOL;
echo PHP_EOL;
echo __('Please follow this url to set your password:') . PHP_EOL;
echo '   ' . $passwordUrl . PHP_EOL;
echo PHP_EOL;
echo __('After that you can log in at:') . PHP_EOL;
echo '   ' . $loginUrl . PHP_EOL;
echo __('Username') . ': ' . $user->username . PHP_EOL;
echo PHP_EOL;
echo PHP_EOL;
echo __('Best Regards.') . PHP_EOL;
