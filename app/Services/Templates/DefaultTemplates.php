<?php

namespace App\Services\Templates;

use App\Enums\TemplateTarget;
use App\Enums\TemplateType;
use App\Models\Template;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Template bawaan yang meniru desain FigCraft ("03 · ID Card & Sertifikat").
 * Latar diekspor dari FigCraft tanpa lapisan dinamis (resources/templates),
 * posisi field dikonversi dari piksel desain ke mm (ID Card 6 px/mm,
 * sertifikat 4 px/mm).
 */
class DefaultTemplates
{
    /** Buat template bawaan bila belum ada satu pun template. */
    public static function install(): void
    {
        if (Template::query()->exists()) {
            return;
        }

        foreach (self::definitions() as $definition) {
            $background = $definition['background'];
            unset($definition['background']);

            $path = 'templates/'.Str::uuid().'.png';
            Storage::disk((string) config('camp.private_disk'))->put($path, (string) file_get_contents(resource_path("templates/{$background}")));

            Template::query()->create([...$definition, 'background_path' => $path, 'is_active' => true]);
        }
    }

    /** @return list<array<string, mixed>> */
    public static function definitions(): array
    {
        $card = config('camp.id_card_mm');
        $certificate = config('camp.certificate_mm');

        return [
            [
                'type' => TemplateType::IdCard,
                'applies_to' => TemplateTarget::Peserta,
                'name' => 'ID Card Peserta 2026',
                'background' => 'idcard-peserta.png',
                'width_mm' => $card['width'],
                'height_mm' => $card['height'],
                'fields' => self::idCardFields(roleBg: '#A51C24', roleColor: '#E8CF8A'),
            ],
            [
                'type' => TemplateType::IdCard,
                'applies_to' => TemplateTarget::Pembina,
                'name' => 'ID Card Pembina 2026',
                'background' => 'idcard-pembina.png',
                'width_mm' => $card['width'],
                'height_mm' => $card['height'],
                'fields' => self::idCardFields(roleBg: '#C9A24A', roleColor: '#3E070B'),
            ],
            [
                'type' => TemplateType::Certificate,
                'applies_to' => TemplateTarget::All,
                'name' => 'Sertifikat 2026',
                'background' => 'sertifikat.png',
                'width_mm' => $certificate['width'],
                'height_mm' => $certificate['height'],
                'fields' => self::certificateFields(),
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private static function idCardFields(string $roleBg, string $roleColor): array
    {
        return [
            self::photo('Foto', x: 16.5, y: 15.33, w: 22, h: 27.33, radius: 2.33, border: '#C9A24A'),
            self::text('Nama', '{nama}', x: 2.67, y: 45.67, w: 49.67, h: 4.67, font: 'display', weight: 700, size: 9.9),
            self::text('Peran', '{peran}', x: 14.83, y: 52, w: 25.33, h: 4.33, size: 4.7, weight: 700, color: $roleColor, uppercase: true, letterSpacing: 0.12, bg: $roleBg, radius: 2.17),
            self::text('Nama gudep', '{gudep}', x: 2.67, y: 58.67, w: 49.67, h: 3, size: 6.6, weight: 700),
            self::text('Asal sekolah', '{sekolah} · {alamat}', x: 2.67, y: 62, w: 49.67, h: 2.4, size: 5.2, color: '#7A6A65'),
            self::text('Nomor seri', '{nomor_seri}', x: 3.67, y: 76.33, w: 30, h: 3.17, font: 'mono', weight: 700, size: 7.6, color: '#E8CF8A', align: 'left', letterSpacing: 0.04),
            self::qr('QR verifikasi', x: 41.67, y: 72, size: 10.67),
        ];
    }

    /** @return list<array<string, mixed>> */
    private static function certificateFields(): array
    {
        $year = config('camp.event_year');

        return [
            self::text('Nama', '{nama}', x: 23.5, y: 93, w: 250, h: 18.75, font: 'display', weight: 700, italic: true, size: 39.7),
            self::text('Keterangan', 'atas partisipasinya sebagai {peran_lengkap}, {gudep} – {sekolah}, {alamat}, dalam kegiatan Perkemahan Lomba Tingkat II Tahun {tahun}.', x: 53.5, y: 120, w: 190, h: 14, size: 12, color: '#4A3A36'),
            self::text('Tempat & tanggal', "Sleman, 20 Desember {$year}\nKetua Panitia", x: 40, y: 155, w: 65, h: 10.5, size: 9.2, weight: 500, color: '#4A3A36'),
            self::text('Nama ketua panitia', 'Nama Ketua Panitia', x: 40, y: 180, w: 65, h: 4.5, size: 9.9, weight: 700),
            self::text('Mengetahui', "Mengetahui,\nKetua Kwartir Cabang", x: 192, y: 155, w: 65, h: 10.5, size: 9.2, weight: 500, color: '#4A3A36'),
            self::text('Nama ketua kwarcab', 'Nama Ketua Kwarcab', x: 192, y: 180, w: 65, h: 4.5, size: 9.9, weight: 700),
            self::qr('QR verifikasi', x: 263, y: 170, size: 16.5),
            self::text('Nomor seri', 'No. {nomor_seri}  ·  verifikasi: /verify/{nomor_seri}', x: 175, y: 188, w: 103, h: 2.75, size: 6.4, weight: 500, color: '#9A8A84', align: 'right'),
        ];
    }

    /** @return array<string, mixed> */
    public static function text(
        string $label,
        string $content,
        float $x,
        float $y,
        float $w,
        float $h,
        string $font = 'sans',
        int $weight = 400,
        bool $italic = false,
        float $size = 8,
        string $color = '#2A1416',
        string $align = 'center',
        bool $uppercase = false,
        float $letterSpacing = 0,
        ?string $bg = null,
        float $radius = 0,
    ): array {
        return [
            'id' => (string) Str::uuid(),
            'kind' => 'text',
            'label' => $label,
            'enabled' => true,
            'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h,
            'content' => $content,
            'font' => $font,
            'weight' => $weight,
            'italic' => $italic,
            'size' => $size,
            'color' => $color,
            'align' => $align,
            'uppercase' => $uppercase,
            'letter_spacing' => $letterSpacing,
            'bg' => $bg,
            'radius' => $radius,
            'border' => null,
        ];
    }

    /** @return array<string, mixed> */
    public static function photo(string $label, float $x, float $y, float $w, float $h, float $radius = 0, ?string $border = null): array
    {
        return [
            'id' => (string) Str::uuid(),
            'kind' => 'photo',
            'label' => $label,
            'enabled' => true,
            'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h,
            'radius' => $radius,
            'border' => $border,
        ];
    }

    /** @return array<string, mixed> */
    public static function qr(string $label, float $x, float $y, float $size): array
    {
        return [
            'id' => (string) Str::uuid(),
            'kind' => 'qr',
            'label' => $label,
            'enabled' => true,
            'x' => $x, 'y' => $y, 'w' => $size, 'h' => $size,
            'radius' => 1,
        ];
    }
}
