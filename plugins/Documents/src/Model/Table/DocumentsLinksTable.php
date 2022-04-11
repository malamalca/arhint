<?php
declare(strict_types=1);

namespace Documents\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Cake\Validation\Validator;

/**
 * DocumentsLinks Model
 *
 * @method \Documents\Model\Entity\DocumentsLink get($primaryKey, array $options = [])
 * @method \Documents\Model\Entity\DocumentsLink newEmptyEntity()
 * @method \Documents\Model\Entity\DocumentsLink patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 */
class DocumentsLinksTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('documents_links');
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
            ->add('link_id', 'valid', ['rule' => 'uuid'])
            ->allowEmptyString('link_id')
            ->add('document_id', 'valid', ['rule' => 'uuid'])
            ->allowEmptyString('document_id');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        /*$rules->add(function ($entity, $options) use($rules) {
            if (get_class($entity) == \Documents\Model\Entity\Invoice::class) {
                $rule = $rules->existsIn(['document_id'], 'Invoices');
                return $rule();
            }

            if (get_class($entity) == \Documents\Model\Entity\Document::class) {
                $rule = $rules->existsIn(['document_id'], 'Documents');
                return $rule();
            }

            if (get_class($entity) == \Documents\Model\Entity\TravelOrder::class) {
                $rule = $rules->existsIn(['document_id'], 'TravelOrders');
                return $rule();
            }

            return false;

        }, 'userExists');*/

        return $rules;
    }

    /**
     * afterDelete method
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \Documents\Model\Entity\DocumentsLink $entity Entity object.
     * @param \ArrayObject $options Array object.
     * @return void
     */
    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {
        $cnt = $this
            ->find()
            ->where(['link_id' => $entity->link_id])
            ->count();

        // only delete when there is only one document in a group of linked documents
        if ($cnt == 1) {
            $this->deleteAll(['link_id' => $entity->link_id]);
        }
    }

    /**
     * Links two documents
     *
     * @param string $model First document's model
     * @param string $id1 First document's id
     * @param string $model2 Second document's model
     * @param string $id2 Second document's id
     * @return string|false
     */
    public function two($model, $id1, $model2, $id2)
    {
        $link_id = Text::uuid();
        $link1 = $this->newEntity([
            'link_id' => $link_id,
            'document_id' => $id1,
            'model' => $model,
        ]);
        $ret1 = $this->save($link1);

        $ret2 = false;
        if ($ret1) {
            $link2 = $this->newEntity([
                'link_id' => $link_id,
                'document_id' => $id2,
                'model' => $model2,
            ]);
            $ret2 = $this->save($link2);
        }

        if ((bool)$ret1 && (bool)$ret2) {
            return $link_id;
        } else {
            return false;
        }
    }

    /**
     * Fetch linked document for specified document's id.
     *
     * @param string $id Document id
     * @param string $documentScope Scope
     * @return \Cake\Datasource\ResultSetInterface|array
     */
    public function forDocument($id, $documentScope)
    {
        $ret = [];
        $links = $this->find()
            ->where(['document_id' => $id])
            ->all()
            ->combine('link_id', 'document_id')
            ->toArray();

        if (!empty($links)) {
            $ret = $this->find()
                ->where(['document_id !=' => $id, 'link_id IN' => array_keys($links)])
                ->contain([$documentScope])
                ->all();
        }

        return $ret;
    }

    /**
     * Checks if entity belongs to user.
     *
     * @param \Documents\Model\Entity\DocumentsLink $entity Entity
     * @param string $ownerId User Id.
     * @return bool
     */
    public function isOwnedBy($entity, $ownerId)
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
