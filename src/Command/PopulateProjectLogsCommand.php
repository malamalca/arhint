<?php
declare(strict_types=1);

namespace App\Command;

use App\Job\AiProcessLogJob;
use App\Model\Entity\Log;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Datasource\EntityInterface;
use Cake\ORM\TableRegistry;
use Cake\Queue\Job\Message;
use DateTimeInterface;
use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Destination;
use Interop\Queue\Message as QueueMessage;
use Interop\Queue\Processor;
use Interop\Queue\Producer;
use Interop\Queue\Queue;
use Interop\Queue\SubscriptionConsumer;
use Interop\Queue\Topic;
use RuntimeException;

class PopulateProjectLogsCommand extends Command
{
    /**
     * Project UUID for "Stanovanjska hiša Rozman"
     */
    public const PROJECT_ID = '09bf361c-bc9f-4166-8f95-61340bba30a7';

    /**
     * Admin user from test fixtures
     */
    public const USER_ID = 'bb4dcb27-2be9-4673-8c2b-c1e823b3c300';

    /**
     * @var \App\Job\AiProcessLogJob|null Cached job instance for processing.
     */
    private ?AiProcessLogJob $job = null;

    /**
     * @param \Cake\Console\Arguments $args
     * @param \Cake\Console\ConsoleIo $io
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $projectId = self::PROJECT_ID;
        // Parse --force from raw argv if present
        $force = in_array('--force', $_SERVER['argv'] ?? []);

        // Verify project exists
        /** @var \Projects\Model\Table\ProjectsTable $projectsTable */
        $projectsTable = TableRegistry::getTableLocator()->get('Projects.Projects');
        $project = $projectsTable->find()
            ->where(['id' => $projectId])
            ->first();

        if (!$project) {
            $io->error("Project not found: {$projectId}");

            return static::CODE_ERROR;
        }

        $io->out("Populating intelligence logs for: {$project->title} ({$projectId})");
        $io->hr();

        // Get a real user from the database for log attribution
        $usersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $usersTable->find()->first();
        if (!$user) {
            $io->error('No users found in database.');

            return static::CODE_ERROR;
        }
        $userId = (string)$user->id;
        $io->out("Using user: {$user->name} ({$userId})\n");

        $logsTable = TableRegistry::getTableLocator()->get('Logs');

        // Check if already populated
        $existingCount = $logsTable->find()
            ->where([
                'model' => 'Project',
                'foreign_id' => $projectId,
            ])
            ->count();

        if ($existingCount > 0 && !$force) {
            $io->warning("Found {$existingCount} existing logs for this project. Recreating...");
            // For now always force during test runs
        }

        // Clean up any previous test data for this project
        if ($existingCount > 0) {
            $logsTable->deleteAll([
                'model' => 'Project',
                'foreign_id' => $projectId,
            ]);
            $io->out("Removed {$existingCount} existing log entries.");

            // Clean analysis records for removed logs
            /** @var array<string> $analysisIds */
            $analysisIds = $logsTable->find()
                ->where([
                    'model' => 'Project',
                    'foreign_id' => $projectId,
                ])
                ->all()
                ->extract('id')
                ->toList();
            if (!empty($analysisIds)) {
                TableRegistry::getTableLocator()->get('LogsAnalysis')
                    ->deleteAll(['event_id IN' => $analysisIds]);
            }
        }

        $logs = $this->getBuildingPermitLogs($projectId);

        $io->out('Creating ' . count($logs) . " log entries...\n");

        $createdCount = 0;
        $processedCount = 0;
        foreach ($logs as $idx => $logData) {
            $label = ($idx + 1) . '/' . count($logs) . ' [' . $logData['action'] . ']';
            $io->out($label);

            /** @var \App\Model\Entity\Log $log */
            $log = $logsTable->newEntity([
                'id' => uniqid('project-log-', true),
                'model' => 'Project',
                'foreign_id' => $projectId,
                'user_id' => $userId,
                'action' => $logData['action'],
                'descript' => $logData['descript'],
            ]);

            // Save triggers Model.afterSave event (which queues AiProcessLog)
            // We disable the event to avoid queue overhead and process directly
            if (!$logsTable->save($log, ['atomic' => false])) {
                $io->error('Failed to save log: ' . json_encode($log->getErrors()));
                continue;
            }
            $createdCount++;

            // Process through AI immediately (bypass queue workers)
            if ($this->processLog($log, $io)) {
                $processedCount++;
                $io->out('  ✓ Analyzed & embedded');
            } else {
                $io->warning('  ✗ Processing failed');
            }

            // Small delay between requests to avoid overwhelming the AI server
            usleep(200_000); // 200ms
        }

        $io->out(''); // clear overwrite line
        $io->hr();
        $io->out("\n✓ Created {$createdCount} log entries");
        $io->out("✓ Processed {$processedCount}/{$createdCount} through AI analysis");
        $io->out('✓ ChromaDB embeddings stored for processed entries');

        return static::CODE_SUCCESS;
    }

    /**
     * Get building permit documentation phases as log entries.
     * Represents a realistic Slovenian building permit process.
     *
     * @param string $projectId Project UUID.
     * @return array<int, array<string, mixed>>
     */
    private function getBuildingPermitLogs(string $projectId): array
    {
        return [
            // Phase 1: Initial Planning & Feasibility
            [
                'action' => 'INITIAL_CONSULTATION',
                'descript' => 'Initial consultation with client Rozman regarding residential building permit. '
                    . 'Client wants to build a two-family house on parcel ID 2847/3 in Ljubljana-Bežigrad. '
                    . 'Preliminary site assessment indicates no major topographical constraints. '
                    . 'Plot is 420m², building footprint limited to 60% per local regulations.',
            ],
            [
                'action' => 'FEASIBILITY_STUDY',
                'descript' => 'Feasibility study completed. Confirmed: land use designation allows '
                    . 'residential construction (R3 zone), no environmental restrictions noted, '
                    . 'access to utilities confirmed. Estimated total construction cost: €450,000. '
                    . 'Recommended timeline for documentation and permit approval: 8-10 months.',
            ],
            // Phase 2: Architectural Concept Design
            [
                'action' => 'ARCHITECTURAL_CONCEPT',
                'descript' => 'Architectural concept design submitted to client. Two-story building '
                    . 'with flat roof, ground floor containing garage and common entrance, '
                    . 'two independent apartments on upper floor (85m² each). Modern minimalist '
                    . 'aesthetic with large glazing facing south orientation. '
                    . 'Client requested revisions to balcony layout on eastern facade.',
            ],
            [
                'action' => 'CONCEPT_REVISION',
                'descript' => 'Revised concept incorporating client feedback: moved eastern balconies '
                    . 'to maximize privacy, added green roof system for rainwater management, '
                    . 'modified garage entrance to comply with Bežigrad municipal setback '
                    . 'requirements. Client approved final concept design.',
            ],
            // Phase 3: Technical Documentation Preparation
            [
                'action' => 'TECHNICAL_DOCUMENTS_STARTED',
                'descript' => 'Technical documentation preparation initiated per Zakon o graditvi '
                    . 'objektov (ZGO-1). Assembled engineering team: architect Marko Novak, '
                    . 'structural engineer Ana Kovačič, MEP specialist Peter Horvat. Required '
                    . 'submissions include: project summary, architectural plans, structural '
                    . 'calculations, utility connection plans, and energy certificate draft.',
            ],
            [
                'action' => 'STRUCTURAL_CALCULATIONS',
                'descript' => 'Structural engineering calculations completed by Ana Kovačič. '
                    . 'Foundation design: strip footings at 1.5m depth due to local soil conditions '
                    . '(clay layer at 0.8m requires careful drainage). Seismic zone 3 requirements '
                    . 'applied per EU8 regulations. Concrete grade C25/30 specified for structural '
                    . 'elements. No significant concerns identified.',
            ],
            [
                'action' => 'MEP_DESIGN',
                'descript' => 'Mechanical, electrical, and plumbing design completed. Solar thermal '
                    . 'system added to roof for domestic hot water (estimated 60% reduction in '
                    . 'heating energy). Underfloor heating on ground floor, radiator system '
                    . 'upstairs. Electrical: 2×40A connections planned (one per apartment), '
                    . 'EV charging point provision in garage.',
            ],
            [
                'action' => 'ENERGY_CERTIFICATE',
                'descript' => 'Draft energy performance certificate prepared by certified assessor '
                    . 'Jurij Smrkolj. Projected primary energy demand: 58 kWh/m²·year, well below '
                    . 'national requirement of 90 kWh/m²·year for new residential buildings. '
                    . 'Expected classification: A (excellent). Window U-value: 1.0 W/m²K, '
                    . 'wall insulation: mineral wool 200mm.',
            ],
            [
                'action' => 'DOCUMENTATION_SUBMISSION',
                'descript' => 'Complete building permit application submitted to Municipality of '
                    . 'Ljubljana - Department for Spatial Planning on 15.03.2026. '
                    . 'Application reference: G-2026/0847. All mandatory documents included '
                    . 'as per ZGO-1 Article 59. Administrative fee €3,200 paid. Expected '
                    . 'preliminary review within 15 working days.',
            ],
            // Phase 4: Municipal Review Process
            [
                'action' => 'MUNICIPALITY_REVIEW_STARTED',
                'descript' => 'Ljubljana Municipality initiated formal review process. '
                    . 'Documents forwarded to relevant departments: Urban Planning, '
                    . 'Fire Protection Service, Environmental Protection Office. '
                    . 'Initial document completeness check passed. '
                    . 'No missing attachments identified.',
            ],
            [
                'action' => 'FIRE_PROTECTION_APPROVAL',
                'descript' => 'Fire protection assessment approved by Regional Directorate of '
                    . 'Interior – Ljubljana Unit. Two separate escape routes per apartment '
                    . 'confirmed, fire door rating EI30 specified for common areas, external '
                    . 'access for emergency services verified. No objections raised.',
            ],
            [
                'action' => 'URBAN_PLANNING_OBJECTIONS',
                'descript' => 'Urban Planning Department issued request for clarification: '
                    . 'shadow impact analysis required due to proposed building height exceeding '
                    . 'neighboring structures by 1.2m on northern side. Client neighbor '
                    . '(parcel ID 2847/4) submitted informal objection regarding potential '
                    . 'view obstruction.',
            ],
            [
                'action' => 'SHADOW_ANALYSIS_COMPLETED',
                'descript' => 'Shadow impact analysis prepared by architect Novak and approved by '
                    . 'certified surveyor Tomaz Majcen. Confirmed: winter sunlight reduction on '
                    . 'adjacent parcel is within 15% regulatory limit for residential zone R3. '
                    . 'Summer shading actually improves thermal comfort of neighboring property. '
                    . 'Analysis submitted as supplementary documentation to application G-2026/0847.',
            ],
            [
                'action' => 'ENVIRONMENTAL_REVIEW',
                'descript' => 'Environmental Protection Office confirmed project qualifies for '
                    . 'simplified environmental assessment per ZVO (Environmental Protection Act). '
                    . 'Main requirements: construction waste management plan mandatory, '
                    . 'stormwater retention system required (existing green roof solution '
                    . 'acceptable), noise protection measures during construction phase. '
                    . 'No significant negative impact on protected natural values identified.',
            ],
            // Phase 5: Permit Approval & Construction Documentation
            [
                'action' => 'BUILDING_PERMIT_APPROVED',
                'descript' => 'Building permit officially approved by Ljubljana Municipality '
                    . 'on 12.06.2026. Reference: GB-LJ-2026/347. Permit valid for 3 years '
                    . 'from issue date. Construction can begin immediately after utility '
                    . 'connection confirmations are finalized. Total approval timeline: 9 weeks '
                    . '(faster than average due to well-prepared documentation).',
            ],
            [
                'action' => 'CONSTRUCTION_DOCS_PREPARED',
                'descript' => 'Detailed construction execution documentation completed by '
                    . 'engineering team. Includes: reinforced concrete drawings, steel '
                    . 'connection details, façade construction sections, plumbing schematics, '
                    . 'electrical single-line diagrams. Documents distributed to main contractor '
                    . '(Gradbeno podjetje Ljubljana d.o.o.) for tender pricing.',
            ],
        ];
    }

    /**
     * Recursively convert an entity (or nested structures) to plain scalar values
     * safe for JSON serialization.
     *
     * @param mixed $data The data to convert.
     * @return mixed The converted data with only scalar/array values.
     */
    private function entityToScalars(mixed $data): mixed
    {
        if (is_null($data)) {
            return null;
        }
        if (is_bool($data) || is_numeric($data)) {
            return $data;
        }
        if (is_string($data)) {
            return $data;
        }
        if ($data instanceof DateTimeInterface) {
            return $data->format('Y-m-d H:i:s');
        }
        if ($data instanceof EntityInterface) {
            $result = [];
            foreach ($data->getVisible() as $field) {
                $value = $data->get($field);
                $result[$field] = $this->entityToScalars($value);
            }

            return $result;
        }
        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $val) {
                $result[$key] = $this->entityToScalars($val);
            }

            return $result;
        }

        return (string)$data;
    }

    /**
     * Process a log entry through AI analysis and ChromaDB storage.
     */
    private function processLog(Log $log, ConsoleIo $io): bool
    {
        if ($this->job === null) {
            $this->job = new AiProcessLogJob();
        }

        // Convert entity to a plain scalar array for JSON serialization
        $entityData = $this->entityToScalars($log);

        // Create a queue message wrapper for the job
        $message = $this->createMessage([
            'user_id' => self::USER_ID,
            'entity' => $entityData,
            'job_id' => (string)$log->get('id'),
        ]);

        $result = $this->job->execute($message);

        return $result === Processor::ACK;
    }

    /**
     * Create a Cake Queue Message instance with the given arguments.
     *
     * @param array<string, mixed> $arguments Message arguments
     */
    private function createMessage(array $arguments): Message
    {
        $wrapped = ['data' => $arguments];
        $body = json_encode($wrapped);
        if ($body === false) {
            throw new RuntimeException('Failed to encode message body');
        }

        // Minimal stub for Interop Queue Message (only getBody() is called)
        $queueMessage = new class ($body) implements QueueMessage {
            /**
             * @param string $body Message body
             */
            public function __construct(private string $body)
            {
            }

            /**
             * @inheritDoc
             */
            public function getBody(): string
            {
                return $this->body;
            }

            /**
             * @inheritDoc
             */
            public function setBody(string $body): void
            {
            }

            /**
             * @inheritDoc
             */
            public function getId(): ?string // @phpstan-ignore return.unusedType (fixed by QueueMessage interface)
            {
                return null;
            }

            /**
             * @inheritDoc
             */
            public function setId(?string $id): void
            {
            }

            /**
             * @inheritDoc
             */

            /**
             * @return array<string>
             */
            public function getContentTypes(): array
            {
                return [];
            }

            /**
             * @inheritDoc
             */
            public function getContentTypeHeaderName(): string
            {
                return '';
            }

            /**
             * @inheritDoc
             */
            public function setMessageTypeHeaderName(): string
            {
                return '';
            }

            /**
             * @inheritDoc
             */
            public function getMessageTypeId(): ?string // @phpstan-ignore return.unusedType (fixed by QueueMessage interface)
            {
                return null;
            }

            /**
             * @inheritDoc
             */
            public function setMessageTypeId(?string $messageTypeId): void
            {
            }

            /**
             * @inheritDoc
             */
            public function setCorrelationId(?string $correlationId = null): void
            {
            }

            /**
             * @inheritDoc
             */
            public function getCorrelationId(): ?string
            {
                return null;
            }

            /**
             * @inheritDoc
             */
            public function setReplyTo(?string $address = null): void
            {
            }

            /**
             * @inheritDoc
             */
            public function getReplyTo(): ?string
            {
                return null;
            }

            /**
             * @inheritDoc
             */
            public function setMessageTtl(int $milliseconds): void
            {
            }

            /**
             * @inheritDoc
             */
            public function getMessageTtl(): int
            {
                return 0;
            }

            /**
             * @inheritDoc
             */
            public function setDelay(int $milliseconds): void
            {
            }

            /**
             * @inheritDoc
             */
            public function getDelay(): int
            {
                return 0;
            }

            /**
             * @inheritDoc
             */
            public function setPriority(int $priority): void
            {
            }

            /**
             * @inheritDoc
             */
            public function getPriority(): int
            {
                return 0;
            }

            /**
             * @inheritDoc
             */

            /**
             * @return array<string, mixed>
             */
            public function getHeaders(): array
            {
                return [];
            }

            /**
             * @inheritDoc
             */
            public function hasHeader(string $name): bool
            {
                return false;
            }

            /**
             * @param string  $name    Header name
             * @param mixed   $default Default value
             * @return mixed            Header value or default
             */
            public function getHeader(string $name, mixed $default = null): mixed
            {
                return $default;
            }

            /**
             * @inheritDoc
             */
            public function setHeader(string $name, mixed $value): void
            {
            }

            /**
             * @inheritDoc
             */
            public function removeHeader(string $name): void
            {
            }

            /**
             * @inheritDoc
             */
            public function getDeliveryTag(): ?string // @phpstan-ignore return.unusedType (fixed by QueueMessage interface)
            {
                return null;
            }

            /**
             * @inheritDoc
             */
            public function isRedelivered(): bool
            {
                return false;
            }

            /**
             * @inheritDoc
             */

            /**
             * @param array<string, mixed> $properties Properties
             */
            public function setProperties(array $properties): void
            {
            }

            /**
             * @inheritDoc
             */

            /**
             * @return array<string, mixed>
             */
            public function getProperties(): array
            {
                return [];
            }

            /**
             * @inheritDoc
             */
            public function setProperty(string $name, mixed $value): void
            {
            }

            /**
             * @param string  $name    Property name
             * @param mixed   $default Default value
             * @return mixed            Property value or default
             */
            public function getProperty(string $name, mixed $default = null): mixed
            {
                return $default;
            }

            /**
             * @inheritDoc
             */
            public function setRedelivered(bool $redelivered): void
            {
            }

            /**
             * @inheritDoc
             */
            public function setMessageId(?string $messageId = null): void
            {
            }

            /**
             * @inheritDoc
             */

            /**
             * @param array<string, mixed> $headers Headers
             */
            public function setHeaders(array $headers): void
            {
            }

            /**
             * @inheritDoc
             */
            public function getMessageId(): ?string
            {
                return null;
            }

            /**
             * @inheritDoc
             */
            public function getTimestamp(): ?int
            {
                return null;
            }

            /**
             * @inheritDoc
             */
            public function setTimestamp(?int $timestamp = null): void
            {
            }
        };

        // Minimal stub for Interop Context (no methods called by Job Message)
        $context = new class implements Context {
            /**
             * @inheritDoc
             */

            /**
             * @param array<string, mixed> $properties Properties
             * @param array<string, mixed> $headers    Headers
             * @return \Interop\Queue\Message
             */
            public function createMessage(string $body = '', array $properties = [], array $headers = []): QueueMessage
            {
                throw new RuntimeException('Not implemented');
            }

            /**
             * @inheritDoc
             */
            public function createTopic(string $topicName): Topic
            {
                throw new RuntimeException('Not implemented');
            }

            /**
             * @inheritDoc
             */
            public function createQueue(string $queueName): Queue
            {
                throw new RuntimeException('Not implemented');
            }

            /**
             * @inheritDoc
             */
            public function createTemporaryQueue(): Queue
            {
                throw new RuntimeException('Not implemented');
            }

            /**
             * @inheritDoc
             */
            public function createProducer(): Producer
            {
                throw new RuntimeException('Not implemented');
            }

            /**
             * @inheritDoc
             */
            public function createConsumer(Destination $destination): Consumer
            {
                throw new RuntimeException('Not implemented');
            }

            /**
             * @inheritDoc
             */
            public function createSubscriptionConsumer(): SubscriptionConsumer
            {
                throw new RuntimeException('Not implemented');
            }

            /**
             * @inheritDoc
             */
            public function purgeQueue(Queue $queue): void
            {
            }

            /**
             * @inheritDoc
             */
            public function close(): void
            {
            }
        };

        return new Message($queueMessage, $context);
    }
}
