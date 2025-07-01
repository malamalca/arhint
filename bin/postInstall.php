#!/usr/bin/php -q
<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Console\Installer;

// Build the runner with an application and root executable name.
Installer::postUpdate(null);