<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;

#[Signature('app:backup {--db-only : Hanya dump database, tanpa dokumen privat}')]
#[Description('Backup database dan dokumen privat ke storage/app/backups (BR-13)')]
class BackupCommand extends Command
{
    public function handle(BackupService $backup): int
    {
        $withFiles = ! $this->option('db-only');

        try {
            $result = $backup->run($withFiles);
        } catch (\Throwable $exception) {
            Log::error('Backup gagal', ['error' => $exception->getMessage()]);
            $this->components->error('Backup gagal: '.$exception->getMessage());

            return self::FAILURE;
        }

        Log::info('Backup selesai', $result);
        $this->components->info(sprintf(
            'Backup %s tersimpan: %s (%s, %d file privat).',
            $withFiles ? 'penuh' : 'database',
            $result['path'],
            Number::fileSize($result['size'], 1),
            $result['files'],
        ));

        return self::SUCCESS;
    }
}
