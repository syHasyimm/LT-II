<?php

namespace App\Services\Pdf;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;

/**
 * QR code sebagai PNG (data URI) untuk dimasukkan ke PDF. Digambar dengan GD
 * dari matriks BaconQrCode agar tidak bergantung pada dukungan SVG dompdf.
 */
class QrCodeService
{
    public function pngDataUri(string $content, int $moduleSize = 10, int $quietZone = 2): string
    {
        $matrix = Encoder::encode($content, ErrorCorrectionLevel::M())->getMatrix();
        $modules = $matrix->getWidth();
        $size = max(1, ($modules + $quietZone * 2) * $moduleSize);

        $image = imagecreatetruecolor($size, $size);
        $white = (int) imagecolorallocate($image, 255, 255, 255);
        $ink = (int) imagecolorallocate($image, 42, 20, 22);
        imagefill($image, 0, 0, $white);

        for ($y = 0; $y < $modules; $y++) {
            for ($x = 0; $x < $modules; $x++) {
                if ($matrix->get($x, $y) === 1) {
                    $left = ($x + $quietZone) * $moduleSize;
                    $top = ($y + $quietZone) * $moduleSize;
                    imagefilledrectangle($image, $left, $top, $left + $moduleSize - 1, $top + $moduleSize - 1, $ink);
                }
            }
        }

        ob_start();
        imagepng($image);

        return 'data:image/png;base64,'.base64_encode((string) ob_get_clean());
    }
}
