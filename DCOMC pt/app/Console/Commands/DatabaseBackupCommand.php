<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'backup:database';

    protected $description = 'Run daily database backup at 5 PM if auto backup is enabled (replaces previous backup file).';

    public function handle(): int
    {
        if (! DatabaseBackupService::isAutoBackupEnabled()) {
            $this->info('Auto backup is disabled. Skipping.');

            return self::SUCCESS;
        }

        try {
            DatabaseBackupService::deleteExistingAutoBackup();
            DatabaseBackupService::createBackup(DatabaseBackupService::autoBackupPath());
            $this->info('Database backup completed: ' . DatabaseBackupService::autoBackupPath());
        } catch (\Throwable $e) {
            $this->error('Backup failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
