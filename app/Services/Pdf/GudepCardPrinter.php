<?php

namespace App\Services\Pdf;

use App\Enums\TemplateType;
use App\Models\GeneratedFile;
use App\Models\Gudep;
use App\Models\Template;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * PDF cetak ID Card atau Sertifikat satu gudep (FR-GEN-04, FR-GEN-06):
 * satu kartu per halaman atau lembar A4 3 × 3. Memakai nomor seri yang sudah
 * diberikan dan template aktif saat ini. Dipakai admin dan unduhan ID Card
 * pembina.
 */
class GudepCardPrinter
{
    public function __construct(private readonly TemplateRenderer $renderer) {}

    public function hasFiles(Gudep $gudep, TemplateType $type): bool
    {
        return $this->files($gudep, $type)->exists();
    }

    /**
     * Siap diunduh pembina: setiap anggota yang perannya punya template aktif
     * sudah punya berkas selesai dibuat (bukan baru diberi nomor seri), dan
     * minimal ada satu kartu. Mencegah lembar tidak lengkap selama antrean
     * masih berjalan.
     */
    public function isReady(Gudep $gudep, TemplateType $type): bool
    {
        $expected = $gudep->members()
            ->whereIn('role', Template::activeRoles($type))
            ->pluck('id');

        if ($expected->isEmpty()) {
            return false;
        }

        $done = GeneratedFile::query()
            ->where('type', $type)
            ->whereNotNull('path')
            ->whereIn('member_id', $expected)
            ->count();

        return $done === $expected->count();
    }

    /** `null` bila belum ada berkas untuk gudep ini atau tak ada template aktif. */
    public function pdf(Gudep $gudep, TemplateType $type, string $mode = 'single'): ?DomPdf
    {
        $files = $this->files($gudep, $type)->with('member.gudep')->orderBy('sequence')->get();

        // Template bisa berbeda untuk peserta dan pembina: kelompokkan per template.
        /** @var array<int, array{template: Template, subjects: list<RenderSubject>}> $groups */
        $groups = [];

        foreach ($files as $file) {
            $template = Template::activeFor($type, $file->member->role);

            if ($template !== null) {
                $groups[$template->id]['template'] = $template;
                $groups[$template->id]['subjects'][] = RenderSubject::fromMember($file->member, $file->serial_number);
            }
        }

        if ($groups === []) {
            return null;
        }

        $first = reset($groups);

        // dompdf merender satu dokumen: gabungkan halaman dari tiap template.
        return count($groups) === 1
            ? $this->renderer->pdf($first['template'], $first['subjects'], $mode)
            : $this->renderer->pdfFromHtml(
                self::mergeHtml(array_values(array_map(fn (array $group) => $this->renderer->html($group['template'], $group['subjects'], $mode), $groups))),
                $first['template'],
                $mode,
            );
    }

    /** @return Builder<GeneratedFile> */
    private function files(Gudep $gudep, TemplateType $type)
    {
        return GeneratedFile::query()
            ->where('type', $type)
            ->whereHas('member', fn ($query) => $query->where('gudep_id', $gudep->id));
    }

    /** @param list<string> $documents */
    private static function mergeHtml(array $documents): string
    {
        $head = (string) Str::before($documents[0], '<body>');
        $last = count($documents) - 1;
        $bodies = array_map(
            // Hanya halaman terakhir dokumen terakhir yang tanpa pemisah halaman.
            fn (string $html, int $index) => $index === $last
                ? Str::between($html, '<body>', '</body>')
                : str_replace('page last', 'page', Str::between($html, '<body>', '</body>')),
            $documents,
            array_keys($documents),
        );

        return $head.'<body>'.implode('', $bodies).'</body></html>';
    }
}
