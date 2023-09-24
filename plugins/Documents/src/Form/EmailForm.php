<?php
declare(strict_types=1);

namespace Documents\Form;

use Cake\Core\Configure;
use Cake\Database\Expression\QueryExpression;
use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Http\ServerRequest;
use Cake\Mailer\Mailer;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Documents\Lib\DocumentsExport;
use Documents\Model\Entity\Invoice;
use Documents\Model\Entity\TravelOrder;

class EmailForm extends Form
{
    /**
     * @var \Cake\Http\ServerRequest $request
     */
    private ServerRequest $request;

    /**
     * Form constructor.
     *
     * @param \Cake\Http\ServerRequest $request Request object.
     * @return void
     */
    public function __construct(ServerRequest $request)
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
            ->email('to');
    }

    /**
     * Form execute action
     *
     * @param array<array-key, mixed> $data Post data.
     * @return bool
     */
    protected function _execute(array $data): bool
    {
        $filter = (array)$this->request->getQuery();

        $identity = $this->request->getAttribute('identity');

        $Exporter = new DocumentsExport();
        $documents = $Exporter->find($filter);
        $identity->applyScope('index', $documents);
        $documents = $documents->toArray();

        if (count($documents) > 0) {
            $data = $Exporter->export('pdf', $documents);

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

                $attachmentName = 'documents.pdf';
                if (count($documents) == 1) {
                    $attachmentName = $documents[0]->title;
                    $attachmentName = (string)mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $attachmentName);
                    $attachmentName = (string)mb_ereg_replace("([\.]{2,})", '', $attachmentName);
                }

                // base attachments (document export)
                $attachments = [
                    $attachmentName => [
                        'data' => $data,
                        'mimetype' => 'application/pdf',
                    ],
                ];

                // documents file attachments
                $DocumentsAttachmentsTable = TableRegistry::getTableLocator()->get('Documents.DocumentsAttachments');
                $docAttachments = $DocumentsAttachmentsTable->find()
                    ->select(['id', 'original', 'filename'])
                    ->where(function (QueryExpression $exp, SelectQuery $query) use ($documents) {
                        foreach ($documents as $doc) {
                            switch (get_class($doc)) {
                                case Invoice::class:
                                    $modelName = 'Invoice';
                                    break;
                                case TravelOrder::class:
                                    $modelName = 'TravelOrder';
                                    break;
                                default:
                                    $modelName = 'Document';
                            }

                            $atchs[] = $query->newExpr()->and(['model' => $modelName, 'document_id' => $doc->id]);
                        }

                        return $exp->or($atchs);
                    })
                    ->all();

                foreach ($docAttachments as $attachment) {
                    $attachments[$attachment->original] = [
                        'file' => Configure::read('Documents.uploadFolder') . DS . $attachment->filename,
                    ];
                }

                $email->setAttachments($attachments);

                $result = $email->deliver((string)$this->request->getData('body'));

                return (bool)$result;
            }
        }

        return false;
    }
}
