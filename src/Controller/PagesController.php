<?php
declare(strict_types=1);

namespace App\Controller;

use ArrayObject;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\View\Exception\MissingTemplateException;
use Cake\View\View;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Lil\View\Helper\LilHelper;

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

        $noteCount = 0;
        if ($this->hasCurrentUser()) {
            $noteCount = TableRegistry::getTableLocator()->get('DashboardNotes')->find()
                ->select()
                ->where(['user_id' => $this->getCurrentUser()->id])
                ->count();
        }

        $noteOffset = $this->request->getQuery('offset', 1);
        $nextNoteOffset = $noteOffset > 1 ? $noteOffset - 1 : 1;
        $prevNoteOffset = ($noteOffset == $noteCount ? $noteCount : $noteOffset + 1);

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
                'add-note' => [
                    'title' => __('Add Note'),
                    'visible' => $this->getCurrentUser()->hasRole('editor'),
                    'url' => [
                        'plugin' => false,
                        'controller' => 'DashboardNotes',
                        'action' => 'edit',
                    ],
                ],
            ],
            'panels' => [
                'notes' => !$this->hasCurrentUser() || !$this->getCurrentUser()->hasRole('editor') ? null : [
                    'params' => ['class' => 'dashboard-panel', 'id' => 'DashboardNotes'],
                    'lines' => [
                        '<h5>' . __('Dashboard Note') . '<span class="actions" style="display: block; float: right">' .
                            sprintf(
                                '<a href="%2$s" class="btn-small"%3$s>%1$s</a>',
                                __('Prev'),
                                Router::url(['?' => ['offset' => $prevNoteOffset]]),
                                $prevNoteOffset == $noteOffset ? ' disabled' : '',
                            ) . ' ' .
                            sprintf(
                                '<a href="%2$s" class="btn-small"%3$s>%1$s</a>',
                                __('Next'),
                                Router::url(['?' => ['offset' => $nextNoteOffset]]),
                                $nextNoteOffset == $noteOffset ? ' disabled' : '',
                            ) .
                        '</span></h5>',
                    ],
                ],
            ],
        ];

        if ($this->hasCurrentUser() && $this->getCurrentUser()->hasRole('editor')) {
            $DashboardNotes = TableRegistry::getTableLocator()->get('DashboardNotes');
            $lastNote = $DashboardNotes->find()
                ->select()
                ->where(['user_id' => $this->getCurrentUser()->id])
                ->orderBy('created DESC')
                ->limit(1)
                ->page((int)$noteOffset)
                ->first();

            $LilHelper = new LilHelper(new View());

            if ($lastNote) {
                $dashboardPanels['panels']['notes']['lines'][] = sprintf(
                    '<div class="details">%1$s | %2$s</div>',
                    __('Created {0}', $lastNote->created),
                    $LilHelper->Link(
                        __('edit'),
                        ['controller' => 'DashboardNotes', 'action' => 'edit', $lastNote->id],
                        [],
                    ),
                );

                $converter = new GithubFlavoredMarkdownConverter([
                    'html_input' => 'strip',
                    'allow_unsafe_links' => false,
                ]);

                $dashboardPanels['panels']['notes']['lines'][] = (string)$converter->convert($lastNote->note);
            } else {
                $dashboardPanels['panels']['notes']['lines'][] = '<p>' .
                    $LilHelper->link(__('No notes found. [$1Add] your first note.'), [
                        1 => [['controller' => 'DashboardNotes', 'action' => 'edit']],
                    ]) .
                    '</p>';
            }
        }

        $event = new Event('App.dashboard', $this, ['panels' => new ArrayObject($dashboardPanels)]);
        EventManager::instance()->dispatch($event);

        $this->set(['panels' => (array)$event->getResult()['panels']]);
    }
}
