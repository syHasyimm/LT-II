<?php

namespace App\Services\Pdf;

/**
 * Perkecil ukuran font field teks satu baris bila teks terlalu panjang untuk
 * lebarnya (misalnya nama dengan gelar), sampai minimal 60% ukuran asli.
 * Lebar teks diperkirakan dari rata-rata lebar karakter per jenis font.
 */
class TextFit
{
    private const PT_TO_MM = 0.3528;

    /** Rata-rata lebar karakter relatif terhadap ukuran font (em). */
    private const CHAR_WIDTH = [
        'display' => 0.54,
        'sans' => 0.56,
        'mono' => 0.6,
    ];

    /**
     * @param  array<string, mixed>  $field
     */
    public static function size(string $text, array $field): float
    {
        $size = (float) ($field['size'] ?? 8);

        if ($text === '' || str_contains($text, "\n")) {
            return $size;
        }

        $sizeMm = $size * self::PT_TO_MM;
        $singleLine = (float) $field['h'] < $sizeMm * 1.2 * 1.9;

        if (! $singleLine) {
            return $size;
        }

        $factor = self::CHAR_WIDTH[$field['font'] ?? 'sans'] ?? 0.56;

        if (! empty($field['uppercase'])) {
            $text = mb_strtoupper($text);
            $factor += 0.08;
        }

        if ((int) ($field['weight'] ?? 400) >= 700) {
            $factor += 0.03;
        }

        $perChar = $factor + (float) ($field['letter_spacing'] ?? 0);
        $available = (float) $field['w'] - (! empty($field['bg']) ? $sizeMm * 1.2 : 0);
        $estimated = mb_strlen($text) * $perChar * $sizeMm;

        if ($estimated <= $available) {
            return $size;
        }

        return round(max($size * 0.6, $size * $available / $estimated), 2);
    }
}
