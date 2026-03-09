<?php

namespace App\Http\Controllers;

use App\Services\DatabaseBackupService;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminSystemController extends Controller
{
    public function overview(Request $request): View
    {
        $cacheDriver = Config::get('cache.default');
        $queueDriver = Config::get('queue.default');

        $pendingJobs = null;
        if ($queueDriver === 'database') {
            try {
                $pendingJobs = DB::table('jobs')->count();
            } catch (\Throwable $e) {
                $pendingJobs = null;
            }
        } elseif ($queueDriver === 'redis') {
            try {
                $connection = Config::get('queue.connections.redis.connection', 'default');
                $queue = Config::get('queue.connections.redis.queue', 'default');
                $key = "queues:{$queue}";
                $pendingJobs = Redis::connection($connection)->llen($key);
            } catch (\Throwable $e) {
                $pendingJobs = null;
            }
        }

        $failedJobs = null;
        try {
            $failedJobs = DB::table('failed_jobs')->count();
        } catch (\Throwable $e) {
            $failedJobs = null;
        }

        $maintenance = app()->isDownForMaintenance();

        return view('dashboards.admin-system-overview', [
            'cacheDriver' => $cacheDriver,
            'queueDriver' => $queueDriver,
            'pendingJobs' => $pendingJobs,
            'failedJobs' => $failedJobs,
            'maintenance' => $maintenance,
        ]);
    }

    public function logs(Request $request): View
    {
        $path = storage_path('logs/laravel.log');
        $exists = File::exists($path);

        $lines = [];
        $entries = [];
        $query = trim((string) $request->get('q', ''));
        $summaryOnly = (bool) $request->boolean('summary');
        $rawMode = (string) $request->get('mode', 'friendly') === 'raw';
        $maxLines = max(50, min(2000, (int) $request->get('lines', 400)));
        $maxBytes = 512 * 1024; // 512KB
        $truncated = false;

        if ($exists) {
            $size = @filesize($path);
            $truncated = $size !== false && $size > $maxBytes;

            $content = $this->tailFile($path, $maxBytes);
            $rawLines = preg_split('/\\r\\n|\\r|\\n/', (string) $content) ?: [];
            $rawLines = array_values(array_filter($rawLines, fn ($l) => $l !== ''));

            if ($query !== '') {
                $rawLines = array_values(array_filter($rawLines, fn ($l) => stripos($l, $query) !== false));
            }

            $lines = array_slice($rawLines, max(0, count($rawLines) - $maxLines));

            // Friendly mode: group lines by log entry (header starts with "[YYYY-MM-DD HH:MM:SS]").
            if (! $rawMode) {
                $entries = $this->parseLogEntries($lines);

                if ($summaryOnly) {
                    $entries = array_values(array_filter($entries, fn ($e) => empty($e['details'])));
                }
            }
        }

        return view('dashboards.admin-system-logs', [
            'logPath' => $path,
            'exists' => $exists,
            'lines' => $lines,
            'entries' => $entries,
            'query' => $query,
            'summaryOnly' => $summaryOnly,
            'rawMode' => $rawMode,
            'maxLines' => $maxLines,
            'truncated' => $truncated,
        ]);
    }

    public function clearLogs(Request $request)
    {
        $path = storage_path('logs/laravel.log');
        if (File::exists($path)) {
            File::put($path, '');
            AuditLogger::log('system.logs.clear', null, ['path' => $path]);
        }

        return redirect()->route('admin.system.logs')->with('success', 'Application log cleared.');
    }

    public function failedJobs(Request $request): View
    {
        $items = collect();
        $enabled = true;

        try {
            $items = DB::table('failed_jobs')
                ->orderByDesc('failed_at')
                ->paginate(25)
                ->withQueryString();
        } catch (\Throwable $e) {
            $enabled = false;
        }

        return view('dashboards.admin-system-failed-jobs', [
            'enabled' => $enabled,
            'failedJobs' => $items,
        ]);
    }

    public function retryFailedJob(Request $request, string $id)
    {
        Artisan::call('queue:retry', ['id' => [$id]]);
        AuditLogger::log('system.failed_job.retry', null, ['id' => $id]);

        return redirect()->route('admin.system.failed-jobs')->with('success', 'Retry triggered for failed job ID: ' . $id);
    }

    public function forgetFailedJob(Request $request, string $id)
    {
        Artisan::call('queue:forget', ['id' => $id]);
        AuditLogger::log('system.failed_job.forget', null, ['id' => $id]);

        return redirect()->route('admin.system.failed-jobs')->with('success', 'Deleted failed job ID: ' . $id);
    }

    public function maintenance(Request $request): View
    {
        return view('dashboards.admin-system-maintenance', [
            'maintenance' => app()->isDownForMaintenance(),
        ]);
    }

    public function down(Request $request)
    {
        $validated = $request->validate([
            'retry' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'refresh' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'status' => ['nullable', 'integer', 'min:200', 'max:599'],
            'secret' => ['nullable', 'string', 'max:64'],
            'with_secret' => ['nullable', 'boolean'],
            'redirect' => ['nullable', 'string', 'max:255'],
            'render' => ['nullable', 'string', 'max:255'],
        ]);

        $args = [];
        foreach (['redirect', 'render', 'retry', 'refresh', 'secret', 'status'] as $k) {
            if (array_key_exists($k, $validated) && $validated[$k] !== null && $validated[$k] !== '') {
                $args['--' . $k] = $validated[$k];
            }
        }
        if (!empty($validated['with_secret'])) {
            $args['--with-secret'] = true;
        }

        Artisan::call('down', $args);
        AuditLogger::log('system.maintenance.down', null, $args);

        return redirect()->route('admin.system.maintenance')->with('success', 'Maintenance mode enabled.');
    }

    public function up(Request $request)
    {
        Artisan::call('up');
        AuditLogger::log('system.maintenance.up');

        return redirect()->route('admin.system.maintenance')->with('success', 'Maintenance mode disabled.');
    }

    public function backup(Request $request): View
    {
        $autoBackupEnabled = DatabaseBackupService::isAutoBackupEnabled();
        $hasExistingBackup = DatabaseBackupService::getCurrentBackupPath() !== null;

        return view('dashboards.admin-system-backup', [
            'autoBackupEnabled' => $autoBackupEnabled,
            'hasExistingBackup' => $hasExistingBackup,
        ]);
    }

    public function toggleBackup(Request $request)
    {
        $request->validate(['enabled' => ['required', 'boolean']]);
        $enabled = (bool) $request->input('enabled');
        DatabaseBackupService::setAutoBackupEnabled($enabled);
        AuditLogger::log('system.backup.toggle', null, ['enabled' => $enabled]);

        return redirect()->route('admin.system.backup')->with(
            'success',
            $enabled ? 'Auto backup is now ON. A backup will run every day at 5:00 PM.' : 'Auto backup is now OFF.'
        );
    }

    public function downloadBackup(Request $request): StreamedResponse
    {
        AuditLogger::log('system.backup.download');

        $filename = 'dcomc_backup_' . now()->format('Y-m-d_His') . '.sql';

        return response()->streamDownload(function () {
            echo DatabaseBackupService::createBackup(null);
        }, $filename, [
            'Content-Type' => 'application/sql',
        ]);
    }

    private function tailFile(string $path, int $maxBytes): string
    {
        $size = @filesize($path);
        if ($size === false || $size <= $maxBytes) {
            return (string) @file_get_contents($path);
        }

        $fp = @fopen($path, 'rb');
        if (!is_resource($fp)) {
            return '';
        }

        @fseek($fp, -$maxBytes, SEEK_END);
        $data = (string) @fread($fp, $maxBytes);
        @fclose($fp);

        // If we read from the middle of the file, drop the first partial line so the UI doesn't start mid-entry.
        $pos = strpos($data, "\n");
        if ($pos !== false) {
            $data = substr($data, $pos + 1);
        }

        return (string) $data;
    }

    /**
     * @param  array<int, string>  $lines
     * @return array<int, array{header: string, details: string}>
     */
    private function parseLogEntries(array $lines): array
    {
        $isHeader = fn (string $line) => preg_match('/^\\[\\d{4}-\\d{2}-\\d{2}\\s+\\d{2}:\\d{2}:\\d{2}\\]/', $line) === 1;

        $entries = [];
        $currentHeader = null;
        $currentDetails = [];

        foreach ($lines as $line) {
            if ($isHeader($line)) {
                if ($currentHeader !== null) {
                    $entries[] = [
                        'header' => $currentHeader,
                        'details' => implode(PHP_EOL, $currentDetails),
                    ];
                }
                $currentHeader = $line;
                $currentDetails = [];
                continue;
            }

            if ($currentHeader === null) {
                // Skip stray lines (should be rare after tail trim).
                continue;
            }

            $currentDetails[] = $line;
        }

        if ($currentHeader !== null) {
            $entries[] = [
                'header' => $currentHeader,
                'details' => implode(PHP_EOL, $currentDetails),
            ];
        }

        return $entries;
    }
}
