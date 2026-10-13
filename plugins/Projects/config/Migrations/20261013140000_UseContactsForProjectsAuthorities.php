<?php
declare(strict_types=1);

use Cake\Utility\Text;
use Migrations\BaseMigration;

class UseContactsForProjectsAuthorities extends BaseMigration
{
    /**
     * Preserve any entries created before authorities were moved to Contacts.
     *
     * @return void
     */
    public function up(): void
    {
        $cases = $this->table('projects_authority_cases');
        if (!$cases->hasColumn('authority_id')) {
            return;
        }
        $cases->addColumn('contact_id', 'uuid', ['null' => true])->update();
        $connection = $this->getAdapter()->getConnection();
        $authorities = $connection->selectQuery()->from('projects_authorities')->select('*')
            ->execute()->fetchAll('assoc');
        foreach ($authorities as $authority) {
            $contacts = $connection->selectQuery()->from('contacts')->select(['id'])->where([
                'owner_id' => $authority['owner_id'], 'title' => $authority['title'], 'kind' => 'C',
            ])->orderBy(['created' => 'ASC', 'id' => 'ASC'])->execute()->fetchAll('assoc');
            // An existing company with the same name is reused, even when the name is not unique.
            $contactId = $contacts ? $contacts[0]['id'] : Text::uuid();
            if (!$contacts) {
                $connection->insertQuery()
                    ->insert(['id', 'owner_id', 'title', 'kind', 'descript', 'created', 'modified'])
                    ->into('contacts')->values([
                        'id' => $contactId, 'owner_id' => $authority['owner_id'], 'title' => $authority['title'],
                        'kind' => 'C', 'descript' => $authority['contact'],
                        'created' => $authority['created'], 'modified' => $authority['modified'],
                    ])->execute();
            }
            $connection->updateQuery()->update('projects_authority_cases')->set(['contact_id' => $contactId])
                ->where(['authority_id' => $authority['id']])->execute();
        }
        $cases->dropForeignKey('authority_id')->removeIndex(['authority_id'])->removeColumn('authority_id')->update();
        // The foreign key needs the same character set and collation as the referenced column.
        $collation = $this->columnCollation('contacts', 'id');
        $definition = $collation === null ? ['null' => false] : [
            'null' => false, 'limit' => 36, 'encoding' => explode('_', $collation)[0], 'collation' => $collation,
        ];
        $cases->changeColumn('contact_id', $collation === null ? 'uuid' : 'char', $definition)
            ->addIndex(['contact_id'])
            ->addForeignKey('contact_id', 'contacts', 'id', ['delete' => 'RESTRICT'])->update();
        $this->table('projects_authorities')->drop()->save();
    }

    /**
     * Contacts are shared CRM data and must not be deleted by rollback.
     *
     * @return void
     */
    public function down(): void
    {
        throw new RuntimeException(
            'The authority register has been merged into shared CRM contacts. ' .
            'Restore from backup to reverse this migration.',
        );
    }

    /**
     * Collation of a column, or null when it cannot be read (not MySQL).
     *
     * @param string $table Table name.
     * @param string $column Column name.
     * @return string|null
     */
    private function columnCollation(string $table, string $column): ?string
    {
        try {
            $row = $this->fetchRow(sprintf(
                'SELECT collation_name FROM information_schema.columns ' .
                "WHERE table_schema = DATABASE() AND table_name = '%s' AND column_name = '%s'",
                $table,
                $column,
            ));
        } catch (Throwable) {
            return null;
        }

        return $row ? (string)($row['collation_name'] ?? $row[0] ?? '') ?: null : null;
    }
}
