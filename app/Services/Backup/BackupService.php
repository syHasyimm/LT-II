<?php

namespace App\Services\Backup;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Symfony\Component\Finder\SplFileInfo;
use ZipStream\ZipStream;

/**
 * Backup database dan dokumen privat ke satu file ZIP (BR-13).
 *
 * - Harian (`--db-only`): dump database saja, kecil dan cepat.
 * - Penuh: dump database + seluruh storage/app/private (pas foto, dokumen,
 *   ID Card, sertifikat, latar template).
 *
 * ZIP ditulis dengan zipstream-php sehingga ekstensi PHP `zip` tidak perlu.
 */
class BackupService
{
    /**
     * @return array{path: string, size: int, files: int}
     */
    public function run(bool $withFiles = true): array
    {
        $directory = (string) config('camp.backup.path');
        File::ensureDirectoryExists($directory, 0750);

        $stamp = CarbonImmutable::now(config('camp.timezone'))->format('Ymd-His');
        $kind = $withFiles ? 'penuh' : 'db';
        $target = $directory.DIRECTORY_SEPARATOR."lt2-{$kind}-{$stamp}.zip";
        $dump = $this->dumpDatabase();

        $handle = fopen($target, 'wb');

        if ($handle === false) {
            throw new RuntimeException("Tidak bisa menulis {$target}.");
        }

        $count = 0;

        try {
            $zip = new ZipStream(outputStream: $handle, sendHttpHeaders: false, defaultEnableZeroHeader: true);
            $zip->addFileFromPath($dump['name'], $dump['path']);

            if ($withFiles) {
                $private = storage_path('app/private');

                if (is_dir($private)) {
                    /** @var SplFileInfo $file */
                    foreach (File::allFiles($private) as $file) {
                        $zip->addFileFromPath('private/'.str_replace('\\', '/', $file->getRelativePathname()), $file->getPathname());
                        $count++;
                    }
                }
            }

            $zip->addFile('manifest.json', (string) json_encode([
                'app' => config('app.name'),
                'jenis' => $kind,
                'dibuat' => CarbonImmutable::now(config('camp.timezone'))->toIso8601String(),
                'database' => config('camp.backup.connection') ?? config('database.default'),
                'file_privat' => $count,
            ], JSON_PRETTY_PRINT));

            $zip->finish();
        } catch (\Throwable $exception) {
            fclose($handle);
            @unlink($target);

            throw $exception;
        } finally {
            @unlink($dump['path']);
        }

        if (is_resource($handle)) {
            fclose($handle);
        }

        $this->prune($directory, $kind, (int) config($withFiles ? 'camp.backup.keep_full' : 'camp.backup.keep_daily'));

        return ['path' => $target, 'size' => (int) filesize($target), 'files' => $count];
    }

    /**
     * @return array{name: string, path: string}
     */
    private function dumpDatabase(): array
    {
        $connection = (string) (config('camp.backup.connection') ?? config('database.default'));
        /** @var array<string, mixed> $config */
        $config = config("database.connections.{$connection}");
        $temp = (string) tempnam(sys_get_temp_dir(), 'lt2db');

        return match ($config['driver'] ?? null) {
            'mysql', 'mariadb' => $this->dumpMysql($config, $temp),
            'sqlite' => $this->copySqlite($config, $temp),
            default => throw new RuntimeException('Driver database ['.($config['driver'] ?? '?').'] belum didukung backup.'),
        };
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{name: string, path: string}
     */
    private function dumpMysql(array $config, string $temp): array
    {
        $command = [
            (string) config('camp.backup.mysqldump'),
            '--host='.$config['host'],
            '--port='.$config['port'],
            '--user='.$config['username'],
            '--single-transaction',
            '--quick',
            '--routines',
            '--no-tablespaces',
            '--default-character-set=utf8mb4',
            '--result-file='.$temp,
            (string) $config['database'],
        ];

        // Kata sandi lewat variabel lingkungan, bukan argumen (tidak tampil di daftar proses).
        $result = Process::timeout(600)
            ->env(['MYSQL_PWD' => (string) ($config['password'] ?? '')])
            ->run($command);

        if ($result->failed()) {
            @unlink($temp);

            throw new RuntimeException('mysqldump gagal: '.trim($result->errorOutput() ?: $result->output()));
        }

        return ['name' => 'database.sql', 'path' => $temp];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{name: string, path: string}
     */
    private function copySqlite(array $config, string $temp): array
    {
        $database = (string) ($config['database'] ?? '');

        if ($database === ':memory:' || ! is_file($database)) {
            @unlink($temp);

            throw new RuntimeException('Database SQLite tidak berupa file, tidak bisa di-backup.');
        }

        File::copy($database, $temp);

        return ['name' => 'database.sqlite', 'path' => $temp];
    }

    /** Simpan hanya N backup terbaru per jenis. */
    private function prune(string $directory, string $kind, int $keep): void
    {
        $files = glob($directory.DIRECTORY_SEPARATOR."lt2-{$kind}-*.zip") ?: [];
        rsort($files);

        foreach (array_slice($files, max($keep, 1)) as $old) {
            @unlink($old);
        }
    }
}
