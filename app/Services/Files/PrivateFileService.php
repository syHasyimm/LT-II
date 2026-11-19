<?php

namespace App\Services\Files;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Penyimpanan file pribadi (FR-DOC-04): disk privat, nama file acak, dan
 * hanya disajikan lewat controller yang sudah memeriksa otorisasi.
 */
class PrivateFileService
{
    public function store(UploadedFile $file, string $directory): string
    {
        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
        $name = Str::uuid()->toString().'.'.$extension;

        $path = $this->disk()->putFileAs($directory, $file, $name);

        if ($path === false) {
            throw new \RuntimeException('Gagal menyimpan file.');
        }

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path !== null && $path !== '') {
            $this->disk()->delete($path);
        }
    }

    public function exists(?string $path): bool
    {
        return $path !== null && $path !== '' && $this->disk()->exists($path);
    }

    /** Tampilkan file di browser (inline) dengan nama unduhan yang rapi. */
    public function inline(string $path, string $downloadName): StreamedResponse
    {
        return $this->disk()->response($path, $downloadName, [
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function disk(): FilesystemAdapter
    {
        return Storage::disk((string) config('camp.private_disk'));
    }
}
