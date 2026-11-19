<?php

namespace App\Services\Pdf;

/**
 * Potong pas foto ke rasio field (seperti CSS `object-fit: cover`), karena
 * dompdf tidak mendukung object-fit.
 */
class PhotoCropper
{
    public static function cover(string $dataUri, float $widthMm, float $heightMm, int $maxPx = 700): string
    {
        $raw = base64_decode(substr($dataUri, (int) strpos($dataUri, ',') + 1), true);
        $source = $raw !== false ? @imagecreatefromstring($raw) : false;

        if ($source === false || $widthMm <= 0 || $heightMm <= 0) {
            return $dataUri;
        }

        $sw = imagesx($source);
        $sh = imagesy($source);
        $ratio = $widthMm / $heightMm;

        if ($sw / $sh > $ratio) {
            $cropW = (int) round($sh * $ratio);
            $cropH = $sh;
        } else {
            $cropW = $sw;
            $cropH = (int) round($sw / $ratio);
        }

        $targetH = max(1, min($maxPx, $cropH));
        $targetW = max(1, (int) round($targetH * $ratio));
        $target = imagecreatetruecolor($targetW, $targetH);
        imagecopyresampled($target, $source, 0, 0, (int) (($sw - $cropW) / 2), (int) (($sh - $cropH) / 2), $targetW, $targetH, $cropW, $cropH);

        ob_start();
        imagejpeg($target, null, 88);

        return 'data:image/jpeg;base64,'.base64_encode((string) ob_get_clean());
    }
}
