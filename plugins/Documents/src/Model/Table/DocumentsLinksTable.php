<?php
declare(strict_types=1);

namespace Documents\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
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
        //$rules->add($rules->existsIn(['link_id'], 'Links'));
        $rules->add($rules->existsIn(['document_id'], 'Documents'));

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
     * @param string $id1 First document's id
     * @param string $id2 Second document's id
     * @return string|false
     */
    public function two($id1, $id2)
    {
        $link_id = Text::uuid();
        $link = $this->newEntity(['document_id' => $id1, 'link_id' => $link_id]);
        $ret1 = $this->save($link);

        $link = $this->newEntity(['document_id' => $id2, 'link_id' => $link_id]);
        $ret2 = $this->save($link);

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
     * @return \Cake\Datasource\ResultSetInterface|array
     */
    public function forDocument($id)
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
                ->contain(['Documents'])
                ->all();
        }

        return $ret;
    }
}
