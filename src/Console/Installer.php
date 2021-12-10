<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.0.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Console;

if (!defined('STDIN')) {
    define('STDIN', fopen('php://stdin', 'r'));
}

use Cake\Auth\DefaultPasswordHasher;
use Cake\Datasource\ConnectionManager;
use Cake\Utility\Security;
use Cake\Utility\Text;
use Composer\Script\Event;
use Exception;
use Migrations\Migrations;

/**
 * Provides installation hooks for when this application is installed through
 * composer. Customize this class to suit your needs.
 */
class Installer
{
    /**
     * An array of directories to be made writable
     */
    public const WRITABLE_DIRS = [
        'logs',
        'tmp',
        'uploads',
        'tmp/cache',
        'tmp/cache/models',
        'tmp/cache/persistent',
        'tmp/cache/views',
        'tmp/sessions',
        'tmp/tests',
    ];

    /**
     * An array of plugins
     */
    public const PLUGINS = [
        'LilCrm',
        'LilExpenses',
        'LilInvoices',
        'LilProjects',
        'LilTasks',
    ];

    /**
     * Does some routine installation tasks so people don't have to.
     *
     * @param \Composer\Script\Event $event The composer event object.
     * @throws \Exception Exception raised by validator.
     * @return void
     */
    public static function postInstall(Event $event)
    {
        $io = $event->getIO();

        $rootDir = dirname(dirname(__DIR__));

        static::createAppLocalConfig($rootDir, $io);
        static::createWritableDirectories($rootDir, $io);

        static::setFolderPermissions($rootDir, $io);
        static::setSecuritySalt($rootDir, $io);

        static::setCookieKeyInFile($rootDir, $io);

        static::setDatabase($rootDir, $io);

        static::executeMigrations($rootDir, $io);
        static::createAdminUser($io);


        $class = 'Cake\Codeception\Console\Installer';
        if (class_exists($class)) {
            $class::customizeCodeceptionBinary($event);
        }
    }

    /**
     * Create config/app_local.php file if it does not exist.
     *
     * @param string $dir The application's root directory.
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @return void
     */
    public static function createAppLocalConfig($dir, $io)
    {
        $appLocalConfig = $dir . '/config/app_local.php';
        $appLocalConfigTemplate = $dir . '/config/app_local.example.php';
        if (!file_exists($appLocalConfig)) {
            copy($appLocalConfigTemplate, $appLocalConfig);
            $io->write('Created `config/app_local.php` file');
        }
    }

    /**
     * Create the `logs` and `tmp` directories.
     *
     * @param string $dir The application's root directory.
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @return void
     */
    public static function createWritableDirectories($dir, $io)
    {
        foreach (static::WRITABLE_DIRS as $path) {
            $path = $dir . '/' . $path;
            if (!file_exists($path)) {
                mkdir($path);
                $io->write('Created `' . $path . '` directory');
            }
        }
    }

    /**
     * Set globally writable permissions on the "tmp" and "logs" directory.
     *
     * This is not the most secure default, but it gets people up and running quickly.
     *
     * @param string $dir The application's root directory.
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @return void
     */
    public static function setFolderPermissions($dir, $io)
    {
        // ask if the permissions should be changed
        if ($io->isInteractive()) {
            $validator = function ($arg) {
                if (in_array($arg, ['Y', 'y', 'N', 'n'])) {
                    return $arg;
                }
                throw new Exception('This is not a valid answer. Please choose Y or n.');
            };
            $setFolderPermissions = $io->askAndValidate(
                '<info>Set Folder Permissions ? (Default to Y)</info> [<comment>Y,n</comment>]? ',
                $validator,
                10,
                'Y'
            );

            if (in_array($setFolderPermissions, ['n', 'N'])) {
                return;
            }
        }

        // Change the permissions on a path and output the results.
        $changePerms = function ($path) use ($io) {
            $currentPerms = fileperms($path) & 0777;
            $worldWritable = $currentPerms | 0007;
            if ($worldWritable == $currentPerms) {
                return;
            }

            $res = chmod($path, $worldWritable);
            if ($res) {
                $io->write('Permissions set on ' . $path);
            } else {
                $io->write('Failed to set permissions on ' . $path);
            }
        };

        $walker = function ($dir) use (&$walker, $changePerms) {
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                $path = $dir . '/' . $file;

                if (!is_dir($path)) {
                    continue;
                }

                $changePerms($path);
                $walker($path);
            }
        };

        $walker($dir . '/tmp');
        $changePerms($dir . '/tmp');
        $changePerms($dir . '/logs');
    }

    /**
     * Set the security.salt value in the application's config file.
     *
     * @param string $dir The application's root directory.
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @return void
     */
    public static function setSecuritySalt($dir, $io)
    {
        $newKey = hash('sha256', Security::randomBytes(64));
        static::setSecuritySaltInFile($dir, $io, $newKey, 'app_local.php');
    }

    /**
     * Set the security.salt value in a given file
     *
     * @param string $dir The application's root directory.
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @param string $newKey key to set in the file
     * @param string $file A path to a file relative to the application's root
     * @return void
     */
    public static function setSecuritySaltInFile($dir, $io, $newKey, $file)
    {
        $config = $dir . '/config/' . $file;
        $content = file_get_contents($config);

        $content = str_replace('__SALT__', $newKey, $content, $count);

        if ($count == 0) {
            $io->write('No Security.salt placeholder to replace.');

            return;
        }

        $result = file_put_contents($config, $content);
        if ($result) {
            $io->write('Updated Security.salt value in config/' . $file);

            return;
        }
        $io->write('Unable to update Security.salt value.');
    }

    /**
     * Set the APP_NAME value in a given file
     *
     * @param string $dir The application's root directory.
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @param string $appName app name to set in the file
     * @param string $file A path to a file relative to the application's root
     * @return void
     */
    public static function setAppNameInFile($dir, $io, $appName, $file)
    {
        $config = $dir . '/config/' . $file;
        $content = file_get_contents($config);
        $content = str_replace('__APP_NAME__', $appName, $content, $count);

        if ($count == 0) {
            $io->write('No __APP_NAME__ placeholder to replace.');

            return;
        }

        $result = file_put_contents($config, $content);
        if ($result) {
            $io->write('Updated __APP_NAME__ value in config/' . $file);

            return;
        }
        $io->write('Unable to update __APP_NAME__ value.');
    }

    /**
     * Set database properties
     *
     * @param string $dir The application's root directory.
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @return void
     */
    public static function setDatabase($dir, $io)
    {
        $io->write('ENTER DATABASE CONNECTION');

        $dbConnectSuccess = false;
        while (!$dbConnectSuccess) {
            $dbHost = $io->ask('<info>Enter database host ?</info> [<comment>localhost</comment>]? ', 'localhost');
            $dbName = $io->ask('<info>Enter database name ?</info> [<comment>arhint</comment>]? ', 'arhint');
            $dbUser = $io->ask('<info>Enter db user ?</info> ');
            $dbPassword = $io->ask('<info>Enter db password ?</info> ');

            $dbConnectSuccess = static::checkDbConnection($dbHost, $dbName, $dbUser, $dbPassword, $io);

            if ($dbConnectSuccess) {
                static::setDbConfigInFile($dbHost, $dbName, $dbUser, $dbPassword, $dir, 'app_local.php', $io);
            } else {
                $io->writeError('Cannot connect to mysql database. Please try again.');
            }
        }
    }

    /**
     * Execute migrations to create tables.
     *
     * @param string $dir The application's root directory.
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @return void
     */
    public static function executeMigrations($dir, $io)
    {
        if (!defined('ROOT')) {
            define('ROOT', $dir);
        }

        if (!defined('DS')) {
            define('DS', '/');
        }

        $migrations = new Migrations(['connection' => 'install']);
        $migrations->migrate();

        foreach (static::PLUGINS as $pluginName) {
            $migrations = new Migrations(['connection' => 'install', 'plugin' => $pluginName]);
            $migrations->migrate();
        }
    }

    /**
     * Create admin user.
     *
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @return void
     */
    public static function createAdminUser($io)
    {
        /** @var \Cake\Database\Connection $cm */
        $conn = ConnectionManager::get('install');

        if ($conn && $conn->connect()) {
            $io->write('CREATE ADMIN USER');

            $adminName = $io->ask('<info>Enter admin\'s display name ?</info> [<comment>Administrator</comment>]? ', 'Administrator');
            $adminEmail = $io->ask('<info>Enter admin\'s email ?</info> ');

            $adminUsername = $io->ask('<info>Enter admin\'s username ?</info> [<comment>admin</comment>]? ', 'admin');
            $adminPassword = $io->ask('<info>Enter admin\'s password ?</info> ');

            $companyTitle = $io->ask('<info>Enter Company Title ?</info> ');
            $companyMatNo = $io->ask('<info>Enter Company Assigned No. ?</info> ');
            $companyTaxNo = $io->ask('<info>Enter Company Tax No. ?</info> ');

            $adminPassword = (new DefaultPasswordHasher())->hash($adminPassword);

            $companyId = Text::uuid();

            $conn->execute(
                'INSERT INTO users (company_id, name, email, username, passwd, privileges) VALUES (:company, :title, :email, :username, :pass, 2)',
                ['company_id' => $companyId, 'title' => $adminName, 'email' => $adminEmail, 'username' => $adminUsername, 'pass' => $adminPassword],
                ['company_id' => 'string', 'title' => 'string', 'email' => 'string', 'username' => 'string', 'pass' => 'string']
            );

            $conn->execute(
                'INSERT INTO contacts (id, owner_id, kind, title, mat_no, tax_no) VALUES (:id, :owner_id, "C", :title, :mat_no, :tax_no)',
                ['id' => $companyId, 'owner_id' => $companyId, 'title' => $companyTitle, 'mat_no' => $companyMatNo, 'tax_no' => $companyTaxNo],
                ['id' => 'string', 'owner_id' => 'string', 'title' => 'string', 'mat_no' => 'string', 'tax_no' => 'string']
            );
        } else {
            $io->writeError('Cannot connect to mysql database to create admin user');
        }
    }

    /**
     * Try to connect to database
     *
     * @param string $dbHost Database host.
     * @param string $db Database name.
     * @param string $dbUser Mysql username.
     * @param string $dbPassword Mysql password.
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @return bool
     */
    public static function checkDbConnection($dbHost, $db, $dbUser, $dbPassword, $io)
    {
        try {
            ConnectionManager::drop('install');

            ConnectionManager::setConfig('install', [
                'className' => 'Cake\Database\Connection',
                'driver' => 'Cake\Database\Driver\Mysql',
                'persistent' => false,
                'host' => $dbHost,
                'username' => $dbUser,
                'password' => $dbPassword,
                'database' => $db,
                'timezone' => 'UTC',
                'flags' => [],
                'cacheMetadata' => true,
                'log' => false,
                'quoteIdentifiers' => true,
                'url' => null,
            ]);
            /** @var \Cake\Database\Connection $connection */
            $connection = ConnectionManager::get('install');
            $result = $connection->connect();

            return $result;
        } catch (Exception $connectionError) {
            $errorMsg = $connectionError->getMessage();
            $io->writeError($errorMsg);
        }

        return false;
    }

    /**
     * Set the dbconfig in a given file
     *
     * @param string $dbHost Database host.
     * @param string $dbName Database name.
     * @param string $dbUser Mysql username.
     * @param string $dbPassword Mysql password.
     * @param string $dir The application's root directory.
     * @param string $file A path to a file relative to the application's root
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @return void
     */
    public static function setDbConfigInFile($dbHost, $dbName, $dbUser, $dbPassword, $dir, $file, $io)
    {
        $config = $dir . '/config/' . $file;
        $content = file_get_contents($config);

        $content = str_replace('__DBHOST__', $dbHost, $content, $count);
        $content = str_replace('__DATABASE__', $dbName, $content, $count);
        $content = str_replace('__DBUSER__', $dbUser, $content, $count);
        $content = str_replace('__DBPASS__', $dbPassword, $content, $count);

        $result = file_put_contents($config, $content);
        if ($result) {
            $io->write('Updated Datasources.default values in config/' . $file);

            return;
        }
        $io->write('Unable to update Datasources.default values.');
    }

    /**
     * Set the security.cookieKey value in a given file
     *
     * @param string $dir The application's root directory.
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @param string $file A path to a file relative to the application's root
     * @return void
     */
    public static function setCookieKeyInFile($dir, $io, $file = 'app_local.php')
    {
        $config = $dir . '/config/' . $file;
        $content = file_get_contents($config);

        $content = str_replace('__COOKIEKEY__', hash('sha256', Security::randomBytes(64)), $content, $count);

        if ($count == 0) {
            $io->write('No Security.cookieKey placeholder to replace.');

            return;
        }

        $result = file_put_contents($config, $content);
        if ($result) {
            $io->write('Updated Security.cookieKey value in config/' . $file);

            return;
        }
        $io->write('Unable to update Security.cookieKey value.');
    }
}
