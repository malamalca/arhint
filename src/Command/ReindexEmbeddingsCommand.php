<?php
declare(strict_types=1);

namespace App\Command;

use App\Lib\EmbeddingService;
use App\Lib\VectorDBService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Log\Log;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Exception;

/**
 * Re-embeds stored log analysis summaries into the vector database using the
 * currently configured embedding provider (see Configure::read('Embedding')).
 *
 * This is the supported way to switch embedding providers (e.g. local <-> openai).
 * Because providers produce vectors of different dimensions, the target Chroma
 * collection must be empty or freshly created at the new dimension — use
 * --recreate (or point --collection at a new name) when changing provider.
 *
 * Examples:
 *   bin/cake reindex_embeddings --recreate
 *   bin/cake reindex_embeddings --collection events_openai --recreate
 *   bin/cake reindex_embeddings --project 09bf361c-bc9f-4166-8f95-61340bba30a7
 *   bin/cake reindex_embeddings --dry-run
 */
class ReindexEmbeddingsCommand extends Command
{
    /**
     * @inheritDoc
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription(
                'Re-embed log analysis summaries into the vector DB using the configured embedding provider.',
            )
            ->addOption('collection', [
                'help' => 'Target collection name. Overrides VectorDB.collection from config for this run.',
            ])
            ->addOption('project', [
                'help' => 'Only reindex analyses whose log belongs to this project/foreign id (logs.foreign_id).',
            ])
            ->addOption('model', [
                'help' => 'Only reindex analyses whose log has this model, e.g. "Projects.Project".',
            ])
            ->addOption('limit', [
                'help' => 'Maximum number of analyses to process.',
            ])
            ->addOption('recreate', [
                'boolean' => true,
                'help' => 'Delete and recreate the target collection before reindexing. '
                    . 'Required when switching providers to a different vector dimension.',
            ])
            ->addOption('dry-run', [
                'boolean' => true,
                'help' => 'Embed and report counts without writing to the vector DB.',
            ]);

        return $parser;
    }

    /**
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $startTime = microtime(true);
        $dryRun = (bool)$args->getOption('dry-run');
        $recreate = (bool)$args->getOption('recreate');

        // Allow overriding the collection for this run. VectorDBService reads the
        // collection name from config at construction, so set it before instantiating.
        $collectionOption = $args->getOption('collection');
        if ($collectionOption !== null && $collectionOption !== '') {
            Configure::write('VectorDB.collection', (string)$collectionOption);
        }
        $collection = (string)Configure::read('VectorDB.collection', 'events');
        $provider = (string)Configure::read('Embedding.provider', 'local');

        Log::info('ReindexEmbeddingsCommand started', [
            'scope' => 'reindex',
            'provider' => $provider,
            'collection' => $collection,
            'dry_run' => $dryRun,
            'recreate' => $recreate,
        ]);

        $io->out("Embedding provider: <info>{$provider}</info>");
        $io->out("Target collection:  <info>{$collection}</info>");
        if ($dryRun) {
            $io->warning('Dry run — nothing will be written to the vector DB.');
        }
        $io->hr();

        // Instantiate services up front so configuration problems fail fast.
        try {
            $embeddingService = new EmbeddingService();
            Log::debug('EmbeddingService initialized', [
                'scope' => 'reindex',
                'provider' => $provider,
            ]);
        } catch (Exception $e) {
            Log::error('ReindexEmbeddingsCommand: EmbeddingService failed to initialize', [
                'scope' => 'reindex',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $io->error('Embedding service not configured: ' . $e->getMessage());

            return static::CODE_ERROR;
        }

        $vectorDb = null;
        if (!$dryRun) {
            try {
                $vectorDb = new VectorDBService();
                Log::debug('VectorDBService initialized', [
                    'scope' => 'reindex',
                    'collection' => $collection,
                ]);
            } catch (Exception $e) {
                Log::error('ReindexEmbeddingsCommand: VectorDBService failed to initialize', [
                    'scope' => 'reindex',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $io->error('Vector DB not configured: ' . $e->getMessage());

                return static::CODE_ERROR;
            }

            if ($recreate) {
                $io->out("Recreating collection \"{$collection}\"...");
                Log::info("Recreating vector collection", [
                    'scope' => 'reindex',
                    'collection' => $collection,
                ]);
                if (!$vectorDb->deleteCollection()) {
                    $io->warning('  Could not delete existing collection (it may not have existed).');
                    Log::warning('Could not delete vector collection — it may not have existed', [
                        'scope' => 'reindex',
                        'collection' => $collection,
                    ]);
                } else {
                    $io->out('  Existing collection removed.');
                    Log::info('Vector collection deleted successfully', [
                        'scope' => 'reindex',
                        'collection' => $collection,
                    ]);
                }
                // The collection is lazily (re)created on first upsert at the new dimension.
            }
        }

        $logsAnalysisTable = TableRegistry::getTableLocator()->get('LogsAnalysis');
        $logsTable = TableRegistry::getTableLocator()->get('Logs');

        // Build the analysis query, optionally constrained by the related log's
        // project (foreign_id) or model.
        $query = $logsAnalysisTable->find();
        $projectFilter = $args->getOption('project');
        $modelFilter = $args->getOption('model');
        if (($projectFilter !== null && $projectFilter !== '') || ($modelFilter !== null && $modelFilter !== '')) {
            $logConditions = [];
            if ($projectFilter !== null && $projectFilter !== '') {
                $logConditions['foreign_id'] = (string)$projectFilter;
            }
            if ($modelFilter !== null && $modelFilter !== '') {
                $logConditions['model'] = (string)$modelFilter;
            }
            Log::debug('Filtering logs by conditions', [
                'scope' => 'reindex',
                'conditions' => $logConditions,
            ]);
            $matchingLogIds = $logsTable->find()
                ->select(['id'])
                ->where($logConditions)
                ->all()
                ->extract('id')
                ->toList();
            if (empty($matchingLogIds)) {
                $io->warning('No logs match the given --project/--model filter. Nothing to do.');
                Log::info('No logs matched the given filters', [
                    'scope' => 'reindex',
                    'conditions' => $logConditions,
                ]);

                return static::CODE_SUCCESS;
            }
            Log::debug('Found matching log IDs', [
                'scope' => 'reindex',
                'matching_log_count' => count($matchingLogIds),
            ]);
            $query->where(['event_id IN' => $matchingLogIds]);
        }

        $limitOption = $args->getOption('limit');
        if ($limitOption !== null && (int)$limitOption > 0) {
            $query->limit((int)$limitOption);
            Log::debug('Query limited', [
                'scope' => 'reindex',
                'limit' => (int)$limitOption,
            ]);
        }

        $total = $query->count();
        if ($total === 0) {
            $io->out('No analysis records to reindex.');
            Log::info('Reindex completed — no analysis records found', [
                'scope' => 'reindex',
                'duration_sec' => round(microtime(true) - $startTime, 3),
            ]);

            return static::CODE_SUCCESS;
        }
        $io->out("Reindexing {$total} analysis record(s)...\n");
        Log::info('Starting reindex batch', [
            'scope' => 'reindex',
            'total_records' => $total,
        ]);

        $processed = 0;
        $skipped = 0;
        $failed = 0;
        $index = 0;

        /** @var array{expected: int, actual: int}|null */
        $dimensionMismatch = null;

        foreach ($query->all() as $analysis) {
            $index++;
            $analysisId = (string)$analysis->get('id');
            $eventId = (string)$analysis->get('event_id');
            $summary = (string)($analysis->get('summary') ?? '');

            if (trim($summary) === '') {
                $skipped++;
                Log::debug('Skipping analysis — empty summary', [
                    'scope' => 'reindex',
                    'analysis_id' => $analysisId,
                    'event_id' => $eventId,
                    'progress' => "{$index}/{$total}",
                ]);
                continue;
            }

            try {
                $vector = $embeddingService->embed($summary);
            } catch (Exception $e) {
                $failed++;
                Log::warning('Embedding failed for analysis', [
                    'scope' => 'reindex',
                    'analysis_id' => $analysisId,
                    'event_id' => $eventId,
                    'error' => $e->getMessage(),
                    'progress' => "{$index}/{$total}",
                ]);
                $io->verbose(sprintf('  [%d/%d] embed failed: %s', $index, $total, $e->getMessage()));
                continue;
            }
            if ($vector === []) {
                $skipped++;
                Log::debug('Skipping analysis — empty vector returned', [
                    'scope' => 'reindex',
                    'analysis_id' => $analysisId,
                    'event_id' => $eventId,
                    'progress' => "{$index}/{$total}",
                ]);
                continue;
            }

            if ($dryRun) {
                $processed++;
                Log::debug('Embedded vector (dry run)', [
                    'scope' => 'reindex',
                    'analysis_id' => $analysisId,
                    'vector_dimensions' => count($vector),
                    'progress' => "{$index}/{$total}",
                ]);
                $io->verbose(sprintf('  [%d/%d] embedded %d-dim vector (dry run)', $index, $total, count($vector)));
                continue;
            }

            $metadata = $this->buildMetadata($analysis, $logsTable, $summary);

            if ($vectorDb->upsertOne((string)$analysis->get('id'), $vector, null, $metadata)) {
                $processed++;
                Log::debug('Upserted vector', [
                    'scope' => 'reindex',
                    'analysis_id' => $analysisId,
                    'vector_dimensions' => count($vector),
                    'progress' => "{$index}/{$total}",
                ]);
            } else {
                $failed++;

                // Detect dimension mismatch on first failure and build a helpful hint.
                if ($dimensionMismatch === null) {
                    $dimCheck = $this->detectDimensionMismatch($vectorDb, count($vector));
                    if ($dimCheck !== null) {
                        $dimensionMismatch = $dimCheck;
                        Log::error('Vector dimension mismatch detected', [
                            'scope' => 'reindex',
                            'expected_dimension' => $dimCheck['expected'],
                            'actual_dimension' => $dimCheck['actual'],
                            'collection' => $collection,
                        ]);
                    }
                }

                Log::warning('Upsert failed for analysis', [
                    'scope' => 'reindex',
                    'analysis_id' => $analysisId,
                    'event_id' => $eventId,
                    'progress' => "{$index}/{$total}",
                ]);
                $io->verbose(sprintf('  [%d/%d] upsert failed for analysis %s', $index, $total, $analysisId));

                // If we already know it's a dimension mismatch, abort early to save time.
                if ($dimensionMismatch !== null) {
                    break;
                }
            }

            if ($index % 25 === 0) {
                $elapsed = round(microtime(true) - $startTime, 2);
                $rate = $index / $elapsed;
                $io->out(sprintf('  ...%d/%d processed (%.1fs, %.1f/sec)', $index, $total, $elapsed, $rate));
                Log::info('Reindex batch progress', [
                    'scope' => 'reindex',
                    'processed' => $processed,
                    'skipped' => $skipped,
                    'failed' => $failed,
                    'progress' => "{$index}/{$total}",
                    'elapsed_sec' => $elapsed,
                    'rate_per_sec' => round($rate, 2),
                ]);
            }
        }

        $duration = round(microtime(true) - $startTime, 3);
        $rate = $index > 0 ? round($index / $duration, 2) : 0;

        // Show dimension-mismatch hint prominently if detected.
        if ($dimensionMismatch !== null) {
            $io->hr();
            $io->error(sprintf(
                'Dimension mismatch: collection "%s" expects %d-dim vectors but provider produces %d-dim.',
                $collection,
                $dimensionMismatch['expected'],
                $dimensionMismatch['actual'],
            ));
            $io->out('');
            $io->out('Fix: re-run with <info>--recreate</info> to delete and recreate the collection,');
            $io->out("or use <info>--collection \"{$collection}_v2\"</info> to create a fresh one.");
            $io->out('');
        }

        $io->hr();
        $io->out("\n<success>Done.</success>");
        $io->out("  Embedded/upserted: {$processed}");
        $io->out("  Skipped (empty):   {$skipped}");
        if ($failed > 0) {
            $io->warning("  Failed:            {$failed}");
        }
        $io->out(sprintf('  Duration:          %.3fs (%.2f records/sec)', $duration, $rate));

        Log::info('ReindexEmbeddingsCommand completed', [
            'scope' => 'reindex',
            'total_records' => $total,
            'processed' => $processed,
            'skipped' => $skipped,
            'failed' => $failed,
            'duration_sec' => $duration,
            'rate_per_sec' => $rate,
            'dry_run' => $dryRun,
        ]);

        return $failed > 0 ? static::CODE_ERROR : static::CODE_SUCCESS;
    }

    /**
     * Probe the vector DB for a dimension mismatch and return a human-readable
     * error hint, or null if no mismatch was detected.
     *
     * @return array{expected: int, actual: int}|null
     */
    private function detectDimensionMismatch(VectorDBService $vectorDb, int $actualDim): ?array
    {
        try {
            $collectionInfo = $vectorDb->getCollectionInfo();
            if ($collectionInfo !== null && isset($collectionInfo['metadata']['dimension'])) {
                $expectedDim = (int)$collectionInfo['metadata']['dimension'];
                if ($expectedDim > 0 && $expectedDim !== $actualDim) {
                    return ['expected' => $expectedDim, 'actual' => $actualDim];
                }
            }
        } catch (Exception $e) {
            Log::debug('Could not probe collection info for dimension check', [
                'scope' => 'reindex',
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Build the vector point metadata for an analysis record, mirroring the
     * structure written by AiProcessLogJob::storeInVectorDb().
     *
     * @param \Cake\Datasource\EntityInterface $analysis The logs_analysis record.
     * @param \Cake\ORM\Table $logsTable The Logs table.
     * @param string $summary The analysis summary text.
     * @return array<string, mixed>
     */
    private function buildMetadata(
        EntityInterface $analysis,
        Table $logsTable,
        string $summary,
    ): array {
        $logModel = '';
        $logForeignId = '';
        $logUserId = '';
        $logAction = '';

        $eventId = (string)$analysis->get('event_id');
        $log = $logsTable->find()
            ->where(['id' => $eventId])
            ->first();

        if ($log === null) {
            Log::warning('Related log not found when building metadata', [
                'scope' => 'reindex',
                'analysis_id' => (string)$analysis->get('id'),
                'event_id' => $eventId,
            ]);
        } else {
            $logModel = (string)$log->get('model');
            $logForeignId = (string)$log->get('foreign_id');
            $logUserId = (string)$log->get('user_id');
            $logAction = (string)$log->get('action');
        }

        $metadata = [
            'log_id' => $eventId,
            'log_model' => $logModel,
            'log_foreign_id' => $logForeignId,
            'log_user_id' => $logUserId,
            'log_action' => $logAction,
            'summary' => $summary,
            'priority' => (int)$analysis->get('priority') ?: null,
        ];

        if ($logModel !== '') {
            $metadata['model'] = $logModel;
        }

        return $metadata;
    }
}
