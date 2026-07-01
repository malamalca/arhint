<?php
declare(strict_types=1);

namespace Projects\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\ORM\TableRegistry;

/**
 * ProjectsLogs Controller — saves/edits/deletes project log entries via App.Logs.
 *
 * Logs are stored with model='Project', action='Comment', foreign_id=project_id.
 *
 * @property \App\Model\Table\LogsTable $Logs
 * @method \Cake\Datasource\Paging\PaginatedInterface paginate($object = null, array $settings = [])
 */
class ProjectsLogsController extends AppController
{
    /**
     * beforeFilterCallback
     *
     * @param \Cake\Event\EventInterface $event Event object
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $this->FormProtection->setConfig('unlockedActions', ['edit']);
    }

    /**
     * Edit method
     *
     * @param string|null $id Log id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $logsTable = TableRegistry::getTableLocator()->get('App.Logs');

        if ($id) {
            /** @var \App\Model\Entity\Log $log */
            $log = $logsTable->get($id);
        } else {
            $projectId = (string)$this->getRequest()->getQuery('project');
            /** @var \App\Model\Entity\Log $log */
            $log = $logsTable->newEmptyEntity();
            $log->model = 'Project';
            $log->action = 'Comment';
            $log->foreign_id = $projectId;
            $log->user_id = $this->getCurrentUser()->get('id');
        }

        $this->Authorization->Authorize($log);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $requestData = $this->getRequest()->getData();
            // CakePHP does not auto-parse raw JSON bodies; decode manually if needed
            if (
                empty($requestData) &&
                str_contains($this->getRequest()->getHeaderLine('Content-Type'), 'application/json')
            ) {
                $requestData = (array)json_decode((string)$this->getRequest()->getBody(), true);
            }

            /** @var \App\Model\Entity\Log $log */
            $log = $logsTable->patchEntity($log, $requestData);
            $isJson = str_contains($this->getRequest()->getHeaderLine('Content-Type'), 'application/json')
                || str_contains($this->getRequest()->getHeaderLine('Accept'), 'application/json')
                || $this->getRequest()->is('json');

            if ($logsTable->save($log)) {
                if ($this->getRequest()->is('json')) {
                    header('Content-Type: text/html');

                    $user = TableRegistry::getTableLocator()->get('Users')->get($log->user_id);
                    $this->set(compact('log', 'user'));
                    $projectsLog = $log; // alias for template compatibility
                    die((string)$this->render('/element/projects_log'));
                }

                if ($isJson) {
                    return $this->response
                        ->withType('application/json')
                        ->withStringBody((string)json_encode(['success' => true, 'id' => $log->id]));
                }

                $this->Flash->success(__d('projects', 'The projects log has been saved.'));

                $redirect = $this->getRequest()->getData(
                    'referer',
                    [
                        'controller' => 'Projects',
                        'action' => 'view',
                        $log->foreign_id,
                        '?' => ['tab' => 'logs'],
                    ],
                );

                return $this->redirect($redirect);
            } else {
                if ($isJson) {
                    return $this->response
                        ->withStatus(422)
                        ->withType('application/json')
                        ->withStringBody(
                            (string)json_encode(['success' => false, 'errors' => $log->getErrors()]),
                        );
                }

                $this->Flash->error(__d('projects', 'The projects log could not be saved. Please, try again.'));
            }
        }

        /** @var \App\Model\Entity\Log $log */
        $project = TableRegistry::getTableLocator()->get('Projects.Projects')->get($log->foreign_id);
        $projectsLog = $log; // alias for template compatibility
        $this->set(compact('log', 'projectsLog', 'project'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Log id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete', 'get']);

        $logsTable = TableRegistry::getTableLocator()->get('App.Logs');
        /** @var \App\Model\Entity\Log $log */
        $log = $logsTable->get($id);
        $this->Authorization->Authorize($log);

        $projectId = $log->foreign_id;

        if ($logsTable->delete($log)) {
            $this->Flash->success(__d('projects', 'The projects log has been deleted.'));
        } else {
            $this->Flash->error(__d('projects', 'The projects log could not be deleted. Please, try again.'));
        }

        $redirect = $this->getRequest()->getQuery(
            'redirect',
            [
                'controller' => 'Projects',
                'action' => 'view',
                $projectId,
                '?' => ['tab' => 'logs'],
            ],
        );

        return $this->redirect($redirect);
    }
}
