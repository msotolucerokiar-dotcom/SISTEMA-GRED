<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use PDO;
use RuntimeException;

final class MigrationRunner
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function migrate(string $migrationDirectory, string $seedDirectory): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (filename VARCHAR(190) PRIMARY KEY, applied_at TIMESTAMPTZ NOT NULL DEFAULT NOW())'
        );
        $this->applyDirectory($migrationDirectory);
        $this->applyDirectory($seedDirectory);
    }

    private function applyDirectory(string $directory): void
    {
        $files = glob(rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.sql');
        if ($files === false) {
            throw new RuntimeException("Cannot read SQL directory: {$directory}");
        }
        sort($files, SORT_STRING);

        foreach ($files as $file) {
            $name = basename($file);
            $check = $this->pdo->prepare('SELECT 1 FROM schema_migrations WHERE filename = :filename');
            $check->execute(['filename' => $name]);
            if ($check->fetchColumn() !== false) {
                continue;
            }

            $sql = file_get_contents($file);
            if ($sql === false) {
                throw new RuntimeException("Cannot read SQL file: {$file}");
            }

            $this->pdo->beginTransaction();
            try {
                $this->pdo->exec($sql);
                $insert = $this->pdo->prepare('INSERT INTO schema_migrations (filename) VALUES (:filename)');
                $insert->execute(['filename' => $name]);
                $this->pdo->commit();
            } catch (\Throwable $exception) {
                $this->pdo->rollBack();
                throw $exception;
            }
        }
    }
}
