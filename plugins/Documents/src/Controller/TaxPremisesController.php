<?php
declare(strict_types=1);

namespace Documents\Controller;

use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Documents\Lib\FursClient;
use Documents\Lib\FursXml;
use Throwable;

/**
 * TaxPremises Controller - business premises used for FURS tax confirmation of invoices.
 *
 * @property \Documents\Model\Table\TaxPremisesTable $TaxPremises
 */
class TaxPremisesController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $taxPremises = $this->Authorization->applyScope($this->TaxPremises->find(), 'index')
            ->orderBy('TaxPremises.no')
            ->all();

        /** @var \Documents\Model\Table\TaxCertificatesTable $TaxCertificates */
        $TaxCertificates = TableRegistry::getTableLocator()->get('Documents.TaxCertificates');
        $certificate = $TaxCertificates->findForUser((string)$this->getCurrentUser()->get('id'));

        $this->set(compact('taxPremises', 'certificate'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Tax premise id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        if (empty($id)) {
            $taxPremise = $this->TaxPremises->newEmptyEntity();
            $taxPremise->owner_id = $this->getCurrentUser()->get('company_id');
            $taxPremise->kind = 'RL';
            $taxPremise->sw_taxno = Configure::read('Documents.furs.vendorTaxNo');
        } else {
            $taxPremise = $this->TaxPremises->get($id);
        }

        $this->Authorization->authorize($taxPremise);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $taxPremise = $this->TaxPremises->patchEntity($taxPremise, $this->getRequest()->getData());
            $taxPremise->owner_id = $this->getCurrentUser()->get('company_id');

            if ($this->TaxPremises->save($taxPremise)) {
                $this->Flash->success(__d('documents', 'The business premise has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__d('documents', 'The business premise could not be saved. Please, try again.'));
        }

        $this->set(compact('taxPremise'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Tax premise id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete', 'get']);

        $taxPremise = $this->TaxPremises->get($id);
        $this->Authorization->authorize($taxPremise);

        /** @var \Documents\Model\Table\DocumentsCountersTable $DocumentsCounters */
        $DocumentsCounters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');
        $inUse = $DocumentsCounters->exists(['tax_premise_id' => $taxPremise->id]);

        if ($inUse) {
            $this->Flash->error(__d('documents', 'The business premise is used by a counter and cannot be deleted.'));
        } elseif ($taxPremise->active && !$taxPremise->closed) {
            $this->Flash->error(__d('documents', 'Registered business premise must be closed before deleting.'));
        } elseif ($this->TaxPremises->delete($taxPremise)) {
            $this->Flash->success(__d('documents', 'The business premise has been deleted.'));
        } else {
            $this->Flash->error(__d('documents', 'The business premise could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Register (or close, if premise is marked closed) business premise at FURS.
     *
     * @param string|null $id Tax premise id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function register(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post']);

        $taxPremise = $this->TaxPremises->get($id);
        $this->Authorization->authorize($taxPremise);

        /** @var \Documents\Model\Table\TaxCertificatesTable $TaxCertificates */
        $TaxCertificates = TableRegistry::getTableLocator()->get('Documents.TaxCertificates');
        $client = $TaxCertificates->clientForUser((string)$this->getCurrentUser()->get('id'));

        if (!$client) {
            $this->Flash->error(__d('documents', 'Please upload your tax certificate first.'));

            return $this->redirect(['action' => 'certificate']);
        }

        /** @var \Crm\Model\Entity\Contact $company */
        $company = TableRegistry::getTableLocator()->get('Crm.Contacts')->get($taxPremise->owner_id);

        $error = $this->TaxPremises->register($taxPremise, $client, (string)$company->tax_no);
        if ($error === null) {
            $this->Flash->success(__d('documents', 'The business premise has been registered.'));
        } else {
            $this->Flash->error(__d('documents', 'Registration failed') . ': ' . $error);
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Check the current user's certificate by sending an echo request to FURS.
     *
     * @return \Cake\Http\Response|null
     */
    public function testCertificate(): ?Response
    {
        $this->getRequest()->allowMethod(['post']);
        $this->Authorization->skipAuthorization();

        /** @var \Documents\Model\Table\TaxCertificatesTable $TaxCertificates */
        $TaxCertificates = TableRegistry::getTableLocator()->get('Documents.TaxCertificates');
        $userId = (string)$this->getCurrentUser()->get('id');

        $ok = false;
        $details = [];
        try {
            $client = $TaxCertificates->clientForUser($userId);

            if (!$client) {
                $details[] = __d('documents', 'Please upload your tax certificate first.');
            } else {
                $message = 'arhim-' . bin2hex(random_bytes(4));
                $response = $client->echo($message);
                if ($response === $message) {
                    $ok = true;
                    $details[] = __d('documents', 'Certificate is accepted by the tax authority.');
                } else {
                    $details[] = __d('documents', 'Unexpected echo response') . ': ' . $response;
                    Log::error('FURS echo mismatch: sent "' . $message . '", received "' . $response . '"', 'furs');
                }
            }
        } catch (Throwable $e) {
            $details[] = __d('documents', 'Certificate check failed') . ': ' . $e->getMessage();
            Log::error('FURS echo error: ' . $e::class . ': ' . $e->getMessage() . "\n" . $e->getTraceAsString(), 'furs');
        }

        // the log can be silently unwritable (e.g. owned by another user), so show where it goes
        $logFile = LOGS . 'error.log';
        $details[] = sprintf(
            'Log: %s (%s)',
            $logFile,
            is_file($logFile) ?
                (is_writable($logFile) ? 'writable' : 'NOT writable by ' . get_current_user()) :
                (is_writable(LOGS) ? 'does not exist, directory writable' : 'directory NOT writable'),
        );

        $details[] = 'Log engines: ' . implode(', ', Log::configured()) . '; files in ' . LOGS . ': ' .
            implode(', ', array_map('basename', glob(LOGS . '*') ?: []));

        $checkResult = ['ok' => $ok, 'text' => date('Y-m-d H:i:s') . "\n" . implode("\n", $details)];
        $certificate = $TaxCertificates->findForUser($userId) ?? $TaxCertificates->newEmptyEntity();
        $this->set(compact('certificate', 'checkResult'));

        return $this->render('certificate');
    }

    /**
     * Network check of both FURS environments (no certificate needed).
     *
     * @return \Cake\Http\Response|null
     */
    public function pingFurs(): ?Response
    {
        $this->getRequest()->allowMethod(['post']);
        $this->Authorization->skipAuthorization();

        $ok = true;
        $details = [date('Y-m-d H:i:s')];
        foreach (['test', 'production'] as $env) {
            $url = (string)Configure::read('Documents.furs.urls.' . $env);
            $result = FursClient::ping($url);
            $ok = $ok && $result['ok'];
            $details[] = '== ' . $env . ' ' . $url . ' => ' . ($result['ok'] ? 'OK' : 'FAILED');
            array_push($details, ...$result['lines']);
        }

        /** @var \Documents\Model\Table\TaxCertificatesTable $TaxCertificates */
        $TaxCertificates = TableRegistry::getTableLocator()->get('Documents.TaxCertificates');
        $userId = (string)$this->getCurrentUser()->get('id');
        $certificate = $TaxCertificates->findForUser($userId) ?? $TaxCertificates->newEmptyEntity();
        $checkResult = ['ok' => $ok, 'text' => implode("
", $details)];
        $this->set(compact('certificate', 'checkResult'));

        return $this->render('certificate');
    }

    /**
     * Upload the current user's p12 certificate used to sign tax confirmation requests.
     *
     * @return \Cake\Http\Response|null
     */
    public function certificate(): ?Response
    {
        // certificates are always bound to the current user
        $this->Authorization->skipAuthorization();

        /** @var \Documents\Model\Table\TaxCertificatesTable $TaxCertificates */
        $TaxCertificates = TableRegistry::getTableLocator()->get('Documents.TaxCertificates');
        $userId = (string)$this->getCurrentUser()->get('id');
        $certificate = $TaxCertificates->findForUser($userId) ?? $TaxCertificates->newEmptyEntity();

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $data = $this->getRequest()->getData();
            $file = $data['p12_file'] ?? null;

            $p12 = null;
            if ($file && $file->getError() === UPLOAD_ERR_OK) {
                $p12 = (string)file_get_contents((string)$file->getStream()->getMetadata('uri'));
            }
            $password = (string)($data['password'] ?? '');
            $taxNo = (string)($data['tax_no'] ?? '');

            if ($taxNo !== '' && FursXml::normalizeTaxNo($taxNo) === null) {
                $this->Flash->error(__d('documents', 'Tax number must have 8 digits.'));
            } elseif ($TaxCertificates->store($userId, $p12, $password, $taxNo)) {
                $this->Flash->success(__d('documents', 'The certificate has been saved.'));

                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__d('documents', 'Certificate could not be opened. Check the file and password.'));
            }
        }

        $this->set(compact('certificate'));

        return null;
    }
}
