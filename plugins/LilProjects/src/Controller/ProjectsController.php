<?php
declare(strict_types=1);

namespace LilProjects\Controller;

use Cake\ORM\TableRegistry;

/**
 * Projects Controller
 *
 * @property \LilProjects\Model\Table\ProjectsTable $Projects
 *
 * @method \LilProjects\Model\Entity\Project[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ProjectsController extends AppController
{
    /**
     * beforeFilter method
     *
     * @param \Cake\Event\Event $event Event object.
     * @return void
     */
    /*public function beforeFilter(Event $event)
    {
        if ($this->getRequest()->getParam('_ext') == 'txt') {
            $this->Auth->allow('index');
        }
        parent::beforeFilter($event);
        if (
            (in_array($this->getRequest()->getParam('action'), ['index']) &&
            in_array($this->getRequest()->getParam('_ext'), ['txt']) &&
            !$this->Auth->user('id'))
        ) {
            if (!isset($_SERVER['PHP_AUTH_USER'])) {
                header('WWW-Authenticate: Basic realm="Lil Projects"');
                header('HTTP/1.0 401 Unauthorized');
                echo __d('lil_projects', 'Invalid username/password!');
                exit;
            } else {
                $user = $this->Auth->identify();
                if (!$user) {
                    unset($_SERVER['PHP_AUTH_USER']);
                    header('HTTP/1.0 401 Unauthorized');
                    echo __d('lil_projects', 'Invalid username/password!');
                    exit;
                } else {
                    $this->Auth->setUser($user);
                }
            }
        }
    }*/

    /**
     * Index method
     *
     * @return \Cake\Http\Response|void
     */
    public function index()
    {
        $projects = $this->Authorization->applyScope($this->Projects->find())
            ->select()
            ->order('title')
            ->all();

        $q = TableRegistry::get('LilProjects.ProjectsWorkhours')->find(
            'list',
            ['keyField' => 'project_id', 'valueField' => 'duration']
        );
        $workhours = $q->select(['project_id', 'duration' => $q->func()->sum('duration')])
            ->group('project_id')
            ->toArray();

        $this->set(compact('projects', 'workhours'));
    }

    /**
     * Map method
     *
     * @return \Cake\Http\Response|void
     */
    public function map()
    {
        $projects = $this->Authorization->applyScope($this->Projects->find())
            ->select()
            ->where([
                'active' => true,
            ])
            ->order('title')
            ->all();

        $this->set(compact('projects'));
    }

    /**
     * View method
     *
     * @param string|null $id Project id.
     * @param string $size Image size.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function picture($id = null, $size = 'normal')
    {
        $project = $this->Projects->get($id);

        $this->Authorization->authorize($project, 'view');

        switch ($size) {
            case 'thumb':
                $im = imagecreatefromstring(base64_decode($project->ico));
                $width = imagesx($im);
                $height = imagesy($im);
                $ratio = 50 / $height;

                $newWidth = (int)round($width * $ratio);
                $newHeight = 50;

                $newImage = imagecreatetruecolor($newWidth, $newHeight);
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
                imagecopyresampled($newImage, $im, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                if (!empty($project->colorize)) {
                    imagefilter(
                        $newImage,
                        IMG_FILTER_COLORIZE,
                        hexdec(substr($project->colorize, 0, 2)),
                        hexdec(substr($project->colorize, 2, 2)),
                        hexdec(substr($project->colorize, 4, 2))
                    );
                }
                imagedestroy($im);

                $im = $newImage;

                ob_start();
                imagepng($im);
                $imageData = ob_get_contents();
                ob_end_clean();
                imagedestroy($im);

                break;
            default:
                $imageData = base64_decode($project->ico);
        }

        $response = $this->response;
        $response = $response->withStringBody($imageData);
        $response = $response->withType('png');

        return $response;
    }

    /**
     * View method
     *
     * @param string|null $id Project id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $project = $this->Projects->get($id);

        $this->Authorization->authorize($project);

        $logs = TableRegistry::get('LilProjects.ProjectsLogs')->find()
            ->select()
            ->where(['project_id' => $id])
            ->order('ProjectsLogs.created DESC')
            ->contain(['Users'])
            ->limit(5)
            ->all();

        if ($this->getRequest()->is('ajax')) {
            $this->viewBuilder()->setTemplate('map_popup');
        }

        $this->set(compact('project', 'logs'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $this->setAction('edit');
    }

    /**
     * Edit method
     *
     * @param string|null $id Project id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        if ($id) {
            $project = $this->Projects->get($id);
        } else {
            $project = $this->Projects->newEmptyEntity();
            $project->owner_id = $this->getCurrentUser()->get('company_id');
        }

        $this->Authorization->authorize($project);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $project = $this->Projects->patchEntity($project, $this->getRequest()->getData());

            $icoFile = $this->getRequest()->getData('ico');
            if (is_array($icoFile)) {
                if (isset($icoFile['error'])) {
                    if ($icoFile['error'] == UPLOAD_ERR_OK) {
                        $project->ico = base64_encode(file_get_contents($icoFile['tmp_name']));
                    }
                    if ($icoFile['error'] == UPLOAD_ERR_NO_FILE) {
                        unset($project->ico);
                        $project->setDirty('ico', false);
                    }
                }
            }

            if ($this->Projects->save($project)) {
                $this->Flash->success(__d('lil_projects', 'The project has been saved.'));
                $redirect = $this->getRequest()->getData('redirect');
                if (!empty($redirect)) {
                    return $this->redirect(base64_decode($redirect));
                }

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__d('lil_projects', 'The project could not be saved. Please, try again.'));
        }
        $this->set(compact('project'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Project id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete', 'get']);
        $project = $this->Projects->get($id);
        if ($this->Projects->delete($project)) {
            $this->Flash->success(__d('lil_projects', 'The project has been deleted.'));
        } else {
            $this->Flash->error(__d('lil_projects', 'The project could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
