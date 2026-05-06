<?php
declare(strict_types=1);

namespace App\Controller;

use App\Lib\AIAssistant;
use ArrayObject;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Routing\Router;
use Cake\View\Exception\MissingTemplateException;
use Cake\View\Helper\FormHelper;
use Cake\View\View;

/**
 * Static content controller
 *
 * This controller will render views from templates/Pages/
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

        $FormHelper = new FormHelper(new View());
        $aiConfig = $this->hasCurrentUser() ? $this->getCurrentUser()->getProperty('ai_assistant') : null;
        if (!$aiConfig || empty($aiConfig->provider)) {
            $formAi =
                '<div class="ai-assistant-warning">' .
                '<p>' . __('AI assistant is not configured for your account.') . '</p>' .
                '<p>' . __('To enable it, add an <code>ai_assistant</code> to your user profile:') . '</p>' .
                '<pre>{"provider": "openai", "api_key": "sk-...", "model": "gpt-4o"}</pre>' .
                '<p>' . __('For a local/custom provider, use:') . '</p>' .
                '<pre>{"provider": "local", "url": "http://...", "model": "model-name"}</pre>' .
                '</div>';
        } elseif (!(new AIAssistant($this->getCurrentUser()))->isAvailable()) {
            $formAi =
                '<div class="ai-assistant-warning">' .
                '<p>' . __('AI assistant host is currently not available.') . '</p>' .
                '</div>';
        } else {
            $formAi =
                '<div id="AIAssistantMessages" class="ai-assistant-messages"></div>' .
                $FormHelper->create(
                    null,
                    ['url' => ['controller' => 'Ai', 'action' => 'chat'], 'id' => 'AIAssistantForm'],
                ) .
                $FormHelper->textarea(
                    'message',
                    ['id' => 'AIAssistantInput', 'placeholder' => __('Ask your AI assistant...'), 'rows' => 2],
                ) .
                '<div class="ai-assistant-actions">' .
                $FormHelper->button(__('Send'), ['id' => 'AIAssistantSend']) .
                ' <a href="#" id="AIAssistantClear" class="btn-small">' . __('Clear') . '</a>' .
                '</div>' .
                $FormHelper->end();
        }

        $dashboardPanels = [
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
            'panels' => [
                'ai-assistant' => [
                    'params' => [
                        'class' => 'dashboard-panel',
                        'id' => 'DashboardAIAssistant',
                        'data-chat-url' => Router::url(['controller' => 'Ai', 'action' => 'chat']),
                        'data-chat-status-url' => Router::url(['controller' => 'Ai', 'action' => 'chatStatus']),
                        'data-clear-url' => Router::url(['controller' => 'Ai', 'action' => 'clearHistory']),
                        'data-worker-status-url' => Router::url(['controller' => 'Ai', 'action' => 'workerStatus']),
                    ],
                    'lines' => [
                        '<h5>' . __('AI Assistant') . '</h5>',
                        '<p>' . __('Ask your AI assistant to help you with various tasks.') . '</p>',
                        '<p>' . $formAi . '</p>',
                    ],
                ],
            ],
        ];

        $event = new Event('App.dashboard', $this, ['panels' => new ArrayObject($dashboardPanels)]);
        EventManager::instance()->dispatch($event);

        $this->set(['panels' => (array)$event->getResult()['panels']]);
    }
}
