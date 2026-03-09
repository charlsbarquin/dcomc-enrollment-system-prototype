<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DatabaseBackupService
{
    public const CONFIG_PATH = 'backup_config.json';

    public const AUTO_BACKUP_FILENAME = 'dcomc_auto_backup.sql';

    /**
     * Get backup config (stored in storage/app/backup_config.json).
     */
    public static function configPath(): string
    {
        return storage_path('app/' . self::CONFIG_PATH);
    }

    public static function isAutoBackupEnabled(): bool
    {
        $path = self::configPath();
        if (! File::exists($path)) {
            return false;
        }
        $json = @file_get_contents($path);
        if ($json === false) {
            return false;
        }
        $data = @json_decode($json, true);

        return ! empty($data['auto_backup_enabled']);
    }

    public static function setAutoBackupEnabled(bool $enabled): void
    {
        $path = self::configPath();
        $dir = dirname($path);
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        File::put($path, json_encode([
            'auto_backup_enabled' => $enabled,
            'updated_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT));
    }

    /**
     * Path to the single auto backup file (replaced each run).
     */
    public static function autoBackupPath(): string
    {
        $dir = storage_path('app/backups');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        return $dir . '/' . self::AUTO_BACKUP_FILENAME;
    }

    /**
     * Delete the existing auto backup file if present.
     */
    public static function deleteExistingAutoBackup(): void
    {
        $path = self::autoBackupPath();
        if (File::exists($path)) {
            File::delete($path);
        }
    }

    /**
     * Run database dump and save to the given path (or return content for stream).
     * Uses PHP/PDO only (no mysqldump required).
     */
    public static function createBackup(?string $savePath = null): string
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver !== 'mysql') {
            throw new \RuntimeException('Database backup is only supported for MySQL.');
        }

        $database = config("database.connections.{$connection}.database");
        $output = "-- DCOMC Database Backup\n";
        $output .= "-- Generated at " . now()->toIso8601String() . "\n";
        $output .= "-- Database: {$database}\n\n";
        $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        $tables = DB::select('SHOW TABLES');
        $tableKey = 'Tables_in_' . $database;

        foreach ($tables as $table) {
            $tableName = $table->{$tableKey};
            $output .= self::dumpTable($tableName);
        }

        $output .= "SET FOREIGN_KEY_CHECKS=1;\n";

        if ($savePath !== null) {
            File::put($savePath, $output);
        }

        return $output;
    }

    protected static function dumpTable(string $tableName): string
    {
        $out = "DROP TABLE IF EXISTS `" . self::escapeIdentifier($tableName) . "`;\n";

        $create = DB::selectOne('SHOW CREATE TABLE `' . self::escapeIdentifier($tableName) . '`');
        $createKey = 'Create Table';
        if ($create && isset($create->{$createKey})) {
            $out .= $create->{$createKey} . ";\n\n";
        }

        $first = DB::table($tableName)->first();
        if ($first === null) {
            return $out . "\n";
        }

        $columns = array_keys((array) $first);
        $colList = implode(', ', array_map(fn ($c) => '`' . self::escapeIdentifier($c) . '`', $columns));
        $insertPart = '';

        DB::table($tableName)->orderBy($columns[0])->chunk(100, function ($rows) use ($tableName, $columns, $colList, &$insertPart) {
            $values = [];
            foreach ($rows as $row) {
                $vals = [];
                foreach ($columns as $col) {
                    $v = $row->$col ?? null;
                    $vals[] = self::quoteValue($v);
                }
                $values[] = '(' . implode(', ', $vals) . ')';
            }
            $insertPart .= 'INSERT INTO `' . self::escapeIdentifier($tableName) . '` (' . $colList . ") VALUES\n";
            $insertPart .= implode(",\n", $values) . ";\n";
        });

        return $out . $insertPart . "\n";
    }

    protected static function escapeIdentifier(string $s): string
    {
        return str_replace('`', '``', $s);
    }

    protected static function quoteValue(mixed $v): string
    {
        if ($v === null) {
            return 'NULL';
        }
        if (is_int($v) || is_float($v)) {
            return (string) $v;
        }
        if (is_bool($v)) {
            return $v ? '1' : '0';
        }

        return "'" . str_replace(["\r", "\n", "\\", "'"], ["\\r", "\\n", "\\\\", "''"], (string) $v) . "'";
    }

    /**
     * Run scheduled auto backup: delete old file, then create new one (only if enabled).
     */
    public static function runScheduledBackup(): bool
    {
        if (! self::isAutoBackupEnabled()) {
            return false;
        }
        self::deleteExistingAutoBackup();
        self::createBackup(self::autoBackupPath());

        return true;
    }

    /**
     * Get the path of the current auto backup file if it exists.
     */
    public static function getCurrentBackupPath(): ?string
    {
        $path = self::autoBackupPath();

        return File::exists($path) ? $path : null;
    }
}
