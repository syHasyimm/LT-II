<?php

namespace App\Services\Import;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Shuchkin\SimpleXLSX;

/**
 * Membaca sheet pertama file XLSX atau CSV menjadi baris teks. Tidak butuh
 * ekstensi PHP `zip`. File tidak disimpan, hanya dibaca dari lokasi sementara.
 */
class SpreadsheetReader
{
    /**
     * @return list<list<string>>
     */
    public static function rows(UploadedFile $file, string $field = 'file'): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        $rows = $path === false ? null : match ($extension) {
            'xlsx' => self::xlsx($path),
            'csv', 'txt' => self::csv($path),
            default => null,
        };

        if ($rows === null) {
            throw ValidationException::withMessages([
                $field => 'File tidak dapat dibaca. Gunakan template Excel (.xlsx) atau file CSV.',
            ]);
        }

        return $rows;
    }

    /** @return list<list<string>>|null */
    private static function xlsx(string $path): ?array
    {
        $xlsx = SimpleXLSX::parseFile($path);

        if ($xlsx === false) {
            return null;
        }

        return array_values(array_map(
            fn (array $row) => array_values(array_map(fn ($cell) => trim((string) $cell), $row)),
            $xlsx->rows(0),
        ));
    }

    /** @return list<list<string>>|null */
    private static function csv(string $path): ?array
    {
        $content = file_get_contents($path);

        if ($content === false) {
            return null;
        }

        // CSV dari Excel Windows lama bisa berenkode Windows-1252.
        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = (string) mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        // Buang BOM UTF-8 dari Excel; tebak pemisah (Excel berbahasa Indonesia memakai titik koma).
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        $firstLine = strtok($content, "\r\n") ?: '';
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return null;
        }

        fwrite($handle, $content);
        rewind($handle);

        $rows = [];

        while (($row = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
            $rows[] = array_map(fn ($cell) => trim((string) $cell), $row);
        }

        fclose($handle);

        return $rows;
    }
}
