<?php
declare(strict_types=1);

namespace Documents\Model\Table;

use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Documents\Model\Entity\DocumentsLog;

/**
 * DocumentsLogs Model
 *
 * @method \Documents\Model\Entity\DocumentsLog get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Documents\Model\Entity\DocumentsLog newEmptyEntity()
 * @method \Documents\Model\Entity\DocumentsLog patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 */
class DocumentsLogsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config List of options for this table.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('documents_logs');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsTo('Documents.Invoices', [
            'foreignKey' => 'document_id',
        ]);
        $this->belongsTo('Documents.Documents', [
            'foreignKey' => 'document_id',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->add('id', 'valid', ['rule' => 'uuid'])
            ->allowEmptyString('id', 'create')
            ->add('document_id', 'valid', ['rule' => 'uuid'])
            ->allowEmptyString('document_id');

        return $validator;
    }

    /**
     * Fetch logs for specified document's id.
     *
     * @param string $id Document id
     * @param string $documentScope Scope
     * @return \Cake\Datasource\ResultSetInterface|array<string, string>
     */
    public function forDocument(string $id, string $documentScope): ResultSetInterface|array
    {
        $logs = $this->find()
            ->where(['model' => $documentScope, 'document_id' => $id])
            ->all();

        return $logs;
    }

    /**
     * Checks if entity belongs to user.
     *
     * @param \Documents\Model\Entity\DocumentsLog $entity Entity
     * @param string $ownerId User Id.
     * @return bool
     */
    public function isOwnedBy(DocumentsLog $entity, string $ownerId): bool
    {
        switch ($entity->model) {
            case 'Document':
                /** @var \Documents\Model\Table\DocumentsTable $ModelTable */
                $ModelTable = TableRegistry::getTableLocator()->get('Documents.Documents');
                break;
            case 'TravelOrder':
                /** @var \Documents\Model\Table\TravelOrdersTable $ModelTable */
                $ModelTable = TableRegistry::getTableLocator()->get('Documents.TravelOrders');
                break;
            default:
                /** @var \Documents\Model\Table\InvoicesTable $ModelTable */
                $ModelTable = TableRegistry::getTableLocator()->get('Documents.Invoices');
        }

        return $ModelTable->isOwnedBy($entity->document_id, $ownerId);
    }
}
