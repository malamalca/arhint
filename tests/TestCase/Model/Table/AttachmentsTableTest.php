<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AttachmentsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\AttachmentsTable Test Case
 */
class AttachmentsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\AttachmentsTable
     */
    protected $Attachments;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.Attachments',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Attachments') ? [] : ['className' => AttachmentsTable::class];
        $this->Attachments = $this->getTableLocator()->get('Attachments', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Attachments);
        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\AttachmentsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        // Valid entity – all fields optional
        $attachment = $this->Attachments->newEntity([
            'model' => 'Test',
            'foreign_id' => '11111111-1111-4111-8111-111111111111',
            'filename' => 'test.pdf',
            'mimetype' => 'application/pdf',
            'filesize' => 2048,
        ]);
        $this->assertEmpty($attachment->getErrors(), 'Valid entity should have no errors');

        // document_id must be a UUID
        $attachment = $this->Attachments->newEntity(['document_id' => 'not-a-uuid']);
        $this->assertArrayHasKey('document_id', $attachment->getErrors());

        // filesize must be numeric
        $attachment = $this->Attachments->newEntity(['filesize' => 'large']);
        $this->assertArrayHasKey('filesize', $attachment->getErrors());

        // Valid UUID for document_id is accepted
        $attachment = $this->Attachments->newEntity([
            'document_id' => '22222222-2222-4222-8222-222222222222',
        ]);
        $this->assertArrayNotHasKey('document_id', $attachment->getErrors());
    }

    /**
     * Test findForModel custom finder
     *
     * @return void
     * @uses \App\Model\Table\AttachmentsTable::findForModel()
     */
    public function testFindForModel(): void
    {
        // Fixture record: model='Test', foreign_id='ffffffff-ffff-ffff-ffff-ffffffffffff'
        $result = $this->Attachments->find('forModel', model: 'Test', foreignId: 'ffffffff-ffff-ffff-ffff-ffffffffffff')
            ->all()
            ->toArray();

        $this->assertCount(1, $result, 'Should find the fixture attachment');
        $this->assertEquals('Test', $result[0]->model);
        $this->assertEquals('test.pdf', $result[0]->filename);

        // Non-existent model returns empty
        $result = $this->Attachments->find('forModel', model: 'NonExistent', foreignId: 'ffffffff-ffff-ffff-ffff-ffffffffffff')
            ->all()
            ->toArray();
        $this->assertEmpty($result, 'Unknown model should return no records');
    }

    /**
     * Test beforeMarshal with legacy array file data
     *
     * @return void
     * @uses \App\Model\Table\AttachmentsTable::beforeMarshal()
     */
    public function testBeforeMarshalWithArrayFileData(): void
    {
        // Simulate PHP $_FILES-style array input
        $entity = $this->Attachments->newEntity([
            'model' => 'Test',
            'filename' => [
                'name' => 'upload.txt',
                'type' => 'text/plain',
                'size' => 512,
            ],
        ]);

        $this->assertEquals('upload.txt', $entity->filename, 'filename extracted from array');
        $this->assertEquals('text/plain', $entity->mimetype, 'mimetype extracted from array');
        $this->assertEquals(512, $entity->filesize, 'filesize extracted from array');
    }

    /**
     * Test beforeMarshal with scanned document data
     *
     * @return void
     * @uses \App\Model\Table\AttachmentsTable::beforeMarshal()
     */
    public function testBeforeMarshalWithScannedData(): void
    {
        $scannedContent = base64_encode('%PDF-1.4 scanned content');

        $entity = $this->Attachments->newEntity([
            'model' => 'Test',
            'scanned' => $scannedContent,
        ]);

        // filename should be set to a generated unique id
        $this->assertNotEmpty($entity->filename, 'Scanned entity should have an auto filename');
        $this->assertEquals('scanned.pdf', $entity->original, 'Original should be set to scanned.pdf');
        $this->assertEquals('application/pdf', $entity->mimetype, 'Mimetype should be PDF');
        $this->assertNotEmpty($entity->filesize, 'Filesize should be set');
    }

    /**
     * Test isOwnedBy with no owner returns false
     *
     * @return void
     * @uses \App\Model\Table\AttachmentsTable::isOwnedBy()
     */
    public function testIsOwnedByNullOwner(): void
    {
        $attachment = $this->Attachments->get('3e7c2fba-1c29-4e5b-9bb2-000000000001');

        $this->assertFalse(
            $this->Attachments->isOwnedBy($attachment, null),
            'null ownerId should always return false',
        );
        $this->assertFalse(
            $this->Attachments->isOwnedBy($attachment, ''),
            'Empty ownerId should always return false',
        );
    }

    /**
     * Test isOwnedBy with unknown foreign_id returns true (record not found in owning table)
     *
     * @return void
     * @uses \App\Model\Table\AttachmentsTable::isOwnedBy()
     */
    public function testIsOwnedByUnknownForeignId(): void
    {
        // The fixture attachment has model='Test' which hits the default branch (Invoices)
        // foreign_id='ffffffff-ffff-ffff-ffff-ffffffffffff' doesn't exist in Invoices table
        // → isOwnedBy returns true (record not found = access allowed)
        $attachment = $this->Attachments->get('3e7c2fba-1c29-4e5b-9bb2-000000000001');

        $this->assertTrue(
            $this->Attachments->isOwnedBy($attachment, USER_ADMIN),
            'Non-existent foreign_id should return true',
        );
    }
}
