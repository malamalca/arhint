<?php
declare(strict_types=1);

namespace Documents\Form;

use App\Mailer\ArhintMailer;
use Cake\Database\Expression\QueryExpression;
use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Http\ServerRequest;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Documents\Model\Entity\Invoice;
use Documents\Model\Entity\TravelOrder;

class EmailForm extends Form
{
    /**
     * @var \Cake\Http\ServerRequest $request
     */
    private ServerRequest $request;

    /**
     * @var mixed
     */
    private mixed $exporter;

    /**
     * Form constructor.
     *
     * @param \Cake\Http\ServerRequest $request Request object.
     * @param string $exporterClass Exporter class string
     * @return void
     */
    public function __construct(ServerRequest $request, string $exporterClass = '\\Documents\\Lib\\DocumentsExport')
    {
        $this->request = $request;
        $this->exporter = new $exporterClass();
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

        $documents = $this->exporter->find($filter);
        $identity->applyScope('index', $documents);
        $documents = $documents->toArray();

        if (count($documents) > 0) {
            $data = $this->exporter->export('pdf', $documents);

            if (!empty($data)) {
                /** @var \App\Model\Entity\User $currentUser */
                $currentUser = $this->request->getAttribute('identity');

                $email = new ArhintMailer('default', $currentUser);
                $email
                    ->setFrom([(string)$currentUser->email => $currentUser->name])
                    ->setTo($this->request->getData('to'))
                    ->setSubject($this->request->getData('subject'));

                $cc = $this->request->getData('cc');
                if (!empty($cc)) {
                    $email->addCc($cc);
                }
                if ($this->request->getData('cc_me')) {
                    $email->addCc($currentUser->email);
                }

                // base attachments (document export)
                $attachments = [];

                if (count($documents) == 1) {
                    // do not attach base document with received invoiced
                    if ($documents[0]->documents_counter->direction != 'received') {
                        $attachmentName = $documents[0]->title;
                        $attachmentName = (string)mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $attachmentName);
                        $attachmentName = (string)mb_ereg_replace("([\.]{2,})", '', $attachmentName);

                        $attachments[$attachmentName] = [
                            'data' => $data,
                            'mimetype' => 'application/pdf',
                        ];
                    }
                } else {
                    $attachmentName = 'documents.pdf';
                    $attachments[$attachmentName] = [
                        'data' => $data,
                        'mimetype' => 'application/pdf',
                    ];
                }

                // documents file attachments
                $AttachmentsTable = TableRegistry::getTableLocator()->get('App.Attachments');
                $docAttachments = $AttachmentsTable->find()
                    ->select(['id', 'model', 'filename'])
                    ->where(function (QueryExpression $exp, SelectQuery $query) use ($documents) {

                        $atchs = [];
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

                            $atchs[] = $query->newExpr()->and(['model' => $modelName, 'foreign_id' => $doc->id]);
                        }

                        return $exp->or($atchs);
                    })
                    ->all();

                foreach ($docAttachments as $attachment) {
                    $attachments[$attachment->filename] = [
                        'file' => $attachment->getFilePath(),
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
