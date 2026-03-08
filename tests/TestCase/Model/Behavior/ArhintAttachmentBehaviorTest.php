<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Behavior;

use App\Model\Behavior\ArhintAttachmentBehavior;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Behavior\ArhintAttachmentBehavior Test Case
 */
class ArhintAttachmentBehaviorTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.Attachments',
        'app.Users',
    ];

    /**
     * Get a fresh bare table with the behavior attached.
     *
     * @param array<string, mixed> $config Behavior config.
     * @return \Cake\ORM\Table
     */
    private function makeTable(array $config = []): Table
    {
        static $counter = 0;
        $counter++;
        // Each call gets a unique alias so table-locator doesn't reuse instances
        $alias = 'BehaviorTest' . $counter;
        /** @var \Cake\ORM\Table $table */
        $table = $this->getTableLocator()->get($alias, [
            'className' => Table::class,
            'table' => 'users',
        ]);
        $table->addBehavior('App.ArhintAttachment', $config);

        return $table;
    }

    /**
     * Test that the default config uses 'filename' as the field.
     *
     * @return void
     */
    public function testDefaultConfig(): void
    {
        $table = $this->makeTable();
        /** @var \App\Model\Behavior\ArhintAttachmentBehavior $behavior */
        $behavior = $table->getBehavior('ArhintAttachment');

        $this->assertInstanceOf(ArhintAttachmentBehavior::class, $behavior);
        $this->assertEquals('filename', $behavior->getConfig('field'));
    }

    /**
     * Test afterSave with field='*' and no uploadedFiles in options returns early.
     *
     * @return void
     */
    public function testAfterSaveWildcardNoUploadedFiles(): void
    {
        $table = $this->makeTable(['field' => '*']);
        /** @var \App\Model\Behavior\ArhintAttachmentBehavior $behavior */
        $behavior = $table->getBehavior('ArhintAttachment');

        $entity = $table->newEmptyEntity();

        // No uploadedFiles key → returns early without touching AttachmentsTable
        $behavior->afterSave(
            new Event('Model.afterSave', $table),
            $entity,
            new ArrayObject(),
        );

        // No Attachments created
        $attachmentsCount = $this->getTableLocator()->get('Attachments')->find()->count();
        $this->assertEquals(1, $attachmentsCount, 'Fixture attachment should still be the only record');
    }

    /**
     * Test afterSave with field='*' and empty uploadedFiles array returns early.
     *
     * @return void
     */
    public function testAfterSaveWildcardEmptyUploadedFiles(): void
    {
        $table = $this->makeTable(['field' => '*']);
        /** @var \App\Model\Behavior\ArhintAttachmentBehavior $behavior */
        $behavior = $table->getBehavior('ArhintAttachment');

        $entity = $table->newEmptyEntity();

        // uploadedFiles key present but empty → returns early
        $behavior->afterSave(
            new Event('Model.afterSave', $table),
            $entity,
            new ArrayObject(['uploadedFiles' => []]),
        );

        $attachmentsCount = $this->getTableLocator()->get('Attachments')->find()->count();
        $this->assertEquals(1, $attachmentsCount, 'No new attachments should be created');
    }

    /**
     * Test afterSave with a specific field not present in uploadedFiles does nothing.
     *
     * @return void
     */
    public function testAfterSaveSpecificFieldNotInOptions(): void
    {
        $table = $this->makeTable(['field' => 'document_file']);
        /** @var \App\Model\Behavior\ArhintAttachmentBehavior $behavior */
        $behavior = $table->getBehavior('ArhintAttachment');

        $entity = $table->newEmptyEntity();

        // uploadedFiles does not contain 'document_file' → nothing happens
        $behavior->afterSave(
            new Event('Model.afterSave', $table),
            $entity,
            new ArrayObject(['uploadedFiles' => ['other_file' => 'not-an-upload']]),
        );

        $attachmentsCount = $this->getTableLocator()->get('Attachments')->find()->count();
        $this->assertEquals(1, $attachmentsCount, 'No attachments should be created');
    }

    /**
     * Test afterSave with a specific field present but not a valid UploadedFile does nothing.
     *
     * @return void
     */
    public function testAfterSaveSpecificFieldNotUploadedFile(): void
    {
        $table = $this->makeTable(['field' => 'document_file']);
        /** @var \App\Model\Behavior\ArhintAttachmentBehavior $behavior */
        $behavior = $table->getBehavior('ArhintAttachment');

        $entity = $table->newEmptyEntity();

        // The field key exists but value is not a Laminas\Diactoros\UploadedFile instance
        $behavior->afterSave(
            new Event('Model.afterSave', $table),
            $entity,
            new ArrayObject(['uploadedFiles' => ['document_file' => 'plain-string']]),
        );

        $attachmentsCount = $this->getTableLocator()->get('Attachments')->find()->count();
        $this->assertEquals(1, $attachmentsCount, 'Non-UploadedFile value should be ignored');
    }

    /**
     * Test custom field config is stored correctly.
     *
     * @return void
     */
    public function testCustomFieldConfig(): void
    {
        $table = $this->makeTable(['field' => ['file_a', 'file_b']]);
        /** @var \App\Model\Behavior\ArhintAttachmentBehavior $behavior */
        $behavior = $table->getBehavior('ArhintAttachment');

        $this->assertEquals(['file_a', 'file_b'], $behavior->getConfig('field'));
    }
}
