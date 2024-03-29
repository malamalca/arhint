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
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use ArrayObject;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\View\Exception\MissingTemplateException;

/**
 * Static content controller
 *
 * This controller will render views from templates/Pages/
 *
 * @link https://book.cakephp.org/4/en/controllers/pages-controller.html
 */
class PagesController extends AppController
{
    /**
     * Displays a view
     *
     * @param string ...$path Path segments.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\ForbiddenException When a directory traversal attempt.
     * @throws \Cake\View\Exception\MissingTemplateException When the view file could not
     *   be found and in debug mode.
     * @throws \Cake\Http\Exception\NotFoundException When the view file could not
     *   be found and not in debug mode.
     * @throws \Cake\View\Exception\MissingTemplateException In debug mode.
     */
    public function display(string ...$path): ?Response
    {
        $this->Authorization->skipAuthorization();

        if (!$path) {
            return $this->redirect('/');
        }
        if (in_array('..', $path, true) || in_array('.', $path, true)) {
            throw new ForbiddenException();
        }
        $page = $subpage = null;

        if (!empty($path[0])) {
            $page = $path[0];
        }
        if (!empty($path[1])) {
            $subpage = $path[1];
        }
        $this->set(compact('page', 'subpage'));

        try {
            return $this->render(implode('/', $path));
        } catch (MissingTemplateException $exception) {
            if (Configure::read('debug')) {
                throw $exception;
            }
            throw new NotFoundException();
        }
    }

    /**
     * Displays a report
     *
     * @param string $reportName Report name
     * @param string $fileName Temporary file name
     * @return \Cake\Http\Response|void
     * @throws \Cake\Http\Exception\NotFoundException When the file could not be found
     */
    public function report(string $reportName, string $fileName)
    {
        $this->Authorization->skipAuthorization();

        $fileName = preg_replace('/[^a-z0-9]+/', '-', strtolower($fileName));
        if (!file_exists(constant('TMP') . $fileName . '.pdf')) {
            throw new NotFoundException();
        }
        $this->set('pdfFileName', $fileName);
    }

    /**
     * Displays a report
     *
     * @param string $fileName Report name
     * @return \Cake\Http\Response
     * @throws \Cake\Http\Exception\NotFoundException When the view file could not be found.
     */
    public function pdf(string $fileName): Response
    {
        $this->Authorization->skipAuthorization();

        //$fileName = preg_replace('/[^a-z0-9_() ]+/', '-', strtolower(urldecode($fileName)));

        if (!file_exists(constant('TMP') . $fileName . '.pdf')) {
            throw new NotFoundException();
        }

        $fileContents = (string)file_get_contents(constant('TMP') . $fileName . '.pdf');
        $result = $this->response->withStringBody($fileContents);
        $result = $result->withType('pdf');

        if ($this->getRequest()->getParam('_ext') == 'pdf') {
            $result = $result->withDownload($fileName . '.pdf');
        }

        return $result;
    }

    /**
     * Displays a dashboard
     *
     * @return \Cake\Http\Response|void
     * @throws \Cake\Http\Exception\NotFoundException When the view file could not be found.
     */
    public function dashboard()
    {
        $this->Authorization->skipAuthorization();

        $dashboardPanels = new ArrayObject([
            'title' => '&nbsp;',
            'menu' => [
                'sign' => [
                    'title' => __('PDF Sign'),
                    'visible' => true,
                    'url' => [
                        'plugin' => false,
                        'controller' => 'Utils',
                        'action' => 'pdfSign',
                    ],
                ],
                'merge' => [
                    'title' => __('PDF Merge'),
                    'visible' => true,
                    'url' => [
                        'plugin' => false,
                        'controller' => 'Utils',
                        'action' => 'pdfMerge',
                    ],
                ],
                'splice' => [
                    'title' => __('PDF Splice'),
                    'visible' => true,
                    'url' => [
                        'plugin' => false,
                        'controller' => 'Utils',
                        'action' => 'pdfSplice',
                    ],
                ],
            ],
            'panels' => [],
        ]);

        $event = new Event('App.dashboard', $this, ['panels' => $dashboardPanels]);
        EventManager::instance()->dispatch($event);

        $this->set(['panels' => (array)$event->getResult()['panels']]);
    }
}
