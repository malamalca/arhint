<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Form;

use App\Model\Entity\User;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\TestCase;
use Cake\TestSuite\TestEmailTransport;
use Documents\Form\EmailForm;
use Documents\Model\Entity\Invoice;
use Documents\Test\TestCase\Form\Stub\FakeInvoicesExport;

/**
 * EmailForm Test Case
 *
 * Uses a stubbed exporter so the test does not depend on an installed PDF engine.
 */
class EmailFormTest extends TestCase
{
    use EmailTrait;

    /**
     * @var array<string>
     */
    public array $fixtures = [
        'Users' => 'app.Users',
        'Attachments' => 'app.Attachments',
        'Logs' => 'app.Logs',
    ];

    public function setUp(): void
    {
        parent::setUp();
        FakeInvoicesExport::$invoices = [];
    }

    /**
     * Build an invoice entity with a counter of the given direction.
     *
     * @param string $direction Counter direction (issued|received).
     * @return \Documents\Model\Entity\Invoice
     */
    private function buildInvoice(string $direction): Invoice
    {
        $invoice = new Invoice([
            'id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5',
            'title' => 'RACUN 2026/001',
        ]);
        $invoice->documents_counter = new \Documents\Model\Entity\DocumentsCounter(['direction' => $direction]);

        return $invoice;
    }

    /**
     * Run the form and return the attachments of the sent message.
     *
     * @return array<string, array<string, mixed>>
     */
    private function sendAndGetAttachments(): array
    {
        $user = TableRegistry::getTableLocator()->get('Users')->get(USER_ADMIN);
        $identity = new class ($user->toArray()) extends User {
            public function applyScope(string $action, mixed $resource, mixed ...$optionalArgs): mixed
            {
                return $resource;
            }
        };

        $data = ['to' => 'buyer@example.com', 'subject' => 'Invoice', 'body' => 'Attached.'];
        $request = (new ServerRequest())
            ->withAttribute('identity', $identity)
            ->withParsedBody($data);

        $form = new EmailForm($request, FakeInvoicesExport::class);
        $this->assertTrue($form->execute($data));
        $this->assertMailSentTo('buyer@example.com');

        return TestEmailTransport::getMessages()[0]->getAttachments();
    }

    /**
     * An issued invoice is sent with the PDF and the eSlog 2.0 XML.
     *
     * @return void
     */
    public function testIssuedInvoiceAttachesPdfAndEslog20Xml(): void
    {
        FakeInvoicesExport::$invoices = [$this->buildInvoice('issued')];

        $attachments = $this->sendAndGetAttachments();

        $this->assertCount(2, $attachments, 'Attachments: ' . implode(', ', array_keys($attachments)));
        $this->assertArrayHasKey('RACUN 2026001', $attachments);
        $this->assertSame('application/pdf', $attachments['RACUN 2026001']['mimetype']);
        $this->assertArrayHasKey('RACUN 2026001.eslog20.xml', $attachments);
        $this->assertSame('application/xml', $attachments['RACUN 2026001.eslog20.xml']['mimetype']);
        $this->assertStringContainsString('<Invoice', (string)base64_decode($attachments['RACUN 2026001.eslog20.xml']['data']));
    }

    /**
     * A received invoice is not re-sent as PDF or XML.
     *
     * @return void
     */
    public function testReceivedInvoiceAttachesNothing(): void
    {
        FakeInvoicesExport::$invoices = [$this->buildInvoice('received')];

        $attachments = $this->sendAndGetAttachments();

        $this->assertSame([], $attachments);
    }
}
