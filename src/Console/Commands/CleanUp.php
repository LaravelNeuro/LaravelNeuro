<?php

namespace LaravelNeuro\LaravelNeuro\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use LaravelNeuro\LaravelNeuro\Networking\Database\Models\NetworkCorporation;
use LaravelNeuro\LaravelNeuro\Networking\Database\Models\NetworkDataSet;
use LaravelNeuro\LaravelNeuro\Networking\Database\Models\NetworkHistory;
use LaravelNeuro\LaravelNeuro\Networking\Database\Models\NetworkProject;
use LaravelNeuro\LaravelNeuro\Networking\Database\Models\NetworkState;

class CleanUp extends Command
{
    protected $signature = 'lneuro:cleanup
    {--i|interactive : Present interactive command interface to set values and flags.}
    {--d|daysOld=30 : Set cutoff date (in days) for history deletion.}
    {--c|corporation=0 : Select a corporation to apply cleanup to (ID). 0 => all.}
    {--p|prune : Delete related NetworkProject, NetworkState, and NetworkDataSet records.}
    {--f|force : Delete records even when Project has no definite resolution.}
    {--t|dry-run : Perform a dry run (no deletions).}
    {--s|stat : Show statistics of matching records instead of performing deletion.}';

    protected $description = 'Remove old or unwanted NetworkHistory entries. Optionally prune entire projects if they are resolved using --prune, or even unresolved projects if --force is specified. Use --dry-run to simulate and --stat to display record counts and storage usage.';

    private function loadAdditionalStyles()
    {
        $this->output->getFormatter()->setStyle('cyan', new \Symfony\Component\Console\Formatter\OutputFormatterStyle('cyan'));
        // Add more custom styles if desired
    }

    private function batchOutput(array $output)
    {
        foreach($output as $line)
            {
                $line = (object) $line;
                $allowedMethods = ["info", "warning", "error", "line"];
                $outputMethod = in_array($line->level, $allowedMethods) ? $line->level : "line";
                $this->$outputMethod($line->message);
            }
    }

    public function handle()
    {
        $this->loadAdditionalStyles();

        if ($this->option('interactive')) {
            $this->info("Starting interactive cleanup session.");
            [$daysOld, $corporation, $prune, $force] = $this->startInteractiveSession();
        } else {
            $daysOld     = $this->option('daysOld') ?? 30;
            $corporation = NetworkCorporation::find($this->option('corporation'));
            $prune       = $this->option('prune');
            $force       = $this->option('force');
        }

        $daysOld    = (int)$daysOld ?: 30;
        $cutoffDate = Carbon::now()->subDays($daysOld);
        $verbose    = $this->option('verbose');
        $dryRun     = $this->option('dry-run');

        // If --stat is provided, display statistics and exit.
        if ($this->option('stat')) {
            $output = $this->showStats($cutoffDate, $corporation, $force);
            $this->info("Statistics (for entries older than {$cutoffDate->toDateTimeString()}):");
            $this->batchOutput($output);
            return 0;
        }

        // Store stat information for later
        if ($verbose) {
            $stat = $this->showStats($cutoffDate, $corporation, $force);
        }

        // Begin deletion for NetworkHistory entries.
        $historyQuery = NetworkHistory::query()
            ->where('created_at', '<', $cutoffDate);

        $historyQuery->whereHas('project', function ($query) use ($corporation, $force, $cutoffDate) {
            if (!$force) {
                $query->whereNotNull('resolution');
            }
            if ($corporation) {
                $query->where('corporation_id', $corporation->id);
            }
            $query->where('updated_at', '<', $cutoffDate);
        });

        $historyCount = $historyQuery->count();
        if ($dryRun) {
            $this->info("[Dry Run] Would delete {$historyCount} NetworkHistory record(s).");
        } else {
            $deletedHistoryCount = $historyQuery->delete();
            if ($verbose) {
                $this->info("Deleted {$deletedHistoryCount} NetworkHistory record(s).");
            }
        }

        // Optionally prune related projects and their associated states and datasets.
        if ($prune) {
            $projectQuery = NetworkProject::query()
                ->where('updated_at', '<', $cutoffDate);

            if (!$force) {
                $projectQuery->whereNotNull('resolution');
            }
            if ($corporation) {
                $projectQuery->where('corporation_id', $corporation->id);
            }

            $projects = $projectQuery->get();

            if ($dryRun) {
                $this->info("[Dry Run] Would prune " . count($projects) . " NetworkProject(s) along with related NetworkState and NetworkDataSet records.");
            } else {
                foreach ($projects as $project) {
                    NetworkState::where('project_id', $project->id)->delete();
                    NetworkDataSet::where('project_id', $project->id)->delete();
                    $project->delete();
                    if ($verbose) {
                        $this->info("Pruned project #{$project->id} (removed states, datasets, etc.).");
                    }
                }
            }
        }
        
        if ($verbose) {
            $this->info("Statistics for removed entries:");
            $this->batchOutput($stat);
        }

        $this->info("Cleanup process complete.");
    }

    private function selectCorporation(): ?NetworkCorporation
    {
        $corporations = NetworkCorporation::all();
        $this->info("Limit cleanup to a specific Corporation?");
        foreach ($corporations as $corp) {
            $this->line("[{$corp->id}] : {$corp->name}", "cyan");
        }
        $this->line("[0]: Perform cleanup on all Corporations.", "yellow");

        $corporationId = (int)$this->ask("Select number from above:");
        return ($corporationId === 0) ? null : NetworkCorporation::find($corporationId);
    }

    private function startInteractiveSession(): array
    {
        $daysOld = $this->ask("How old should an entry be to be deleted? (number of days, default: 30)");
        $daysOld = $daysOld ?: 30;
        $corporation = $this->selectCorporation();
        $pruneInput = $this->ask("Prune projects, states, and datasets too? [Yes/No]");
        $forceInput = $this->ask("Clean history (and if prune, everything) for projects without a definitive outcome? [Yes/No]");

        $prune = str_contains(strtolower($pruneInput ?? ''), 'y');
        $force = str_contains(strtolower($forceInput ?? ''), 'y');

        return [$daysOld, $corporation, $prune, $force];
    }

    /**
     * Display statistics on matching records and estimated storage sizes.
     *
     * @param Carbon $cutoffDate
     * @param NetworkCorporation|null $corporation
     * @param bool $force
     */
    private function showStats(Carbon $cutoffDate, $corporation, bool $force) : array
    {
        // Stat output
        $statOutput = [];
        // Build a common project query filter.
        $projectQuery = NetworkProject::query()
            ->where('updated_at', '<', $cutoffDate);

        if (!$force) {
            $projectQuery->whereNotNull('resolution');
        }
        if ($corporation) {
            $projectQuery->where('corporation_id', $corporation->id);
        }

        $projectIds = $projectQuery->pluck('id')->toArray();

        // Set up our stats array for each model.
        $stats = [];

        // History Stats.
        $historyQuery = NetworkHistory::query()
            ->where('created_at', '<', $cutoffDate)
            ->whereHas('project', function ($query) use ($corporation, $force, $cutoffDate) {
                if (!$force) {
                    $query->whereNotNull('resolution');
                }
                if ($corporation) {
                    $query->where('corporation_id', $corporation->id);
                }
                $query->where('updated_at', '<', $cutoffDate);
            });
        $stats['NetworkHistory'] = [
            'filteredCount' => $historyQuery->count(),
            'table'         => (new NetworkHistory)->getTable()
        ];

        // Project Stats.
        $stats['NetworkProject'] = [
            'filteredCount' => $projectQuery->count(),
            'table'         => (new NetworkProject)->getTable()
        ];

        // DataSet Stats.
        $datasetQuery = NetworkDataSet::query()->whereIn('project_id', $projectIds);
        $stats['NetworkDataSet'] = [
            'filteredCount' => $datasetQuery->count(),
            'table'         => (new NetworkDataSet)->getTable()
        ];

        // State Stats.
        $stateQuery = NetworkState::query()->whereIn('project_id', $projectIds);
        $stats['NetworkState'] = [
            'filteredCount' => $stateQuery->count(),
            'table'         => (new NetworkState)->getTable()
        ];

        foreach ($stats as $label => $data) {
            $filteredCount = $data['filteredCount'];
            $tableName = $data['table'];

            $tableStats = $this->getTableStats($tableName);
            if (!$tableStats) {
                $statOutput[] = ["level" => "line", "message" => "{$label}: {$filteredCount} record(s) - (Table status unavailable)"];
                continue;
            }

            // Avoid division by zero.
            $totalRows = $tableStats['rows'] ?: 1;
            $totalSize = $tableStats['size'] ?: 0;
            // Approximate filtered size as proportion of total rows.
            $approxSize = ($filteredCount / $totalRows) * $totalSize;
            $statOutput[] = ["level" => "line", "message" => "{$label}: {$filteredCount} record(s) - " . $this->formatBytes($approxSize) . " estimated"];
        }

        return $statOutput;
    }

    /**
     * Retrieve table statistics (row count and total size) for supported database drivers.
     *
     * @param string $tableName
     * @return array|null ['rows' => int, 'size' => int] or null if the driver is unsupported.
     */
    private function getTableStats(string $tableName): ?array
    {
        $connection = DB::connection();
        // 1st Party Drivers: 'sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'
        $driver = $connection->getDriverName(); 

        $driver = ($driver == 'mariadb') ? 'mysql' : $driver;
        
        switch ($driver) {
            case 'mysql':
                // MySQL and MariaDB use the same syntax.
                $result = DB::select("SHOW TABLE STATUS WHERE Name = ?", [$tableName]);
                if (empty($result)) {
                    return null;
                }
                $status = $result[0];
                return [
                    'rows' => (int) ($status->Rows ?? 0),
                    'size' => ((int) ($status->Data_length ?? 0)) + ((int) ($status->Index_length ?? 0))
                ];
            case 'pgsql':
                // PostgreSQL: use pg_class and pg_total_relation_size()
                $rowCountResult = DB::select("SELECT reltuples AS rows FROM pg_class WHERE relname = ?", [$tableName]);
                $rows = !empty($rowCountResult) ? (int) $rowCountResult[0]->rows : 0;
                $sizeResult = DB::select("SELECT pg_total_relation_size(?) AS size", [$tableName]);
                $size = !empty($sizeResult) ? (int) $sizeResult[0]->size : 0;
                return [
                    'rows' => $rows,
                    'size' => $size
                ];
            case 'sqlite':
                // SQLite: get row count using Eloquent and size via PRAGMA.
                $rowCount = DB::table($tableName)->count();
                $pageCountResult = DB::select("PRAGMA page_count");
                $pageSizeResult = DB::select("PRAGMA page_size");
                $pageCount = isset($pageCountResult[0]->page_count) ? (int) $pageCountResult[0]->page_count : 0;
                $pageSize = isset($pageSizeResult[0]->page_size) ? (int) $pageSizeResult[0]->page_size : 0;
                $size = $pageCount * $pageSize;
                return [
                    'rows' => $rowCount,
                    'size' => $size,
                ];
            case 'sqlsrv':
                // SQL Server: use sp_spaceused.
                $result = DB::select("EXEC sp_spaceused ?", [$tableName]);
                if (empty($result)) {
                    return null;
                }
                // sp_spaceused returns columns 'rows' and 'reserved' (e.g., "1234 KB")
                $rows = (int) str_replace(',', '', $result[0]->rows);
                $reserved = $result[0]->reserved;
                // Parse reserved size.
                preg_match('/([\d,\.]+)\s*(\w+)/', $reserved, $matches);
                if (count($matches) < 3) {
                    $size = 0;
                } else {
                    $sizeValue = (float) str_replace(',', '', $matches[1]);
                    $unit = strtoupper($matches[2]);
                    $multiplier = match ($unit) {
                        'KB' => 1024,
                        'MB' => 1024 * 1024,
                        'GB' => 1024 * 1024 * 1024,
                        default => 1,
                    };
                    $size = $sizeValue * $multiplier;
                }
                return [
                    'rows' => $rows,
                    'size' => $size,
                ];
            default:
                $this->warn("Database driver [$driver] is outside the supported scope, can't stat table space use.");
                return null;
        }
    }

    /**
     * Format bytes into a human-readable string.
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}