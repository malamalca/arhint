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

        $io->out("Embedding provider: <info>{$provider}</info>");
        $io->out("Target collection:  <info>{$collection}</info>");
        if ($dryRun) {
            $io->warning('Dry run — nothing will be written to the vector DB.');
        }
        $io->hr();

        // Instantiate services up front so configuration problems fail fast.
        try {
            $embeddingService = new EmbeddingService();
        } catch (Exception $e) {
            $io->error('Embedding service not configured: ' . $e->getMessage());

            return static::CODE_ERROR;
        }

        $vectorDb = null;
        if (!$dryRun) {
            try {
                $vectorDb = new VectorDBService();
            } catch (Exception $e) {
                $io->error('Vector DB not configured: ' . $e->getMessage());

                return static::CODE_ERROR;
            }

            if ($recreate) {
                $io->out("Recreating collection \"{$collection}\"...");
                if (!$vectorDb->deleteCollection()) {
                    $io->warning('  Could not delete existing collection (it may not have existed).');
                } else {
                    $io->out('  Existing collection removed.');
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
            $matchingLogIds = $logsTable->find()
                ->select(['id'])
                ->where($logConditions)
                ->all()
                ->extract('id')
                ->toList();
            if (empty($matchingLogIds)) {
                $io->warning('No logs match the given --project/--model filter. Nothing to do.');

                return static::CODE_SUCCESS;
            }
            $query->where(['event_id IN' => $matchingLogIds]);
        }

        $limitOption = $args->getOption('limit');
        if ($limitOption !== null && (int)$limitOption > 0) {
            $query->limit((int)$limitOption);
        }

        $total = $query->count();
        if ($total === 0) {
            $io->out('No analysis records to reindex.');

            return static::CODE_SUCCESS;
        }
        $io->out("Reindexing {$total} analysis record(s)...\n");

        $processed = 0;
        $skipped = 0;
        $failed = 0;
        $index = 0;

        foreach ($query->all() as $analysis) {
            $index++;
            $summary = (string)($analysis->get('summary') ?? '');
            if (trim($summary) === '') {
                $skipped++;
                continue;
            }

            try {
                $vector = $embeddingService->embed($summary);
            } catch (Exception $e) {
                $failed++;
                $io->verbose(sprintf('  [%d/%d] embed failed: %s', $index, $total, $e->getMessage()));
                continue;
            }
            if ($vector === []) {
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $processed++;
                $io->verbose(sprintf('  [%d/%d] embedded %d-dim vector (dry run)', $index, $total, count($vector)));
                continue;
            }

            $metadata = $this->buildMetadata($analysis, $logsTable, $summary);

            if ($vectorDb->upsertOne((string)$analysis->get('id'), $vector, null, $metadata)) {
                $processed++;
            } else {
                $failed++;
                $io->verbose(sprintf('  [%d/%d] upsert failed for analysis %s', $index, $total, $analysis->get('id')));
            }

            if ($index % 25 === 0) {
                $io->out(sprintf('  ...%d/%d processed', $index, $total));
            }
        }

        $io->hr();
        $io->out("\n<success>Done.</success>");
        $io->out("  Embedded/upserted: {$processed}");
        $io->out("  Skipped (empty):   {$skipped}");
        if ($failed > 0) {
            $io->warning("  Failed:            {$failed}");
        }

        return $failed > 0 ? static::CODE_ERROR : static::CODE_SUCCESS;
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

        $log = $logsTable->find()
            ->where(['id' => $analysis->get('event_id')])
            ->first();
        if ($log !== null) {
            $logModel = (string)$log->get('model');
            $logForeignId = (string)$log->get('foreign_id');
            $logUserId = (string)$log->get('user_id');
            $logAction = (string)$log->get('action');
        }

        $metadata = [
            'log_id' => (string)$analysis->get('event_id'),
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
