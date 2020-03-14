<?php
declare(strict_types=1);

namespace LilInvoices\Form;

use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Mailer\Mailer;
use Cake\Validation\Validator;
use LilInvoices\Lib\LilInvoicesExport;

class EmailForm extends Form
{
    /**
     * @var \Cake\Http\ServerRequest $request
     */
    private $request = null;

    /**
     * Form constructor.
     *
     * @param \Cake\Http\ServerRequest $request Request object.
     * @return void
     */
    public function __construct($request)
    {
        $this->request = $request;
    }

    /**
     * Schema definition.
     *
     * @param \Cake\Form\Schema $schema Schema object.
     * @return \Cake\Form\Schema
     */
    protected function _buildSchema(Schema $schema): Schema
    {
        return $schema->addField('name', 'string')
            ->addField('email', ['type' => 'string'])
            ->addField('body', ['type' => 'text']);
    }

    /**
     * Validator definition
     *
     * @param \Cake\Validation\Validator $validator Validator object.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        return $validator
            ->requirePresence('to')
            ->email('to')
            ->notEmptyString('subject')
            ->notEmptyString('body');
    }

    /**
     * Form execute action
     *
     * @param array $data Post data.
     * @return bool
     */
    protected function _execute(array $data): bool
    {
        $filter = (array)$this->request->getQuery();

        $Exporter = new LilInvoicesExport();
        $invoices = $Exporter->find($filter)->toArray();

        if (count($invoices) > 0) {
            $data = $Exporter->export('pdf', $invoices);

            if (!empty($data)) {
                $email = new Mailer('default');

                $email
                    ->setTo($this->request->getData('to'))
                    ->setSubject($this->request->getData('subject'));

                $cc = $this->request->getData('cc');
                if (!empty($cc)) {
                    $email->addCc($cc);
                }
                if ($this->request->getData('cc_me')) {
                    $currentUser = $this->request->getAttribute('identity');
                    $email->addCc($currentUser->email);
                }

                $attachmentName = 'invoices.pdf';
                if (count($invoices) == 1) {
                    $attachmentName = $invoices[0]->title;
                    $attachmentName = (string)mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $attachmentName);
                    $attachmentName = (string)mb_ereg_replace("([\.]{2,})", '', $attachmentName);
                }

                $email->setAttachments([
                    $attachmentName => [
                        'data' => $data,
                        'mimetype' => 'application/pdf',
                    ],
                ]);

                $result = $email->deliver((string)$this->request->getBody());

                return (bool)$result;
            }
        }

        return false;
    }
}
