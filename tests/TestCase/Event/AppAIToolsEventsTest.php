<?php
declare(strict_types=1);

namespace App\Test\TestCase\Event;

use App\Event\AppAIToolsEvents;
use App\Model\Entity\User;
use App\Test\TestCase\MakesPdfTrait;
use ArrayObject;
use Authorization\AuthorizationServiceInterface;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Event\AppAIToolsEvents Test Case (attachment tools)
 */
class AppAIToolsEventsTest extends TestCase
{
    use MakesPdfTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Attachments',
    ];

    private const ATTACHMENT_ID = '3e7c2fba-1c29-4e5b-9bb2-000000000001';
    private const FOREIGN_ID = 'ffffffff-ffff-ffff-ffff-ffffffffffff';

    protected AppAIToolsEvents $listener;
    protected User $user;
    private string $uploadFolder;
    private string $originalUploadFolder;

    public function setUp(): void
    {
        parent::setUp();
        $this->listener = new AppAIToolsEvents();

        $authService = $this->createMock(AuthorizationServiceInterface::class);
        $authService->method('applyScope')->willReturnCallback(fn($identity, $action, $resource) => $resource);
        $authService->method('can')->willReturn(true);

        $this->user = TableRegistry::getTableLocator()->get('Users')->get(USER_ADMIN);
        $this->user->setAuthorization($authService);

        $this->originalUploadFolder = (string)Configure::read('App.uploadFolder');
        $this->uploadFolder = TMP . 'tests_uploads_' . uniqid() . DS;
        mkdir($this->uploadFolder . 'Test', 0777, true);
        Configure::write('App.uploadFolder', $this->uploadFolder);
    }

    public function tearDown(): void
    {
        Configure::write('App.uploadFolder', $this->originalUploadFolder);
        foreach (glob($this->uploadFolder . 'Test' . DS . '*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->uploadFolder . 'Test');
        rmdir($this->uploadFolder);

        parent::tearDown();
    }

    public function testAttachmentToolsAreRegistered(): void
    {
        $toolsList = new ArrayObject();
        $this->listener->aiAssistantTools(new Event('App.AIAssistant.tools'), $toolsList);

        $names = array_map(fn($t) => $t->name, iterator_to_array($toolsList));
        $this->assertContains('App.list_attachments', $names);
        $this->assertContains('App.read_attachment', $names);
    }

    public function testListAttachments(): void
    {
        $result = $this->execute('App.list_attachments', ['model' => 'Test', 'foreign_id' => self::FOREIGN_ID]);

        $this->assertCount(1, $result);
        $this->assertEquals(self::ATTACHMENT_ID, $result[0]['id']);
        $this->assertEquals('test.pdf', $result[0]['filename']);
    }

    public function testListAttachmentsWithoutArguments(): void
    {
        $this->assertArrayHasKey('error', $this->execute('App.list_attachments', []));
    }

    public function testListAttachmentsEmpty(): void
    {
        $result = $this->execute('App.list_attachments', ['model' => 'Test', 'foreign_id' => 'nonexistent']);

        $this->assertArrayHasKey('message', $result);
    }

    public function testReadPdfAttachmentById(): void
    {
        $this->writeTestPdf('test.pdf', ['Requirement one: fire resistance EI 60']);

        $result = $this->execute('App.read_attachment', ['id' => self::ATTACHMENT_ID]);

        $this->assertArrayNotHasKey('error', $result, (string)($result['error'] ?? ''));
        $this->assertEquals(1, $result['pages']);
        $this->assertStringContainsString('fire resistance EI 60', $result['text']);
        $this->assertNull($result['next_offset']);
    }

    public function testReadPdfAttachmentByOwner(): void
    {
        $this->writeTestPdf('test.pdf', ['Owner lookup works']);

        $result = $this->execute(
            'App.read_attachment',
            ['model' => 'Test', 'foreign_id' => self::FOREIGN_ID, 'name' => 'TEST'],
        );

        $this->assertArrayNotHasKey('error', $result, (string)($result['error'] ?? ''));
        $this->assertStringContainsString('Owner lookup works', $result['text']);
    }

    public function testReadPdfPageRangeAndPaging(): void
    {
        $this->writeTestPdf('test.pdf', ['First page text', 'Second page text']);

        $second = $this->execute('App.read_attachment', ['id' => self::ATTACHMENT_ID, 'first_page' => 2]);
        $this->assertEquals(2, $second['pages']);
        $this->assertEquals(2, $second['first_page']);
        $this->assertStringContainsString('Second page text', $second['text']);
        $this->assertStringNotContainsString('First page text', $second['text']);

        $beyond = $this->execute('App.read_attachment', ['id' => self::ATTACHMENT_ID, 'first_page' => 5]);
        $this->assertArrayHasKey('error', $beyond);

        $all = $this->execute('App.read_attachment', ['id' => self::ATTACHMENT_ID]);
        $this->assertStringContainsString('Second page text', $all['text']);
        $offset = mb_strlen($all['text']) - 4;
        $tail = $this->execute('App.read_attachment', ['id' => self::ATTACHMENT_ID, 'offset' => $offset]);
        $this->assertEquals($offset, $tail['offset']);
        $this->assertEquals(4, mb_strlen($tail['text']));
    }

    public function testReadPdfWithoutTextLayerSuggestsOcr(): void
    {
        $this->writeTestPdf('test.pdf', ['']);

        $result = $this->execute('App.read_attachment', ['id' => self::ATTACHMENT_ID]);

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('ocr=true', $result['error']);
    }

    public function testReadTextAttachment(): void
    {
        $attachments = TableRegistry::getTableLocator()->get('Attachments');
        $attachments->updateAll(
            ['filename' => 'notes.txt', 'ext' => 'txt', 'mimetype' => 'text/plain'],
            ['id' => self::ATTACHMENT_ID],
        );
        file_put_contents($this->uploadFolder . 'Test' . DS . 'notes.txt', "Zahteve: požarna odpornost\n");

        $result = $this->execute('App.read_attachment', ['id' => self::ATTACHMENT_ID]);

        $this->assertStringContainsString('požarna odpornost', $result['text']);
    }

    public function testReadUnsupportedAttachmentType(): void
    {
        $attachments = TableRegistry::getTableLocator()->get('Attachments');
        $attachments->updateAll(
            ['filename' => 'image.png', 'ext' => 'png', 'mimetype' => 'image/png'],
            ['id' => self::ATTACHMENT_ID],
        );
        file_put_contents($this->uploadFolder . 'Test' . DS . 'image.png', 'x');

        $result = $this->execute('App.read_attachment', ['id' => self::ATTACHMENT_ID]);

        $this->assertArrayHasKey('error', $result);
    }

    public function testReadAttachmentMissingFile(): void
    {
        $result = $this->execute('App.read_attachment', ['id' => self::ATTACHMENT_ID]);

        $this->assertArrayHasKey('error', $result);
    }

    public function testReadAttachmentRejectsPathTraversal(): void
    {
        file_put_contents($this->uploadFolder . 'secret.txt', 'secret');
        $attachments = TableRegistry::getTableLocator()->get('Attachments');
        $attachments->updateAll(
            ['filename' => '../secret.txt', 'ext' => 'txt', 'mimetype' => 'text/plain'],
            ['id' => self::ATTACHMENT_ID],
        );

        $result = $this->execute('App.read_attachment', ['id' => self::ATTACHMENT_ID]);
        unlink($this->uploadFolder . 'secret.txt');

        $this->assertArrayHasKey('error', $result);
        $this->assertArrayNotHasKey('text', $result);
    }

    public function testReadAttachmentNotFound(): void
    {
        $result = $this->execute('App.read_attachment', ['id' => '00000000-0000-0000-0000-000000000000']);

        $this->assertArrayHasKey('error', $result);
    }

    public function testReadAttachmentRequiresIdentification(): void
    {
        $this->assertArrayHasKey('error', $this->execute('App.read_attachment', []));
    }

    /**
     * Run a tool and return its result.
     *
     * @param string $tool Tool name.
     * @param array<mixed> $arguments Tool arguments.
     * @return mixed
     */
    private function execute(string $tool, array $arguments): mixed
    {
        $event = new Event('App.AIAssistant.executeTool', null, [$tool, $arguments, $this->user]);
        $this->listener->aiAssistantExecuteTool($event, $tool, $arguments);

        return $event->getResult();
    }

    /**
     * Write a PDF into the test uploads folder.
     *
     * @param string $filename File name inside the Test folder.
     * @param array<string> $pageTexts Text of each page.
     * @return void
     */
    private function writeTestPdf(string $filename, array $pageTexts): void
    {
        $this->writePdf($this->uploadFolder . 'Test' . DS . $filename, $pageTexts);
    }
}
