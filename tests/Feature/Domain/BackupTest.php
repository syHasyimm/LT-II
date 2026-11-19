<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    $this->backupDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'lt2-backup-test-'.uniqid();
    config(['camp.backup.path' => $this->backupDir]);
});

afterEach(function () {
    File::deleteDirectory($this->backupDir);
});

/** @return list<string> */
function zipEntries(string $path): array
{
    // Baca central directory ZIP tanpa ekstensi zip: cari tanda tangan entri file.
    $data = (string) file_get_contents($path);
    preg_match_all('/PK\x01\x02.{24}(.{2})(.{2})(.{2}).{12}/s', $data, $matches, PREG_OFFSET_CAPTURE);
    $names = [];

    foreach ($matches[0] as $index => [$header, $offset]) {
        $length = unpack('v', $matches[1][$index][0])[1];
        $names[] = substr($data, $offset + 46, $length);
    }

    return $names;
}

it('membuat backup penuh berisi dump database dan dokumen privat', function () {
    Process::fake(function ($process) {
        preg_match('/--result-file=(\S+)/', implode(' ', (array) $process->command), $match);
        file_put_contents($match[1], '-- dump palsu');

        return Process::result();
    });
    config([
        'camp.backup.connection' => 'backup_mysql',
        'database.connections.backup_mysql' => [
            'driver' => 'mysql', 'host' => '127.0.0.1', 'port' => 3306,
            'database' => 'perkemahan_lt2', 'username' => 'root', 'password' => 'rahasia',
        ],
    ]);

    $private = storage_path('app/private/backup-test');
    File::ensureDirectoryExists($private);
    file_put_contents($private.'/dokumen.pdf', '%PDF-1.4 contoh');

    try {
        $this->artisan('app:backup')->assertSuccessful();
    } finally {
        File::deleteDirectory($private);
    }

    $files = glob($this->backupDir.'/lt2-penuh-*.zip');
    expect($files)->toHaveCount(1)
        ->and(zipEntries($files[0]))->toContain('database.sql', 'manifest.json', 'private/backup-test/dokumen.pdf');

    // Kata sandi tidak dikirim sebagai argumen perintah.
    Process::assertRan(fn ($process) => ! str_contains(implode(' ', (array) $process->command), 'rahasia')
        && ($process->environment['MYSQL_PWD'] ?? null) === 'rahasia');
});

it('membuat backup database saja dan menyimpan sejumlah backup terbaru', function () {
    $database = tempnam(sys_get_temp_dir(), 'lt2sqlite');
    file_put_contents($database, 'SQLite format 3');
    config([
        'camp.backup.connection' => 'backup_sqlite',
        'database.connections.backup_sqlite' => ['driver' => 'sqlite', 'database' => $database],
        'camp.backup.keep_daily' => 2,
    ]);

    File::ensureDirectoryExists($this->backupDir);
    foreach (['20260101-010000', '20260102-010000', '20260103-010000'] as $stamp) {
        touch("{$this->backupDir}/lt2-db-{$stamp}.zip");
    }

    $this->artisan('app:backup', ['--db-only' => true])->assertSuccessful();
    @unlink($database);

    $files = array_map('basename', glob($this->backupDir.'/lt2-db-*.zip'));
    expect($files)->toHaveCount(2)
        ->and($files)->toContain('lt2-db-20260103-010000.zip')
        ->and(zipEntries($this->backupDir.'/'.end($files)))->toContain('database.sqlite')
        ->not->toContain('private/');
});

it('melaporkan kegagalan backup', function () {
    Process::fake(fn () => Process::result(errorOutput: 'Access denied', exitCode: 2));
    config([
        'camp.backup.connection' => 'backup_mysql',
        'database.connections.backup_mysql' => ['driver' => 'mysql', 'host' => 'h', 'port' => 3306, 'database' => 'd', 'username' => 'u', 'password' => ''],
    ]);

    $this->artisan('app:backup', ['--db-only' => true])->assertFailed();
    expect(glob($this->backupDir.'/*.zip') ?: [])->toBeEmpty();
});

it('menjadwalkan backup harian dan mingguan', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('app:backup --db-only')
        ->assertSuccessful();
});
