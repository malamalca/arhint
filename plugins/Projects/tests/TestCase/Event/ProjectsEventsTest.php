<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Event;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\I18n\Date;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;
use Projects\Event\ProjectsEvents;

/**
 * Projects\Event\ProjectsEvents Test Case
 *
 * Focuses on logProjectDocument(): creating a project log when a document or
 * invoice is (re)assigned to a project.
 */
class ProjectsEventsTest extends TestCase
{
    protected array $fixtures = [
        'app.Users',
        'app.Logs',
        'plugin.Projects.Projects',
    ];

    protected ProjectsEvents $listener;

    /** Fixture project id. */
    private const PROJECT_1 = '4dd53305-9715-4be4-b169-20defe113d2a';
    private const PROJECT_2 = '4dd53305-9715-4be4-b169-20defe113d2b';

    public function setUp(): void
    {
        parent::setUp();
        Router::reload();
        Router::createRouteBuilder('/')->scope('/documents', ['plugin' => 'Documents'], function ($routes): void {
            $routes->fallbacks(DashedRoute::class);
        });
        $this->listener = new ProjectsEvents();
    }

    // -------------------------------------------------------------------------
    // implementedEvents
    // -------------------------------------------------------------------------

    public function testImplementedEventsRegistersAfterSave(): void
    {
        $events = $this->listener->implementedEvents();

        $this->assertArrayHasKey('Model.afterSave', $events);
        $this->assertSame('logProjectDocument', $events['Model.afterSave']);
    }

    // -------------------------------------------------------------------------
    // logProjectDocument — happy paths
    // -------------------------------------------------------------------------

    public function testLogCreatedWhenDocumentAssignedToProject(): void
    {
        $documentsTable = TableRegistry::getTableLocator()->get('Documents.Documents');
        $id = Text::uuid();
        $document = $documentsTable->newEmptyEntity();
        $document->set('id', $id);
        $document->set('no', '2024-042');
        $document->set('title', 'Architectural Plans');
        $document->set('dat_issue', new Date('2024-05-01'));
        $document->clean();
        // Assign to a project in a separate save -> project_id becomes dirty.
        $document->set('project_id', self::PROJECT_1);

        $before = $this->countComments(self::PROJECT_1);
        $this->dispatch($documentsTable, $document);

        $this->assertSame($before + 1, $this->countComments(self::PROJECT_1));

        $log = $this->latestComment(self::PROJECT_1);
        $this->assertNotNull($log);
        $this->assertSame('Projects.Project', $log->model);
        $this->assertSame(self::PROJECT_1, $log->foreign_id);
        $this->assertSame('Comment', $log->action);
        $this->assertStringContainsString('2024-042', (string)$log->descript);
        $this->assertStringContainsString('Architectural Plans', (string)$log->descript);
        $this->assertStringContainsString('2024-05-01', (string)$log->descript);
        $this->assertStringContainsString('/view/' . $id, (string)$log->descript);
    }

    public function testLogCreatedWhenInvoiceAssignedToProject(): void
    {
        $invoicesTable = TableRegistry::getTableLocator()->get('Documents.Invoices');
        $id = Text::uuid();
        $invoice = $invoicesTable->newEmptyEntity();
        $invoice->set('id', $id);
        $invoice->set('no', 'INV-2024-7');
        $invoice->set('title', 'Final Construction Invoice');
        $invoice->clean();
        $invoice->set('project_id', self::PROJECT_2);

        $before = $this->countComments(self::PROJECT_2);
        $this->dispatch($invoicesTable, $invoice);

        $this->assertSame($before + 1, $this->countComments(self::PROJECT_2));

        $log = $this->latestComment(self::PROJECT_2);
        $this->assertNotNull($log);
        $this->assertSame(self::PROJECT_2, $log->foreign_id);
        $this->assertStringContainsString('INV-2024-7', (string)$log->descript);
        $this->assertStringContainsString('Final Construction Invoice', (string)$log->descript);
        $this->assertStringContainsString('/view/' . $id, (string)$log->descript);
    }

    // -------------------------------------------------------------------------
    // logProjectDocument — no-op paths
    // -------------------------------------------------------------------------

    public function testNoLogWhenProjectIdEmpty(): void
    {
        $documentsTable = TableRegistry::getTableLocator()->get('Documents.Documents');
        $document = $documentsTable->newEmptyEntity();
        $document->set('id', Text::uuid());
        $document->set('title', 'Unassigned Document');
        // project_id stays empty (document created before being put on a project).

        $before = $this->countComments(self::PROJECT_1);
        $this->dispatch($documentsTable, $document);

        $this->assertSame($before, $this->countComments(self::PROJECT_1));
    }

    public function testNoLogWhenProjectIdNotDirty(): void
    {
        $documentsTable = TableRegistry::getTableLocator()->get('Documents.Documents');
        $document = $documentsTable->newEmptyEntity();
        $document->set('id', Text::uuid());
        $document->set('title', 'Edited Document');
        $document->set('project_id', self::PROJECT_1);
        // Subsequent edit that does not touch project_id.
        $document->clean();
        $document->set('title', 'Edited Document v2');

        $before = $this->countComments(self::PROJECT_1);
        $this->dispatch($documentsTable, $document);

        $this->assertSame($before, $this->countComments(self::PROJECT_1));
    }

    public function testNoLogForUnrelatedTable(): void
    {
        $projectsTable = TableRegistry::getTableLocator()->get('Projects.Projects');
        $project = $projectsTable->newEmptyEntity();
        $project->set('id', Text::uuid());
        $project->set('project_id', self::PROJECT_1);

        $before = $this->countComments(self::PROJECT_1);
        $this->dispatch($projectsTable, $project);

        $this->assertSame($before, $this->countComments(self::PROJECT_1));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function dispatch(Table $table, EntityInterface $entity): void
    {
        $options = new ArrayObject();
        $event = new Event('Model.afterSave', $table, ['entity' => $entity, 'options' => $options]);
        $this->listener->logProjectDocument($event, $entity, $options);
    }

    private function countComments(string $projectId): int
    {
        return TableRegistry::getTableLocator()->get('App.Logs')->find()
            ->where([
                'model' => 'Projects.Project',
                'foreign_id' => $projectId,
                'action' => 'Comment',
            ])
            ->count();
    }

    private function latestComment(string $projectId): ?EntityInterface
    {
        return TableRegistry::getTableLocator()->get('App.Logs')->find()
            ->where([
                'model' => 'Projects.Project',
                'foreign_id' => $projectId,
                'action' => 'Comment',
            ])
            ->orderBy(['created' => 'DESC', 'id' => 'DESC'])
            ->first();
    }
}
